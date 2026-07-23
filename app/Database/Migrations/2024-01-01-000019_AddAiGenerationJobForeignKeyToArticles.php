<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddAiGenerationJobForeignKeyToArticles extends Migration
{
    /**
     * Adds the deferred FK for articles.ai_generation_job_id -> ai_generation_jobs.id
     * now that the ai_generation_jobs table exists. This is split out from
     * 2024-01-01-000007_CreateArticlesTable to break the articles <-> ai_generation_jobs
     * (via ai_image_jobs/media) circular reference.
     */
    public function up(): void
    {
        $this->db->query(
            'ALTER TABLE articles ADD CONSTRAINT fk_articles_ai_generation_job '
            . 'FOREIGN KEY (ai_generation_job_id) REFERENCES ai_generation_jobs(id) ON DELETE SET NULL'
        );
    }

    public function down(): void
    {
        $this->db->query('ALTER TABLE articles DROP FOREIGN KEY fk_articles_ai_generation_job');
    }
}
