<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateArticleTaxonomyPivotsTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'article_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => false,
            ],
            'category_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => false,
            ],
            'is_primary' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'null'       => false,
                'default'    => 0,
            ],
        ]);

        $this->forge->addKey(['article_id', 'category_id'], true);
        $this->forge->addKey('category_id');

        $this->forge->addForeignKey('article_id', 'articles', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('category_id', 'categories', 'id', '', 'CASCADE');

        $this->forge->createTable('article_categories', true, ['ENGINE' => 'InnoDB', 'CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_general_ci']);

        $this->forge->addField([
            'article_id' => [
                'type'     => 'BIGINT',
                'unsigned' => true,
                'null'     => false,
            ],
            'tag_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => false,
            ],
        ]);

        $this->forge->addKey(['article_id', 'tag_id'], true);

        $this->forge->addForeignKey('article_id', 'articles', 'id', '', 'CASCADE');
        $this->forge->addForeignKey('tag_id', 'tags', 'id', '', 'CASCADE');

        $this->forge->createTable('article_tags', true, ['ENGINE' => 'InnoDB', 'CHARSET' => 'utf8mb4', 'COLLATE' => 'utf8mb4_general_ci']);
    }

    public function down(): void
    {
        $this->forge->dropTable('article_tags', true);
        $this->forge->dropTable('article_categories', true);
    }
}
