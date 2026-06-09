<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateOrgStructureTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('name');
        $this->forge->createTable('org_structures');

        // Populate existing unique departments from resources table
        $db = \Config\Database::connect();
        
        // Check if resources table exists to avoid errors on fresh setup
        if ($db->tableExists('resources')) {
            $resources = $db->table('resources')->select('department')->distinct()->get()->getResultArray();
            $inserted = [];
            foreach ($resources as $r) {
                $dept = trim($r['department']);
                if (!empty($dept) && !in_array($dept, $inserted)) {
                    $db->table('org_structures')->insert([
                        'name'        => $dept,
                        'description' => 'Automatically imported from existing resource records.'
                    ]);
                    $inserted[] = $dept;
                }
            }
        }
    }

    public function down()
    {
        $this->forge->dropTable('org_structures', true);
    }
}
