<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMediaTable extends Migration
{
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
            'disk' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => false,
                'default'    => 'local',
            ],
            'path' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => false,
            ],
            'cdn_url' => [
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
            'mime_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => false,
            ],
            'alt_text' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'alt_text_source' => [
                'type'       => 'ENUM',
                'constraint' => ['ai', 'manual'],
                'null'       => true,
            ],
            'caption' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
            ],
            'credit' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'source' => [
                'type'       => 'ENUM',
                'constraint' => ['upload', 'ai_generated', 'stock'],
                'null'       => false,
                'default'    => 'upload',
            ],
            'generated_by' => [
                'type'       => 'ENUM',
                'constraint' => ['human', 'ai', 'stock'],
                'null'       => false,
                'default'    => 'human',
            ],
            'ai_image_job_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'uploaded_by' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('uuid', false, true);
        $this->forge->addKey('ai_image_job_id');
        $this->forge->addKey('uploaded_by');

        $this->forge->addForeignKey('ai_image_job_id', 'ai_image_jobs', 'id', '', 'SET NULL');
        $this->forge->addForeignKey('uploaded_by', 'users', 'id', '', 'SET NULL');

        $this->forge->createTable('media', true, ['ENGINE' => 'InnoDB', 'CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_general_ci']);
    }

    public function down(): void
    {
        $this->forge->dropTable('media', true);
    }
}
