<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class User extends Entity
{
    protected $attributes = [
        'id'              => null,
        'uuid'            => null,
        'name'            => null,
        'email'           => null,
        'password_hash'   => null,
        'role'            => null,
        'bio'             => null,
        'credentials'     => null,
        'avatar_media_id' => null,
        'twitter_handle'  => null,
        'linkedin_url'    => null,
        'is_active'       => null,
        'last_login_at'   => null,
        'created_at'      => null,
        'updated_at'      => null,
    ];

    protected $casts = [
        'id'              => '?integer',
        'avatar_media_id' => '?integer',
        'is_active'       => 'boolean',
        'last_login_at'   => '?datetime',
        'created_at'      => '?datetime',
        'updated_at'      => '?datetime',
    ];

    /**
     * Used by author bio pages/templates so they can render an author's
     * credentials line without needing to null-check first.
     */
    public function getDisplayCredentials(): string
    {
        return $this->attributes['credentials'] ?? '';
    }
}
