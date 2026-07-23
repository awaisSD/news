<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEditorialReviewLogTable extends Migration
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
            'reviewer_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => false,
            ],
            'action' => [
                'type'       => 'ENUM',
                'constraint' => ['submitted', 'edited', 'style_pass_applied', 'style_pass_rejected', 'changes_requested', 'rejected', 'approved', 'published', 'correction_made', 'cap_block'],
                'null'       => false,
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'diff_snapshot' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['article_id', 'created_at']);
        $this->forge->addKey('reviewer_id');

        $this->forge->addForeignKey('article_id', 'articles', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('reviewer_id', 'users', 'id');

        $this->forge->createTable('editorial_review_log', true, ['ENGINE' => 'InnoDB', 'CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_general_ci']);
    }

    public function down(): void
    {
        $this->forge->dropTable('editorial_review_log', true);
    }
}
