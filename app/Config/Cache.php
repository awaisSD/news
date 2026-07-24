<?php

namespace Config;

use CodeIgniter\Cache\Handlers\ApcuHandler;
use CodeIgniter\Cache\Handlers\DummyHandler;
use CodeIgniter\Cache\Handlers\FileHandler;
use CodeIgniter\Cache\Handlers\MemcachedHandler;
use CodeIgniter\Cache\Handlers\PredisHandler;
use CodeIgniter\Cache\Handlers\RedisHandler;
use CodeIgniter\Cache\Handlers\WincacheHandler;
use CodeIgniter\Config\BaseConfig;

/**
 * Full-page/fragment caching for anonymous article and category requests
 * is the single biggest TTFB win on a plain Apache+PHP+MySQL box — see
 * Filters\CacheControlFilter and the cache() calls in Front controllers.
 *
 * Handler starts as 'file' (single server). The moment a second app
 * server is added behind a load balancer, switch $handler to 'redis'
 * and point it at one shared Redis instance — every call site already
 * goes through Services::cache(), so this is a config-only change.
 */
class Cache extends BaseConfig
{
    public string $handler = 'file';

    public string $backupHandler = 'dummy';

    public string $prefix = 'newsweb_';

    public int $ttl = 60;

    /**
     * Required for PSR-6 compliance — do not remove even though this app
     * doesn't set custom cache tags today.
     */
    public string $reservedCharacters = '{}()/\@:';

    public array $file = [
        'storePath' => WRITEPATH . 'cache/',
        'mode'      => 0640,
    ];

    public array $memcached = [
        'host'   => '127.0.0.1',
        'port'   => 11211,
        'weight' => 1,
        'raw'    => false,
    ];

    public array $redis = [
        'host'       => '127.0.0.1',
        'password'   => null,
        'port'       => 6379,
        'timeout'    => 0,
        'async'      => false,
        'persistent' => false,
        'database'   => 0,
    ];

    /**
     * Read by CacheFactory::getHandler() — omitting this entirely (as an
     * earlier version of this file did) throws "Undefined property
     * Config\Cache::$validHandlers" the moment any cache() call runs.
     *
     * @var array<string, class-string<\CodeIgniter\Cache\CacheInterface>>
     */
    public array $validHandlers = [
        'apcu'      => ApcuHandler::class,
        'dummy'     => DummyHandler::class,
        'file'      => FileHandler::class,
        'memcached' => MemcachedHandler::class,
        'predis'    => PredisHandler::class,
        'redis'     => RedisHandler::class,
        'wincache'  => WincacheHandler::class,
    ];

    /**
     * @var bool|list<string>
     */
    public $cacheQueryString = false;

    /**
     * @var list<int>
     */
    public array $cacheStatusCodes = [];

    /**
     * TTLs (seconds) for specific cached artifacts — referenced by name
     * from Libraries/Seo builders and Front controllers, so tuning
     * freshness doesn't require touching business logic.
     */
    public array $ttls = [
        'article_page'   => 60,
        'category_page'  => 60,
        'author_page'    => 120,
        'rss_feed'       => 300,
        'sitemap'        => 1800,
        // Rolling 2-day Google News sitemap needs near-real-time freshness.
        'news_sitemap'   => 90,
        'category_tree'  => 3600,
    ];

    public function __construct()
    {
        parent::__construct();

        $this->handler       = env('cache.handler', $this->handler);
        $this->redis['host'] = env('redis.host', $this->redis['host']);
        $this->redis['port'] = (int) env('redis.port', $this->redis['port']);
    }
}
