<?php

namespace Config;

use CodeIgniter\Database\Config;

/**
 * Database connection groups.
 *
 * 'default' is the read/write connection used everywhere by default.
 * 'readonly' is not wired into any Model yet — add it only once a real
 * MySQL read replica exists, then point read-heavy Models (e.g. the
 * public-facing ArticleModel list/category queries) at it explicitly.
 * This keeps the read-replica migration a config + call-site change,
 * not an architecture change.
 */
class Database extends Config
{
    /**
     * The directory that holds the Migrations and Seeds directories.
     * Read directly by the migration runner — omitting this throws
     * "Undefined property Config\Database::$filesPath" the moment
     * `php spark migrate` looks for migration files.
     */
    public string $filesPath = APPPATH . 'Database' . DIRECTORY_SEPARATOR;

    public string $defaultGroup = 'default';

    private const DATE_FORMAT = [
        'date'     => 'Y-m-d',
        'datetime' => 'Y-m-d H:i:s',
        'time'     => 'H:i:s',
    ];

    public array $default = [
        'DSN'          => '',
        'hostname'     => 'localhost',
        'username'     => '',
        'password'     => '',
        'database'     => '',
        'DBDriver'     => 'MySQLi',
        'DBPrefix'     => '',
        'pConnect'     => false,
        'DBDebug'      => true,
        'charset'      => 'utf8mb4',
        'DBCollat'     => 'utf8mb4_general_ci',
        'swapPre'      => '',
        'encrypt'      => false,
        'compress'     => false,
        'strictOn'     => true,
        'failover'     => [],
        'port'         => 3306,
        'numberNative' => false,
        'foundRows'    => false,
        'dateFormat'   => self::DATE_FORMAT,
    ];

    public array $readonly = [
        'DSN'          => '',
        'hostname'     => 'localhost',
        'username'     => '',
        'password'     => '',
        'database'     => '',
        'DBDriver'     => 'MySQLi',
        'DBPrefix'     => '',
        'pConnect'     => false,
        'DBDebug'      => true,
        'charset'      => 'utf8mb4',
        'DBCollat'     => 'utf8mb4_general_ci',
        'swapPre'      => '',
        'encrypt'      => false,
        'compress'     => false,
        'strictOn'     => false,
        'failover'     => [],
        'port'         => 3306,
        'numberNative' => false,
        'foundRows'    => false,
        'dateFormat'   => self::DATE_FORMAT,
    ];

    public array $tests = [
        'DSN'         => '',
        'hostname'    => '127.0.0.1',
        'username'    => '',
        'password'    => '',
        'database'    => ':memory:',
        'DBDriver'    => 'SQLite3',
        'DBPrefix'    => 'db_',
        'pConnect'    => false,
        'DBDebug'     => true,
        'charset'     => 'utf8mb4',
        'DBCollat'    => 'utf8mb4_general_ci',
        'swapPre'     => '',
        'encrypt'     => false,
        'compress'    => false,
        'strictOn'    => false,
        'failover'    => [],
        'foreignKeys' => true,
        'busyTimeout' => 1000,
        'synchronous' => null,
        'dateFormat'  => self::DATE_FORMAT,
    ];

    public function __construct()
    {
        parent::__construct();

        // Match .env-driven defaults for the connection groups above.
        $this->default['hostname'] = env('database.default.hostname', $this->default['hostname']);
        $this->default['database'] = env('database.default.database', $this->default['database']);
        $this->default['username'] = env('database.default.username', $this->default['username']);
        $this->default['password'] = env('database.default.password', $this->default['password']);
        $this->default['DBDriver'] = env('database.default.DBDriver', $this->default['DBDriver']);
        $this->default['port']     = (int) env('database.default.port', $this->default['port']);

        // Never let an automated test run touch the real database.
        if (ENVIRONMENT === 'testing') {
            $this->defaultGroup = 'tests';
        }
    }
}
