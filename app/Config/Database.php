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
        'hostname'     => '127.0.0.1',
        'username'     => '',
        'password'     => '',
        'database'     => 'finazapersonal',
        'DBDriver'     => 'Postgre',
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
        'port'         => 5432,
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

        $databaseUrl = getenv('DATABASE_URL') ?: getenv('database.default.url');
        if ($databaseUrl) {
            $parsed = parse_url($databaseUrl);
            $scheme = strtolower($parsed['scheme'] ?? '');
            if (!$parsed || !in_array($scheme, ['postgres', 'postgresql', 'mysql', 'mysqli'], true)
                || empty($parsed['host']) || empty(trim($parsed['path'] ?? '', '/'))) {
                throw new \InvalidArgumentException('DATABASE_URL must specify a supported database scheme, host and database.');
            }
            $postgres = in_array($scheme, ['postgres', 'postgresql'], true);
            $this->default['DBDriver'] = $postgres ? 'Postgre' : 'MySQLi';
            $this->default['hostname'] = trim($parsed['host'], '[]');
            $this->default['port'] = $parsed['port'] ?? ($postgres ? 5432 : 3306);
            $this->default['username'] = rawurldecode($parsed['user'] ?? '');
            $this->default['password'] = rawurldecode($parsed['pass'] ?? '');
            $this->default['database'] = rawurldecode(ltrim($parsed['path'], '/'));
            $this->default['DSN'] = '';
            $urlOptions = [];
            parse_str($parsed['query'] ?? '', $urlOptions);
        } else {
            $driver = getenv('DB_DRIVER');
            if ($driver !== false && $driver !== '') {
                $drivers = ['postgres' => 'Postgre', 'postgresql' => 'Postgre', 'postgre' => 'Postgre', 'mysql' => 'MySQLi', 'mysqli' => 'MySQLi'];
                if (!isset($drivers[strtolower($driver)])) {
                    throw new \InvalidArgumentException('Unsupported DB_DRIVER.');
                }
                $this->default['DBDriver'] = $drivers[strtolower($driver)];
            }
            if (getenv('DB_PORT') === false && getenv('database.default.port') === false) {
                $this->default['port'] = $this->default['DBDriver'] === 'Postgre' ? 5432 : 3306;
            }
            foreach (['DB_HOST' => 'hostname', 'DB_PORT' => 'port', 'DB_USER' => 'username', 'DB_PASSWORD' => 'password', 'DB_NAME' => 'database'] as $env => $field) {
                $value = getenv($env);
                if ($value !== false) {
                    $this->default[$field] = $field === 'port' ? (int) $value : $value;
                }
            }
            $this->default['connect_timeout'] = 3;
        }

        if ($this->default['DBDriver'] === 'Postgre') {
            $parameters = [
                'host' => $this->default['hostname'],
                'port' => $this->default['port'],
                'user' => $this->default['username'],
                'password' => $this->default['password'],
                'dbname' => $this->default['database'],
                'connect_timeout' => 3,
            ];
            foreach (['sslmode', 'sslrootcert', 'sslcert', 'sslkey', 'options', 'application_name'] as $option) {
                if (isset($urlOptions[$option]) && is_string($urlOptions[$option])) {
                    $parameters[$option] = $urlOptions[$option];
                }
            }
            $parts = [];
            foreach ($parameters as $key => $value) {
                // libpq quoted values; escaping '/' also prevents CI4's URI autodetection
                // when a credential happens to contain ://.
                $parts[] = $key . "='" . addcslashes((string) $value, "\\'/") . "'";
            }
            $this->default['DSN'] = implode(' ', $parts);
        }

        // Ensure that we always set the database group to 'tests' if
        // we are currently running an automated test suite, so that
        // we don't overwrite live data on accident.
        if (ENVIRONMENT === 'testing') {
            $this->defaultGroup = 'tests';
        }
    }
}
