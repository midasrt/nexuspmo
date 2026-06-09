<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateProjectPhaseSubtasks extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'phase_id' => [
                'type'           => 'INT',
                'unsigned'       => true,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'start' => [
                'type' => 'DATE',
            ],
            'end' => [
                'type' => 'DATE',
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
            ],
            'sequence' => [
                'type'       => 'INT',
                'unsigned'   => true,
                'default'    => 0,
            ]
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('phase_id', 'project_phases', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('project_phase_subtasks');
    }

    public function down()
    {
        $this->forge->dropTable('project_phase_subtasks');
    }
}
