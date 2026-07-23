<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateArticleCorrectionsTable extends Migration
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
            'corrected_by' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => false,
            ],
            'correction_note' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'severity' => [
                'type'       => 'ENUM',
                'constraint' => ['minor', 'substantial'],
                'null'       => false,
                'default'    => 'minor',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('article_id');
        $this->forge->addKey('corrected_by');

        $this->forge->addForeignKey('article_id', 'articles', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('corrected_by', 'users', 'id');

        $this->forge->createTable('article_corrections', true, ['ENGINE' => 'InnoDB', 'CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_general_ci']);
    }

    public function down(): void
    {
        $this->forge->dropTable('article_corrections', true);
    }
}
