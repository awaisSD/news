<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAiGenerationJobsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'topic_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'article_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'job_type' => [
                'type'       => 'ENUM',
                'constraint' => ['article', 'style_pass'],
                'null'       => false,
                'default'    => 'article',
            ],
            'provider' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => false,
            ],
            'model' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => false,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['pending', 'processing', 'completed', 'failed', 'cancelled', 'blocked_by_cap'],
                'null'       => false,
                'default'    => 'pending',
            ],
            'prompt_payload' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'response_metadata' => [
                'type' => 'JSON',
                'null' => true,
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
            'error_message' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'locked_by' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'locked_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'started_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'completed_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['status', 'created_at']);
        $this->forge->addKey('topic_id');
        $this->forge->addKey('article_id');
        $this->forge->addKey('requested_by');

        $this->forge->addForeignKey('topic_id', 'topics', 'id', '', 'SET NULL');
        $this->forge->addForeignKey('article_id', 'articles', 'id', '', 'SET NULL');
        $this->forge->addForeignKey('requested_by', 'users', 'id', '', 'SET NULL');

        $this->forge->createTable('ai_generation_jobs', true, ['ENGINE' => 'InnoDB', 'CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_general_ci']);
    }

    public function down(): void
    {
        $this->forge->dropTable('ai_generation_jobs', true);
    }
}
