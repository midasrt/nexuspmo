<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePortfolioTables extends Migration
{
    public function up()
    {
        // 1. SQUADS
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
            'mission' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'lead' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('name');
        $this->forge->createTable('squads');

        // 2. RESOURCES
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
            'department' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'role' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
            ],
            'utilization' => [
                'type'       => 'INT',
                'default'    => 0,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
            ],
            'email' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
            ],
            'location' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'joinedDate' => [
                'type' => 'DATE',
            ],
            'skills' => [
                'type' => 'TEXT', // comma-separated values
            ],
            'manager' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('name');
        $this->forge->createTable('resources');

        // 3. SQUAD_MEMBERS (join table for squads and resources)
        $this->forge->addField([
            'squad_id' => [
                'type'       => 'INT',
                'unsigned'   => true,
            ],
            'resource_id' => [
                'type'       => 'INT',
                'unsigned'   => true,
            ],
        ]);
        $this->forge->addKey(['squad_id', 'resource_id'], true);
        $this->forge->createTable('squad_members');

        // 4. PROJECTS
        $this->forge->addField([
            'id' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'code' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'owner' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'squad' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => true,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
            ],
            'health' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'startDate' => [
                'type' => 'DATE',
            ],
            'endDate' => [
                'type' => 'DATE',
            ],
            'progress' => [
                'type'       => 'INT',
                'default'    => 0,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('code');
        $this->forge->createTable('projects');

        // 5. RESOURCE_PROJECTS (join table mapping resource IDs and project IDs)
        $this->forge->addField([
            'resource_id' => [
                'type'     => 'INT',
                'unsigned' => true,
            ],
            'project_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
        ]);
        $this->forge->addKey(['resource_id', 'project_id'], true);
        $this->forge->createTable('resource_projects');

        // 6. PROJECT_PHASES
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
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('project_phases');

        // 7. PROJECT_DEPENDENCIES
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
            'dep_project_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'dep_project_name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'type' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('project_dependencies');

        // 8. PROJECT_ACTION_ITEMS
        $this->forge->addField([
            'id' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'project_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'owner' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'due' => [
                'type' => 'DATE',
            ],
            'done' => [
                'type'       => 'TINYINT',
                'constraint' => 1,
                'default'    => 0,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('project_action_items');

        // 9. PROJECT_STATUS_HISTORY
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
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
            ],
            'note' => [
                'type' => 'TEXT',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('project_status_history');

        // 10. PROJECT_ESCALATIONS
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
            'level' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
            ],
            'note' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'to_recipient' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('project_escalations');

        // 11. PROJECT_RISKS
        $this->forge->addField([
            'id' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'project_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'severity' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
            ],
            'type' => [
                'type'       => 'VARCHAR',
                'constraint' => 10,
            ],
            'mitigation' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'owner' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('project_risks');

        // 12. PROJECT_DOCUMENTS
        $this->forge->addField([
            'id' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'project_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'size' => [
                'type' => 'INT',
            ],
            'uploaded_at' => [
                'type' => 'DATE',
            ],
            'file_path' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('project_documents');

        // 13. STATUS_DEFINITIONS
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
            ],
            'label' => [
                'type'       => 'VARCHAR',
                'constraint' => 100,
            ],
            'criteria' => [
                'type' => 'TEXT',
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('status');
        $this->forge->createTable('status_definitions');
    }

    public function down()
    {
        $this->forge->dropTable('status_definitions', true);
        $this->forge->dropTable('project_documents', true);
        $this->forge->dropTable('project_risks', true);
        $this->forge->dropTable('project_escalations', true);
        $this->forge->dropTable('project_status_history', true);
        $this->forge->dropTable('project_action_items', true);
        $this->forge->dropTable('project_dependencies', true);
        $this->forge->dropTable('project_phases', true);
        $this->forge->dropTable('resource_projects', true);
        $this->forge->dropTable('projects', true);
        $this->forge->dropTable('squad_members', true);
        $this->forge->dropTable('resources', true);
        $this->forge->dropTable('squads', true);
    }
}
