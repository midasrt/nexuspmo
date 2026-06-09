<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class RemoveJoinedDateFromResources extends Migration
{
    public function up()
    {
        $this->forge->dropColumn('resources', 'joinedDate');
    }

    public function down()
    {
        $this->forge->addColumn('resources', [
            'joinedDate' => [
                'type' => 'DATE',
                'null' => true,
            ]
        ]);
    }
}
