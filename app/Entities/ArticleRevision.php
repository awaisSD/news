<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class ArticleRevision extends Entity
{
    protected $attributes = [
        'id'                 => null,
        'article_id'         => null,
        'editor_id'          => null,
        'status_at_revision' => null,
        'headline'           => null,
        'body_html'          => null,
        'is_substantive'     => null,
        'correction_note'    => null,
        'diff_summary'       => null,
        'created_at'         => null,
    ];

    protected $casts = [
        'id'             => '?integer',
        'article_id'     => '?integer',
        'editor_id'      => '?integer',
        'is_substantive' => 'boolean',
        'created_at'     => '?datetime',
    ];
}
