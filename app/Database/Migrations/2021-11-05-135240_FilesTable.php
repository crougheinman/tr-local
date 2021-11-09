<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class FilesTable extends Migration
{

    public function up()
    {
		$forge = \Config\Database::forge();
        $forge->addField(
            [
                'id' => [
                    'type' => 'int',
                    'constraint' => 18,
                    'auto_increment' => true
                ],
                'file_name' => [
                    'type' => 'varchar',
                    'constraint' => 255,
                    'comment' => 'Only virtual file name on the app. Not physical file name.'
                ],
                'file_type' => [
                    'type' => 'varchar',
                    'constraint' => 255,
                ],
                'file_ext' => [
                    'type' => 'varchar',
                    'constraint' => 4,
                ],
                'file_size' => [
                    'type' => 'int',
                    'constraint' => 18,
                ],
                'entity_id' => [
                    'type' => 'int',
                    'constraint' => 18,
                ],
                'folder' => [
                    'type' => 'text',
                ],
                'modified_by' => [
                    'type' => 'varchar',
                    'constraint' => 32,
                    'null' => false
                ],
                'created_by' => [
                    'type' => 'varchar',
                    'constraint' => 32,
                    'null' => false
                ],
                'created_at' => [
                    'type' => 'datetime',
                    'null' => true
                ],
                'modified_at' => [
                    'type' => 'timestamp',
                ],
                'deleted_at' => [
                    'type' => 'datetime',
                    'null' => true
                ]
            ]
        );

		$forge->addKey('id',true);
		$forge->createTable('files');
    }

    public function down()
    {
        $this->forge->dropTable('files');
    }
}
