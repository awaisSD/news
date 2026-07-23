<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFeaturedMediaForeignKeyToArticles extends Migration
{
    /**
     * Adds the deferred FK for articles.featured_media_id -> media.id now that the
     * media table exists. This is split out from 2024-01-01-000007_CreateArticlesTable
     * to break the articles <-> media <-> ai_image_jobs circular reference.
     */
    public function up(): void
    {
        $this->db->query(
            'ALTER TABLE articles ADD CONSTRAINT fk_articles_featured_media '
            . 'FOREIGN KEY (featured_media_id) REFERENCES media(id) ON DELETE SET NULL'
        );
    }

    public function down(): void
    {
        $this->db->query('ALTER TABLE articles DROP FOREIGN KEY fk_articles_featured_media');
    }
}
