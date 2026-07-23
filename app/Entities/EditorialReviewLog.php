<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class EditorialReviewLog extends Entity
{
    protected $attributes = [
        'id'            => null,
        'article_id'    => null,
        'reviewer_id'   => null,
        'action'        => null,
        'notes'         => null,
        'diff_snapshot' => null,
        'created_at'    => null,
    ];

    protected $casts = [
        'id'            => '?integer',
        'article_id'    => '?integer',
        'reviewer_id'   => '?integer',
        'diff_snapshot' => '?json-array',
        'created_at'    => '?datetime',
    ];
}
