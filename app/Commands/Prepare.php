<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class Prepare extends BaseCommand
{
    protected $group = 'Deployment';
    protected $name = 'app:prepare';
    protected $description = 'Wait for the database and migrate before serving HTTP.';

    public function run(array $params)
    {
        try {
            $config = config('Database');
            $config->default['DBDebug'] = true;
            $db = \Config\Database::connect($config->default, false);
        } catch (\Throwable $e) {
            CLI::error('Invalid database configuration. Configure DATABASE_URL in the runtime environment.');
            exit(1);
        }

        $connected = false;
        for ($attempt = 0; $attempt < 15; $attempt++) {
            try {
                $db->initialize();
                $connected = $db->connID !== false;
            } catch (\Throwable $e) {
                // Never print connection strings or credentials to the deployment log.
            }
            if ($connected) {
                break;
            }
            CLI::write('Waiting for database (' . ($attempt + 1) . '/15)...');
            sleep(2);
        }
        if (!$connected) {
            CLI::error('Database unavailable. Check runtime configuration and the private database network.');
            exit(1);
        }

        $locked = false;
        $transaction = false;
        try {
            // Concurrent replicas must not apply the same pending migration.
            for ($attempt = 0; $attempt < 60; $attempt++) {
                if ($db->DBDriver === 'Postgre') {
                    $row = $db->query('SELECT CASE WHEN pg_try_advisory_lock(17482, 8080) THEN 1 ELSE 0 END AS acquired')->getRowArray();
                } else {
                    $row = $db->query("SELECT GET_LOCK('fihex_migrations', 1) AS acquired")->getRowArray();
                }
                if ((int) $row['acquired'] === 1) {
                    $locked = true;
                    break;
                }
                sleep(1);
            }
            if (!$locked) {
                throw new \RuntimeException('Migration lock timeout');
            }
            if ($db->DBDriver === 'Postgre') {
                $db->transException(true);
                $db->transBegin();
                $transaction = true;
            }
            $runner = new \CodeIgniter\Database\MigrationRunner(config('Migrations'), $db);
            if (!$runner->setNamespace('App')->latest()) {
                throw new \RuntimeException('Migration failed');
            }
            if ($transaction) {
                if (!$db->transStatus() || !$db->transCommit()) {
                    throw new \RuntimeException('Migration transaction failed');
                }
                $transaction = false;
            }
            CLI::write('Database ready; all application migrations applied.');
        } catch (\Throwable $e) {
            if ($transaction) {
                $db->transRollback();
            }
            CLI::error(get_class($e) . ' at ' . basename($e->getFile()) . ':' . $e->getLine());
            CLI::error('Database migration failed. HTTP startup cancelled; inspect the private application logs.');
            exit(1);
        } finally {
            if ($locked) {
                if ($db->DBDriver === 'Postgre') {
                    $db->query('SELECT pg_advisory_unlock(17482, 8080)');
                } else {
                    $db->query("SELECT RELEASE_LOCK('fihex_migrations')");
                }
            }
            $db->close();
        }
    }
}
