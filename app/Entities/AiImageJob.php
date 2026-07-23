<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class AiImageJob extends Entity
{
    protected $attributes = [
        'id'             => null,
        'article_id'     => null,
        'provider'       => null,
        'prompt'         => null,
        'status'         => null,
        'generated_path' => null,
        'width'          => null,
        'height'         => null,
        'cost_usd'       => null,
        'requested_by'   => null,
        'created_at'     => null,
        'completed_at'   => null,
    ];

    protected $casts = [
        'id'           => '?integer',
        'article_id'   => '?integer',
        'width'        => '?integer',
        'height'       => '?integer',
        'cost_usd'     => '?float',
        'requested_by' => '?integer',
        'created_at'   => '?datetime',
        'completed_at' => '?datetime',
    ];
}
