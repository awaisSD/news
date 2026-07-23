<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class TopicSource extends Entity
{
    protected $attributes = [
        'id'                   => null,
        'topic_id'             => null,
        'source_name'          => null,
        'source_url'           => null,
        'title'                => null,
        'summary'              => null,
        'published_at_source'  => null,
        'fetched_at'           => null,
    ];

    protected $casts = [
        'id'                  => '?integer',
        'topic_id'            => '?integer',
        'published_at_source' => '?datetime',
        'fetched_at'          => '?datetime',
    ];
}
