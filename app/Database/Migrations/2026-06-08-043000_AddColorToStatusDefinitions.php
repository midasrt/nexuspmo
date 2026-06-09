<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddColorToStatusDefinitions extends Migration
{
    public function up()
    {
        $fields = [
            'color' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
                'default'    => '#6b7280',
            ]
        ];
        $this->forge->addColumn('status_definitions', $fields);

        // Update default colors for existing seeded statuses
        $db = \Config\Database::connect();
        
        if ($db->tableExists('status_definitions')) {
            $colors = [
                'on-track' => '#10b981',
                'at-risk'  => '#f59e0b',
                'blocked'  => '#ef4444',
                'delayed'  => '#f43f5e',
                'backlog'  => '#6b7280',
            ];

            foreach ($colors as $slug => $hex) {
                $db->table('status_definitions')->where('status', $slug)->update(['color' => $hex]);
            }
        }
    }

    public function down()
    {
        $this->forge->dropColumn('status_definitions', 'color');
    }
}
