<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Tag extends Entity
{
    protected $attributes = [
        'id'   => null,
        'name' => null,
        'slug' => null,
    ];

    protected $casts = [
        'id' => '?integer',
    ];
}
