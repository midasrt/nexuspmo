<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class EchoCommandTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate = true;
    protected $migrateOnce = false;
    protected $namespace = 'App';

    public function testEchoCommandCreateAndUpdateSubtask(): void
    {
        $db = \Config\Database::connect();

        // Setup settings
        $db->table('settings')->where('key_name', 'daily_work_hours')->delete();
        $db->table('settings')->where('key_name', 'work_days_per_week')->delete();
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

        // Insert a resource
        $db->table('resources')->insert([
            'name'        => 'Nanang Adi Utomo',
            'department'  => 'Engineering',
            'role'        => 'Developer',
            'utilization' => 0,
            'status'      => 'employee',
            'email'       => 'nanang@example.com',
            'location'    => 'Jakarta',
            'skills'      => 'PHP, MySQL',
            'manager'     => 'Manager Name'
        ]);
        $resourceId = $db->insertID();

        // Insert a project
        $projectId = 'test-echo-proj';
        $db->table('projects')->insert([
            'id'        => $projectId,
            'code'      => 'TEP',
            'name'      => 'Test Echo Project',
            'owner'     => 'Owner Name',
            'status'    => 'active',
            'startDate' => '2026-06-01',
            'endDate'   => '2026-06-30'
        ]);

        // Assign resource to project
        $db->table('resource_projects')->insert([
            'resource_id' => $resourceId,
            'project_id'  => $projectId
        ]);

        // Insert a project phase
        $db->table('project_phases')->insert([
            'project_id' => $projectId,
            'name'       => 'Phase 1',
            'start'      => '2026-06-01',
            'end'        => '2026-06-30',
            'status'     => 'active'
        ]);
        $phaseId = $db->insertID();

        // Mock session and request
        $_SESSION['isLoggedIn'] = true;
        $_SESSION['role'] = 'admin';

        $request = Config\Services::request();
        $request->setGlobal('post', [
            'command' => 'Create a phase called [Phase 2] for start date on [2026-06-18] to end on [2026-06-25] with status [active]'
        ]);

        $controller = new \App\Controllers\Projects();
        $controller->initController($request, Config\Services::response(), Config\Services::logger());

        $response = $controller->echoCommand($projectId);
        $resBody = json_decode($response->getBody(), true);

        $this->assertEquals('success', $resBody['status']);

        $phase2 = $db->table('project_phases')->where('name', 'Phase 2')->get()->getRowArray();
        $this->assertNotNull($phase2);
        $this->assertEquals('2026-06-18', $phase2['start']);
        $this->assertEquals('2026-06-25', $phase2['end']);
        $this->assertEquals('active', $phase2['status']);

        $request = Config\Services::request();
        $request->setGlobal('post', [
            'command' => 'Create a subtask on [Phase 1] called [Subtask A] for start date on [2026-06-15] with [5] mandays assigned to [Nanang Adi Utomo] with status [active]'
        ]);

        $controller = new \App\Controllers\Projects();
        $controller->initController($request, Config\Services::response(), Config\Services::logger());

        // Run echo command
        $response = $controller->echoCommand($projectId);
        $resBody = json_decode($response->getBody(), true);

        $this->assertEquals('success', $resBody['status']);

        // Check database
        $subtask = $db->table('project_phase_subtasks')->where('name', 'Subtask A')->get()->getRowArray();
        $this->assertNotNull($subtask);
        $this->assertEquals('2026-06-15', $subtask['start']);
        $this->assertEquals(5.0, (float)$subtask['man_days']);
        $this->assertEquals(40.0, (float)$subtask['task_hours']);
        $this->assertEquals($resourceId, $subtask['resource_id']);
        $this->assertEquals('active', $subtask['status']);

        // Now update the subtask
        $request = Config\Services::request();
        $request->setGlobal('post', [
            'command' => 'Update [Subtask A] start date on [2026-06-16] with [3] mandays assigned to [Nanang Adi Utomo] with status [complete]'
        ]);
        $controller = new \App\Controllers\Projects();
        $controller->initController($request, Config\Services::response(), Config\Services::logger());
        $response = $controller->echoCommand($projectId);
        $resBody = json_decode($response->getBody(), true);

        $this->assertEquals('success', $resBody['status']);

        // Check updated database values
        $updatedSubtask = $db->table('project_phase_subtasks')->where('name', 'Subtask A')->get()->getRowArray();
        $this->assertNotNull($updatedSubtask);
        $this->assertEquals('2026-06-16', $updatedSubtask['start']);
        $this->assertEquals(3.0, (float)$updatedSubtask['man_days']);
        $this->assertEquals(24.0, (float)$updatedSubtask['task_hours']);
        $this->assertEquals($resourceId, $updatedSubtask['resource_id']);
        $this->assertEquals('complete', $updatedSubtask['status']);

        // Test assigning to "no one"
        $request = Config\Services::request();
        $request->setGlobal('post', [
            'command' => 'Update [Subtask A] start date on [2026-06-16] with [3] mandays assigned to [no one] with status [complete]'
        ]);
        $controller = new \App\Controllers\Projects();
        $controller->initController($request, Config\Services::response(), Config\Services::logger());
        $response = $controller->echoCommand($projectId);
        $resBody = json_decode($response->getBody(), true);

        $this->assertEquals('success', $resBody['status']);

        $unassignedSubtask = $db->table('project_phase_subtasks')->where('name', 'Subtask A')->get()->getRowArray();
        $this->assertNull($unassignedSubtask['resource_id']);
        $subtaskId = $unassignedSubtask['id'];

        // Test editing subtask by ID (#id)
        $request = Config\Services::request();
        $request->setGlobal('post', [
            'command' => "Update [#{$subtaskId}] start date on [2026-06-18] with [4] mandays assigned to [unassigned] with status [active]"
        ]);
        $controller = new \App\Controllers\Projects();
        $controller->initController($request, Config\Services::response(), Config\Services::logger());
        $response = $controller->echoCommand($projectId);
        $resBody = json_decode($response->getBody(), true);

        $this->assertEquals('success', $resBody['status']);

        $updatedByIdSubtask = $db->table('project_phase_subtasks')->where('id', $subtaskId)->get()->getRowArray();
        $this->assertEquals('2026-06-18', $updatedByIdSubtask['start']);
        $this->assertEquals(4.0, (float)$updatedByIdSubtask['man_days']);
        $this->assertEquals('active', $updatedByIdSubtask['status']);

        // Test explicit assign resource to subtask by ID
        $request = Config\Services::request();
        $request->setGlobal('post', [
            'command' => "Assign resource [Nanang Adi Utomo] to subtask [#{$subtaskId}]"
        ]);
        $controller = new \App\Controllers\Projects();
        $controller->initController($request, Config\Services::response(), Config\Services::logger());
        $response = $controller->echoCommand($projectId);
        $resBody = json_decode($response->getBody(), true);

        $this->assertEquals('success', $resBody['status']);

        $assignedByIdSubtask = $db->table('project_phase_subtasks')->where('id', $subtaskId)->get()->getRowArray();
        $this->assertEquals($resourceId, $assignedByIdSubtask['resource_id']);

        // Test explicit unassign resource from subtask by ID
        $request = Config\Services::request();
        $request->setGlobal('post', [
            'command' => "Unassign resource from subtask [#{$subtaskId}]"
        ]);
        $controller = new \App\Controllers\Projects();
        $controller->initController($request, Config\Services::response(), Config\Services::logger());
        $response = $controller->echoCommand($projectId);
        $resBody = json_decode($response->getBody(), true);

        $this->assertEquals('success', $resBody['status']);

        $unassignedByIdSubtask = $db->table('project_phase_subtasks')->where('id', $subtaskId)->get()->getRowArray();
        $this->assertNull($unassignedByIdSubtask['resource_id']);
    }
}
