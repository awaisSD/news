<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateTopicSourcesTable extends Migration
{
    /**
     * Metadata only — never store full scraped article bodies here; this table
     * exists so topic discovery never becomes copy-and-spin of source content.
     */
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'source_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => false,
            ],
            'source_url' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => false,
            ],
            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => false,
            ],
            'summary' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'published_at_source' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'fetched_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        $this->forge->addKey('id', true);

        $this->forge->createTable('topic_sources', true, ['ENGINE' => 'InnoDB', 'CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_general_ci']);
    }

    public function down(): void
    {
        $this->forge->dropTable('topic_sources', true);
    }
}
