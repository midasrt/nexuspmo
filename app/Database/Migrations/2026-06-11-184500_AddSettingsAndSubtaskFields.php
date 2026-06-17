<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSettingsAndSubtaskFields extends Migration
{
    public function up()
    {
        // 1. Create settings table
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'key_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'unique'     => true,
            ],
            'value' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ]
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('settings');

        // Insert default settings
        $db = \Config\Database::connect();
        $db->table('settings')->insertBatch([
            [
                'key_name' => 'daily_work_hours',
                'value'    => '8'
            ],
            [
                'key_name' => 'work_days_per_week',
                'value'    => '5'
            ]
        ]);

        // 2. Add columns to project_phase_subtasks
        $fields = [
            'man_days' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => 0.00,
                'null'       => false,
            ],
            'task_hours' => [
                'type'       => 'DECIMAL',
                'constraint' => '10,2',
                'default'    => 0.00,
                'null'       => false,
            ]
        ];
        $this->forge->addColumn('project_phase_subtasks', $fields);
    }

    public function down()
    {
        $this->forge->dropTable('settings');
        $this->forge->dropColumn('project_phase_subtasks', ['man_days', 'task_hours']);
    }
}
