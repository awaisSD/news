<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class AuditLog extends Entity
{
    protected $attributes = [
        'id'           => null,
        'user_id'      => null,
        'action'       => null,
        'subject_type' => null,
        'subject_id'   => null,
        'before_json'  => null,
        'after_json'   => null,
        'ip_address'   => null,
        'created_at'   => null,
    ];

    protected $casts = [
        'id'          => '?integer',
        'user_id'     => '?integer',
        'subject_id'  => '?integer',
        'before_json' => '?json-array',
        'after_json'  => '?json-array',
        'created_at'  => '?datetime',
    ];
}
