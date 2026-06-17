<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddResourceIdToPhasesAndSubtasks extends Migration
{
    public function up()
    {
        $fields = [
            'resource_id' => [
                'type'       => 'INT',
                'unsigned'   => true,
                'null'       => true,
                'after'      => 'status',
            ]
        ];
        $this->forge->addColumn('project_phases', $fields);
        $this->forge->addColumn('project_phase_subtasks', $fields);

        // Add foreign keys
        $this->db->query("ALTER TABLE project_phases ADD CONSTRAINT fk_phases_resource FOREIGN KEY (resource_id) REFERENCES resources(id) ON DELETE SET NULL ON UPDATE CASCADE");
        $this->db->query("ALTER TABLE project_phase_subtasks ADD CONSTRAINT fk_subtasks_resource FOREIGN KEY (resource_id) REFERENCES resources(id) ON DELETE SET NULL ON UPDATE CASCADE");
    }

    public function down()
    {
        try {
            $this->db->query("ALTER TABLE project_phase_subtasks DROP FOREIGN KEY fk_subtasks_resource");
        } catch (\Exception $e) {}
        try {
            $this->db->query("ALTER TABLE project_phases DROP FOREIGN KEY fk_phases_resource");
        } catch (\Exception $e) {}

        $this->forge->dropColumn('project_phases', 'resource_id');
        $this->forge->dropColumn('project_phase_subtasks', 'resource_id');
    }
}

