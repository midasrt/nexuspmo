<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddSequenceToProjectPhases extends Migration
{
    public function up()
    {
        $fields = [
            'sequence' => [
                'type'       => 'INT',
                'unsigned'   => true,
                'default'    => 0,
            ]
        ];
        $this->forge->addColumn('project_phases', $fields);
    }

    public function down()
    {
        $this->forge->dropColumn('project_phases', 'sequence');
    }
}
