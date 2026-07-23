<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class ArticleCorrection extends Entity
{
    protected $attributes = [
        'id'              => null,
        'article_id'      => null,
        'corrected_by'    => null,
        'correction_note' => null,
        'severity'        => null,
        'created_at'      => null,
    ];

    protected $casts = [
        'id'           => '?integer',
        'article_id'   => '?integer',
        'corrected_by' => '?integer',
        'created_at'   => '?datetime',
    ];
}
