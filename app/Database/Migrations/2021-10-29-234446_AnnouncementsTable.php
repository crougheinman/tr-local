<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AnnouncementsTable extends Migration
{
	public function up()
	{
		$forge = \Config\Database::forge();

		$forge->addField([
			'id' =>[
				'type' => 'int',
				'constraint' => 32,
				'null' => false,
				'auto_increment' => true
			],
			'title' =>[
				'type' => 'varchar',
				'constraint' => 255,
				'null' => false,
			],
			'body' =>[
				'type' => 'text',
				'null' => false,
			],
			'slug' =>[
				'type' => 'text',
				'null' => false,
			],
			'date_start' =>[
				'type' => 'datetime',
				'null' => false,
			],
			'date_end' =>[
				'type' => 'datetime',
				'null' => true,
			],
			'type' =>[
				'type' => 'int',
				'constraint' => 1,
				'null' => false,
			],
			'banner' =>[
				'type' => 'int',
				'constraint' => 32,
			],
			'render_as' =>[
				'type' => 'int',
				'constraint' => 2,
				'comment' => 'flag column in which it determines what would be rendered: [1]-HTML, [2]-File(Image/PDF)'
			],
			'created_at' =>[
				'type' => 'datetime',
				'null' => true
			],
			'created_by' =>[
				'type' => 'varchar',
				'constraint' => 32,
				'null' => false
			],
			'modified_at' =>[
				'type' => 'timestamp',
			],
			'modified_by' =>[
				'type' => 'varchar',
				'constraint' => 32,
				'null' => false
			],
			'deleted_at' =>[
				'type' => 'datetime',
				'null' => true
			],
		]);

		$forge->addKey('id',true);
		$forge->createTable('announcements');
	}

	//--------------------------------------------------------------------

	public function down()
	{
		$this->forge->dropTable('announcements');
	}
}
