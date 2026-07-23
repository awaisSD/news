<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Image/media delivery. The CDN sits in front of media.cdn_url values only,
 * decoupled from the PHP/MySQL origin — see the Performance Strategy section
 * of the project plan for why this is recommended even on a traditional
 * Apache+PHP+MySQL stack.
 */
class Media extends BaseConfig
{
    public string $cdnBaseUrl = '';

    /**
     * 'local' stores under writable/uploads and serves through the CDN
     * as a pull-zone/origin pull; 'cdn' assumes direct upload to object
     * storage. Read by Libraries\AI\ImageGenerationService and the
     * admin MediaController.
     */
    public string $disk = 'local';

    public string $uploadPath = WRITEPATH . 'uploads/';

    /**
     * Responsive variants generated at ingest time (never on-request) —
     * widths in pixels. Stored alongside the original in a media_variants
     * concept the Media model exposes.
     *
     * @var array<int, int>
     */
    public array $variantWidths = [400, 800, 1200, 1600];

    public function __construct()
    {
        parent::__construct();

        $this->cdnBaseUrl = env('media.cdnBaseUrl', $this->cdnBaseUrl);
        $this->disk       = env('media.disk', $this->disk);
    }
}
