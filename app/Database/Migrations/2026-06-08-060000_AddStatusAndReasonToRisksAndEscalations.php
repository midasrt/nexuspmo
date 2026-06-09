<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddStatusAndReasonToRisksAndEscalations extends Migration
{
    public function up()
    {
        // Add status and reason to project_escalations
        $this->forge->addColumn('project_escalations', [
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'active',
            ],
            'reason' => [
                'type' => 'TEXT',
                'null' => true,
            ]
        ]);

        // Add status and reason to project_risks
        $this->forge->addColumn('project_risks', [
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'active',
            ],
            'reason' => [
                'type' => 'TEXT',
                'null' => true,
            ]
        ]);

        // Add resolved_date to project_action_items
        $this->forge->addColumn('project_action_items', [
            'resolved_date' => [
                'type' => 'DATE',
                'null' => true,
            ]
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('project_escalations', ['status', 'reason']);
        $this->forge->dropColumn('project_risks', ['status', 'reason']);
        $this->forge->dropColumn('project_action_items', 'resolved_date');
    }
}
