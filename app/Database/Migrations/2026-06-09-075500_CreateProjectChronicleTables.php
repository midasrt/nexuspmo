<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateProjectChronicleTables extends Migration
{
    public function up()
    {
        // 1. project_narrative_history
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'project_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'date' => [
                'type' => 'DATE',
            ],
            'sentence' => [
                'type' => 'TEXT',
            ],
            'created_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('project_id');
        $this->forge->createTable('project_narrative_history');

        // 2. project_detailed_history
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'project_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'activity' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'old_state' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'new_state' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'date' => [
                'type' => 'DATE',
            ],
            'created_at' => [
                'type'    => 'DATETIME',
                'null'    => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('project_id');
        $this->forge->createTable('project_detailed_history');
    }

    public function down()
    {
        $this->forge->dropTable('project_narrative_history', true);
        $this->forge->dropTable('project_detailed_history', true);
    }
}
