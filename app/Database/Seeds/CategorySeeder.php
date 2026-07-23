<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        if ($this->db->table('categories')->countAllResults() > 0) {
            return;
        }

        $timestamp = '2024-01-01 00:00:00';

        $categories = [
            ['name' => 'News', 'slug' => 'news', 'sort_order' => 1],
            ['name' => 'World', 'slug' => 'world', 'sort_order' => 2],
            ['name' => 'Technology', 'slug' => 'technology', 'sort_order' => 3],
            ['name' => 'Sports', 'slug' => 'sports', 'sort_order' => 4],
            ['name' => 'Business', 'slug' => 'business', 'sort_order' => 5],
            ['name' => 'Health', 'slug' => 'health', 'sort_order' => 6],
        ];

        $rows = [];
        foreach ($categories as $category) {
            $rows[] = [
                'parent_id'   => null,
                'name'        => $category['name'],
                'slug'        => $category['slug'],
                'description' => null,
                'sort_order'  => $category['sort_order'],
                'is_active'   => 1,
                'created_at'  => $timestamp,
                'updated_at'  => $timestamp,
            ];
        }

        $this->db->table('categories')->insertBatch($rows);
    }
}
