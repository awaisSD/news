<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAiSettingsTable extends Migration
{
    /**
     * NOTE: the key column is named `setting_key` (not `key`) because `key` is a
     * reserved word in MySQL.
     */
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'setting_key' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => false,
            ],
            'setting_value' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('setting_key', false, true);

        $this->forge->createTable('ai_settings', true, ['ENGINE' => 'InnoDB', 'CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_general_ci']);
    }

    public function down(): void
    {
        $this->forge->dropTable('ai_settings', true);
    }
}
