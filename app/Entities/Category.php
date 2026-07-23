<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Category extends Entity
{
    protected $attributes = [
        'id'          => null,
        'parent_id'   => null,
        'name'        => null,
        'slug'        => null,
        'description' => null,
        'sort_order'  => null,
        'is_active'   => null,
        'created_at'  => null,
        'updated_at'  => null,
    ];

    protected $casts = [
        'id'         => '?integer',
        'parent_id'  => '?integer',
        'sort_order' => 'integer',
        'is_active'  => 'boolean',
        'created_at' => '?datetime',
        'updated_at' => '?datetime',
    ];

    /**
     * Populated only when hydrated via CategoryModel::getTree(); not a
     * database column. Holds child Category entities nested one level deep.
     *
     * @var Category[]
     */
    protected $children = [];

    public function setChildren(array $children): static
    {
        $this->children = $children;

        return $this;
    }

    /**
     * @return Category[]
     */
    public function getChildren(): array
    {
        return $this->children;
    }
}
