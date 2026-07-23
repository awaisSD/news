<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateArticlesTable extends Migration
{
    /**
     * NOTE (circular FK): featured_media_id -> media.id and ai_generation_job_id -> ai_generation_jobs.id
     * are created here as plain indexed BIGINT UNSIGNED NULL columns WITHOUT foreign key constraints,
     * because articles <-> media <-> ai_image_jobs / ai_generation_jobs form a three-way reference
     * cycle and media/ai_generation_jobs are created in later migrations. The real FK constraints for
     * these two columns are added in migrations 2024-01-01-000018 and 2024-01-01-000019.
     */
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'uuid' => [
                'type'       => 'CHAR',
                'constraint' => 36,
                'null'       => false,
            ],
            'headline' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => false,
            ],
            'slug' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => false,
            ],
            'subheadline' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
            ],
            'excerpt' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
            ],
            'body_html' => [
                'type' => 'MEDIUMTEXT',
                'null' => false,
            ],
            'body_format' => [
                'type'       => 'ENUM',
                'constraint' => ['html', 'markdown'],
                'null'       => false,
                'default'    => 'html',
            ],
            'featured_media_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
                'comment'  => 'References media.id — FK added in migration 2024-01-01-000018 (circular reference)',
            ],
            'primary_category_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => false,
            ],
            'author_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => false,
            ],
            'editor_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'assigned_editor_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['draft', 'in_review', 'changes_requested', 'approved', 'published', 'corrected', 'rejected', 'retracted'],
                'null'       => false,
                'default'    => 'draft',
            ],
            'ai_assisted' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'null'       => false,
                'default'    => 0,
            ],
            'ai_generation_job_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
                'comment'  => 'References ai_generation_jobs.id — FK added in migration 2024-01-01-000019 (circular reference)',
            ],
            'is_breaking' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'null'       => false,
                'default'    => 0,
            ],
            'word_count' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
            'reading_time_minutes' => [
                'type'     => 'SMALLINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'meta_title' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'meta_description' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
            ],
            'canonical_url' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
            ],
            'published_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at_content' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'publish_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'view_count' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => false,
                'default'  => 0,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'deleted_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('uuid', false, true);
        $this->forge->addKey(['primary_category_id', 'slug'], false, true);
        $this->forge->addKey(['status', 'published_at']);
        $this->forge->addKey(['primary_category_id', 'status', 'published_at']);
        $this->forge->addKey('featured_media_id');
        $this->forge->addKey('ai_generation_job_id');
        $this->forge->addKey('author_id');
        $this->forge->addKey('editor_id');
        $this->forge->addKey('assigned_editor_id');

        $this->forge->addForeignKey('primary_category_id', 'categories', 'id');
        $this->forge->addForeignKey('author_id', 'users', 'id');
        $this->forge->addForeignKey('editor_id', 'users', 'id', '', 'SET NULL');
        $this->forge->addForeignKey('assigned_editor_id', 'users', 'id', '', 'SET NULL');

        $this->forge->createTable('articles', true, ['ENGINE' => 'InnoDB', 'CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_general_ci']);

        // Forge does not support FULLTEXT indexes directly — add via raw SQL.
        $this->db->query('ALTER TABLE articles ADD FULLTEXT ft_headline_excerpt (headline, excerpt)');
    }

    public function down(): void
    {
        $this->forge->dropTable('articles', true);
    }
}
