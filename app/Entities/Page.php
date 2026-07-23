<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Page extends Entity
{
    protected $attributes = [
        'id'                => null,
        'slug'              => null,
        'title'             => null,
        'body_html'         => null,
        'meta_description'  => null,
        'is_published'      => null,
        'updated_by'        => null,
        'created_at'        => null,
        'updated_at'        => null,
    ];

    protected $casts = [
        'id'           => '?integer',
        'is_published' => 'boolean',
        'updated_by'   => '?integer',
        'created_at'   => '?datetime',
        'updated_at'   => '?datetime',
    ];
}
