<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTopicsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => false,
            ],
            'brief' => [
                'type' => 'TEXT',
                'null' => false,
            ],
            'angle_notes' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'source_ids' => [
                'type' => 'JSON',
                'null' => true,
            ],
            'created_via' => [
                'type'       => 'ENUM',
                'constraint' => ['rss', 'trending', 'manual'],
                'null'       => false,
                'default'    => 'manual',
            ],
            'assigned_editor_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => true,
            ],
            'status' => [
                'type'       => 'ENUM',
                'constraint' => ['new', 'assigned', 'in_generation', 'used', 'archived'],
                'null'       => false,
                'default'    => 'new',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('assigned_editor_id');
        $this->forge->addKey('status');

        $this->forge->addForeignKey('assigned_editor_id', 'users', 'id', '', 'SET NULL');

        $this->forge->createTable('topics', true, ['ENGINE' => 'InnoDB', 'CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_general_ci']);
    }

    public function down(): void
    {
        $this->forge->dropTable('topics', true);
    }
}
