<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateArticleRevisionsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'article_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => false,
            ],
            'editor_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => false,
            ],
            'status_at_revision' => [
                'type'       => 'ENUM',
                'constraint' => ['draft', 'in_review', 'changes_requested', 'approved', 'published', 'corrected', 'rejected', 'retracted'],
                'null'       => false,
            ],
            'headline' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => false,
            ],
            'body_html' => [
                'type' => 'MEDIUMTEXT',
                'null' => false,
            ],
            'is_substantive' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'null'       => false,
                'default'    => 0,
            ],
            'correction_note' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'diff_summary' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['article_id', 'created_at']);
        $this->forge->addKey('editor_id');

        $this->forge->addForeignKey('article_id', 'articles', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('editor_id', 'users', 'id');

        $this->forge->createTable('article_revisions', true, ['ENGINE' => 'InnoDB', 'CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_general_ci']);
    }

    public function down(): void
    {
        $this->forge->dropTable('article_revisions', true);
    }
}
