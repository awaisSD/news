<?php

namespace Config;

use CodeIgniter\Config\AutoloadConfig;

class Autoload extends AutoloadConfig
{
    /**
     * PSR-4 namespace mapping beyond what Composer's autoloader already
     * provides (App\ -> app/ is declared in composer.json).
     *
     * @var array<string, string>
     */
    public $psr4 = [
        APP_NAMESPACE => APPPATH,
    ];

    /**
     * Classmap kept empty; CodeIgniter's own classmap is merged in
     * automatically by the framework's core Autoloader.
     *
     * @var array<string, string>
     */
    public $classmap = [];

    /**
     * Non-class files (e.g. our custom helpers) to preload.
     *
     * @var array<int, string>
     */
    public $files = [];

    /**
     * Helpers autoloaded on every request AND every CLI command run —
     * this matters because Libraries/Commands (e.g. ArticleGenerationService,
     * ProcessAiQueue) call generate_uuid4()/render_jsonld()/media_url() etc.
     * without extending BaseController, so they can't rely on its
     * per-controller $helpers preload list.
     *
     * @var array<int, string>
     */
    public $helpers = ['uuid', 'seo', 'image'];
}
