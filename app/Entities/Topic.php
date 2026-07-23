<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Topic extends Entity
{
    protected $attributes = [
        'id'                 => null,
        'title'              => null,
        'brief'              => null,
        'angle_notes'        => null,
        'source_ids'         => null,
        'created_via'        => null,
        'assigned_editor_id' => null,
        'status'             => null,
        'created_at'         => null,
        'updated_at'         => null,
    ];

    protected $casts = [
        'id'                 => '?integer',
        'assigned_editor_id' => '?integer',
        'source_ids'         => '?json-array',
        'created_at'         => '?datetime',
        'updated_at'         => '?datetime',
    ];
}
