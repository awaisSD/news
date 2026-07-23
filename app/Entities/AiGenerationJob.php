<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class AiGenerationJob extends Entity
{
    protected $attributes = [
        'id'                => null,
        'topic_id'          => null,
        'article_id'        => null,
        'job_type'          => null,
        'provider'          => null,
        'model'             => null,
        'status'            => null,
        'prompt_payload'    => null,
        'response_metadata' => null,
        'cost_usd'          => null,
        'requested_by'      => null,
        'error_message'     => null,
        'locked_by'         => null,
        'locked_at'         => null,
        'created_at'        => null,
        'started_at'        => null,
        'completed_at'      => null,
    ];

    protected $casts = [
        'id'                => '?integer',
        'topic_id'          => '?integer',
        'article_id'        => '?integer',
        'prompt_payload'    => '?json-array',
        'response_metadata' => '?json-array',
        'cost_usd'          => '?float',
        'requested_by'      => '?integer',
        'locked_at'         => '?datetime',
        'created_at'        => '?datetime',
        'started_at'        => '?datetime',
        'completed_at'      => '?datetime',
    ];
}
