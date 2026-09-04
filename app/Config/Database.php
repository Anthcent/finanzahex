<?php

namespace Config;

use CodeIgniter\Database\Config;

/**
 * Database Configuration
 */
class Database extends Config
{
    /**
     * The directory that holds the Migrations
     * and Seeds directories.
     */
    public string $filesPath = APPPATH . 'Database' . DIRECTORY_SEPARATOR;

    /**
     * Lets you choose which connection group to
     * use if no other is specified.
     */
    public string $defaultGroup = 'default';

    /**
     * The default database connection.
     *
     * @var array<string, mixed>
     */
    public array $default = [
        'DSN'          => '',
        'hostname'     => 'localhost',
        'username'     => '',
        'password'     => '',
        'database'     => 'finazapersonal',
        'DBDriver'     => 'MySQLi',
        'DBPrefix'     => '',
        'pConnect'     => false,
        'DBDebug'      => true,
        'charset'      => 'utf8',
        'DBCollat'     => 'utf8_general_ci',
        'swapPre'      => '',
        'encrypt'      => false,
        'compress'     => false,
        'strictOn'     => false,
        'failover'     => [],
        'port'         => 3306,
        'numberNative' => false,
    ];

    /**
     * This database connection is used when
     * running PHPUnit database tests.
     *
     * @var array<string, mixed>
     */
    public array $tests = [
        'DSN'         => '',
        'hostname'    => '127.0.0.1',
        'username'    => '',
        'password'    => '',
        'database'    => ':memory:',
        'DBDriver'    => 'SQLite3',
        'DBPrefix'    => 'db_',  // Needed to ensure we're working correctly with prefixes live. DO NOT REMOVE FOR CI DEVS
        'pConnect'    => false,
        'DBDebug'     => true,
        'charset'     => 'utf8',
        'DBCollat'    => 'utf8_general_ci',
        'swapPre'     => '',
        'encrypt'     => false,
        'compress'    => false,
        'strictOn'    => false,
        'failover'    => [],
        'port'        => 3306,
        'foreignKeys' => true,
        'busyTimeout' => 1000,
    ];

    public function __construct()
    {
        parent::__construct();

        // Support for DATABASE_URL (injected by Dokploy / Hexper Ops / Cloud providers)
        $databaseUrl = getenv('DATABASE_URL') ?: (getenv('database.default.url') ?: null);
        if (!empty($databaseUrl)) {
            $parsed = parse_url($databaseUrl);
            if ($parsed !== false) {
                $scheme = strtolower($parsed['scheme'] ?? '');
                if ($scheme === 'postgres' || $scheme === 'postgresql') {
                    $this->default['DBDriver'] = 'Postgre';
                    $this->default['port']     = isset($parsed['port']) ? (int) $parsed['port'] : 5432;
                    $this->default['charset']  = 'utf8';
                    $this->default['DBCollat'] = 'utf8_general_ci';
                } elseif ($scheme === 'mysql' || $scheme === 'mysqli') {
                    $this->default['DBDriver'] = 'MySQLi';
                    $this->default['port']     = isset($parsed['port']) ? (int) $parsed['port'] : 3306;
                }

                if (!empty($parsed['host'])) {
                    $this->default['hostname'] = $parsed['host'];
                }
                if (isset($parsed['user'])) {
                    $this->default['username'] = urldecode($parsed['user']);
                }
                if (isset($parsed['pass'])) {
                    $this->default['password'] = urldecode($parsed['pass']);
                }
                if (isset($parsed['path'])) {
                    $this->default['database'] = urldecode(ltrim($parsed['path'], '/'));
                }
            }
        } else {
            // Support for individual environment variables
            if ($dbDriver = getenv('DB_DRIVER')) {
                $this->default['DBDriver'] = $dbDriver;
            }
            if ($dbHost = getenv('DB_HOST')) {
                $this->default['hostname'] = $dbHost;
            }
            if ($dbPort = getenv('DB_PORT')) {
                $this->default['port'] = (int) $dbPort;
            }
            if ($dbUser = getenv('DB_USER')) {
                $this->default['username'] = $dbUser;
            }
            if ($dbPass = getenv('DB_PASSWORD')) {
                $this->default['password'] = $dbPass;
            }
            if ($dbName = getenv('DB_NAME')) {
                $this->default['database'] = $dbName;
            }
        }

        // Ensure that we always set the database group to 'tests' if
        // we are currently running an automated test suite, so that
        // we don't overwrite live data on accident.
        if (ENVIRONMENT === 'testing') {
            $this->defaultGroup = 'tests';
        }
    }
}
