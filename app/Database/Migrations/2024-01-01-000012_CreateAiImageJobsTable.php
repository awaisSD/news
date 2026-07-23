<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAiImageJobsTable extends Migration
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
            'provider' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => false,
            ],
            'prompt' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'processing', 'completed', 'failed', 'cancelled'],
                'null'       => false,
                'default'    => 'pending',
            ],
            'generated_path' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
            ],
            'width' => [
                'type'     => 'SMALLINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'height' => [
                'type'     => 'SMALLINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'cost_usd' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,4',
                'null'       => true,
            ],
            'requested_by' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'completed_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['status', 'created_at']);
        $this->forge->addKey('article_id');
        $this->forge->addKey('requested_by');

        $this->forge->addForeignKey('article_id', 'articles', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('requested_by', 'users', 'id', '', 'SET NULL');

        $this->forge->createTable('ai_image_jobs', true, ['ENGINE' => 'InnoDB', 'CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_general_ci']);
    }

    public function down(): void
    {
        $this->forge->dropTable('ai_image_jobs', true);
    }
}
