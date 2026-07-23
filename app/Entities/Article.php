<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Article extends Entity
{
    protected $attributes = [
        'id'                    => null,
        'uuid'                  => null,
        'headline'              => null,
        'slug'                  => null,
        'subheadline'           => null,
        'excerpt'               => null,
        'body_html'             => null,
        'body_format'           => null,
        'featured_media_id'     => null,
        'primary_category_id'   => null,
        'author_id'             => null,
        'editor_id'             => null,
        'assigned_editor_id'    => null,
        'status'                => null,
        'ai_assisted'           => null,
        'ai_generation_job_id'  => null,
        'is_breaking'           => null,
        'word_count'            => null,
        'reading_time_minutes'  => null,
        'meta_title'            => null,
        'meta_description'      => null,
        'canonical_url'         => null,
        'published_at'          => null,
        'updated_at_content'    => null,
        'publish_at'            => null,
        'view_count'            => null,
        'created_at'            => null,
        'updated_at'            => null,
        'deleted_at'            => null,
    ];

    protected $casts = [
        'id'                   => '?integer',
        'featured_media_id'    => '?integer',
        'primary_category_id'  => '?integer',
        'author_id'            => '?integer',
        'editor_id'            => '?integer',
        'assigned_editor_id'   => '?integer',
        'ai_assisted'          => 'boolean',
        'ai_generation_job_id' => '?integer',
        'is_breaking'          => 'boolean',
        'word_count'           => '?integer',
        'reading_time_minutes' => '?integer',
        'published_at'         => '?datetime',
        'updated_at_content'   => '?datetime',
        'publish_at'           => '?datetime',
        'view_count'           => 'integer',
        'created_at'           => '?datetime',
        'updated_at'           => '?datetime',
        'deleted_at'           => '?datetime',
    ];
}
