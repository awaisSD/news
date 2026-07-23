<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateRedirectsTable extends Migration
{
    /**
     * old_path is indexed because it is queried on every request by
     * Filters\RedirectFilter.
     */
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'old_path' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => false,
            ],
            'new_path' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => false,
            ],
            'redirect_type' => [
                'type'     => 'SMALLINT',
                'unsigned' => true,
                'null'     => false,
                'default'  => 301,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('old_path');

        $this->forge->createTable('redirects', true, ['ENGINE' => 'InnoDB', 'CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_general_ci']);
    }

    public function down(): void
    {
        $this->forge->dropTable('redirects', true);
    }
}
