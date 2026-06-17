<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateActivityLog extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
            'user_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'user_role' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'action' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                // CREATE, UPDATE, DELETE
            ],
            'module' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                // projects, resources, squads, users, settings, etc.
            ],
            'target_id' => [
                'type'     => 'INT',
                'unsigned' => true,
                'null'     => true,
            ],
            'target_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'ip_address' => [
                'type'       => 'VARCHAR',
                'constraint' => 45,
                'null'       => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey('user_id');
        $this->forge->addKey('module');
        $this->forge->addKey('created_at');
        $this->forge->createTable('user_activity_log');
    }

    public function down()
    {
        $this->forge->dropTable('user_activity_log');
    }
}
