<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Redirect extends Entity
{
    protected $attributes = [
        'id'            => null,
        'old_path'      => null,
        'new_path'      => null,
        'redirect_type' => null,
        'created_at'    => null,
    ];

    protected $casts = [
        'id'            => '?integer',
        'redirect_type' => 'integer',
        'created_at'    => '?datetime',
    ];
}
