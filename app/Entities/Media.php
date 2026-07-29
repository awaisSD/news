<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;
use Config\Media as MediaConfig;

class Media extends Entity
{
    protected $attributes = [
        'id'              => null,
        'uuid'            => null,
        'disk'            => null,
        'path'            => null,
        'cdn_url'         => null,
        'width'           => null,
        'height'          => null,
        'mime_type'       => null,
        'alt_text'        => null,
        'alt_text_source' => null,
        'caption'         => null,
        'credit'          => null,
        'source'          => null,
        'generated_by'    => null,
        'ai_image_job_id' => null,
        'uploaded_by'     => null,
        'created_at'      => null,
    ];

    protected $casts = [
        'id'              => '?integer',
        'width'           => '?integer',
        'height'          => '?integer',
        'ai_image_job_id' => '?integer',
        'uploaded_by'     => '?integer',
        'created_at'      => '?datetime',
    ];

    /**
     * Returns cdn_url when set; otherwise a CDN-based URL if Media::$cdnBaseUrl
     * is configured; otherwise a plain local URL under /uploads/, which Apache
     * serves directly since Media::$uploadPath now lives inside public/.
     */
    public function getUrl(): string
    {
        if (! empty($this->attributes['cdn_url'])) {
            return $this->attributes['cdn_url'];
        }

        /** @var MediaConfig $config */
        $config = config(MediaConfig::class);
        $path   = $this->attributes['path'] ?? '';

        if (! empty($config->cdnBaseUrl)) {
            return rtrim($config->cdnBaseUrl, '/') . '/' . ltrim($path, '/');
        }

        return base_url('uploads/' . ltrim($path, '/'));
    }
}
