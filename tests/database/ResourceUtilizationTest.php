<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * @internal
 */
final class ResourceUtilizationTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    // Migrate database before running tests
    protected $migrate = true;
    protected $migrateOnce = false;
    protected $namespace = 'App';

    public function testResourceUtilizationWeeklyLoadCalculation(): void
    {
        $db = \Config\Database::connect();

        // 1. Ensure the default settings are set (the migration does this, but we'll enforce it)
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

        // 2. Insert a resource
        $resourceId = $db->table('resources')->insert([
            'name'        => 'Dimas Harsono',
            'department'  => 'Engineering',
            'role'        => 'Developer',
            'utilization' => 0,
            'status'      => 'employee',
            'email'       => 'dimas@example.com',
            'location'    => 'Jakarta',
            'skills'      => 'PHP, CodeIgniter, MySQL',
            'manager'     => 'Manager Name'
        ]);
        $resourceId = $db->insertID();

        // 3. Insert a project
        $projectId = 'test-proj-util';
        $db->table('projects')->insert([
            'id'        => $projectId,
            'code'      => 'TPU',
            'name'      => 'Test Project Utilization',
            'owner'     => 'Owner Name',
            'status'    => 'active',
            'startDate' => '2026-06-01',
            'endDate'   => '2026-06-30'
        ]);

        // 4. Insert a project phase
        $db->table('project_phases')->insert([
            'project_id' => $projectId,
            'name'       => 'Phase 1',
            'start'      => '2026-06-01',
            'end'        => '2026-06-30',
            'status'     => 'active'
        ]);
        $phaseId = $db->insertID();

        // 5. Insert subtask 1: task_hours = 40, duration 7 days -> 1 week span -> weekly load = 40
        $db->table('project_phase_subtasks')->insert([
            'phase_id'    => $phaseId,
            'name'        => 'Subtask 1',
            'start'       => '2026-06-01',
            'end'         => '2026-06-07',
            'status'      => 'active',
            'resource_id' => $resourceId,
            'task_hours'  => 40.00
        ]);

        // 6. Insert subtask 2: task_hours = 40, duration 10 days -> 2 week span -> weekly load = 20
        $db->table('project_phase_subtasks')->insert([
            'phase_id'    => $phaseId,
            'name'        => 'Subtask 2',
            'start'       => '2026-06-01',
            'end'         => '2026-06-10', // 10 days
            'status'      => 'active',
            'resource_id' => $resourceId,
            'task_hours'  => 40.00
        ]);

        // 7. Insert completed subtask: task_hours = 100, should be ignored
        $db->table('project_phase_subtasks')->insert([
            'phase_id'    => $phaseId,
            'name'        => 'Subtask 3 (Completed)',
            'start'       => '2026-06-01',
            'end'         => '2026-06-05',
            'status'      => 'completed',
            'resource_id' => $resourceId,
            'task_hours'  => 100.00
        ]);

        // Run the calculation method
        \App\Controllers\Projects::recalculateAllResourceUtilizations();

        // Retrieve the resource
        $resource = $db->table('resources')->where('id', $resourceId)->get()->getRowArray();

        // Expected load:
        // Subtask 1: ceil(7 / 7) = 1 week. load = 40 / 1 = 40
        // Subtask 2: ceil(10 / 7) = 2 weeks. load = 40 / 2 = 20
        // Total Weekly Load = 40 + 20 = 60
        // Capacity = 8 * 5 = 40
        // Utilization = round((60 / 40) * 100) = 150%
        $this->assertEquals(150, (int)$resource['utilization']);
    }

    public function testResourceUtilizationEdgeCases(): void
    {
        $db = \Config\Database::connect();

        // 1. Ensure the default settings are set
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

        // Insert common project & phase for testing
        $projectId = 'test-proj-util-edge';
        $db->table('projects')->insert([
            'id'        => $projectId,
            'code'      => 'TPUE',
            'name'      => 'Test Project Utilization Edge',
            'owner'     => 'Owner Name',
            'status'    => 'active',
            'startDate' => '2026-06-01',
            'endDate'   => '2026-06-30'
        ]);

        $db->table('project_phases')->insert([
            'project_id' => $projectId,
            'name'       => 'Phase 1',
            'start'      => '2026-06-01',
            'end'        => '2026-06-30',
            'status'     => 'active'
        ]);
        $phaseId = $db->insertID();

        // Edge Case A: Resource with no subtasks
        $resAId = $db->table('resources')->insert([
            'name'        => 'Resource A',
            'department'  => 'Engineering',
            'role'        => 'Developer',
            'utilization' => 99, // start with dummy value to see if it resets
            'status'      => 'employee',
            'email'       => 'resa@example.com',
            'location'    => 'Jakarta',
            'skills'      => 'PHP',
            'manager'     => 'Manager'
        ]);
        $resAId = $db->insertID();

        // Edge Case B: Resource with subtask spanning 7 days (1.0 week)
        $resBId = $db->table('resources')->insert([
            'name'        => 'Resource B',
            'department'  => 'Engineering',
            'role'        => 'Developer',
            'utilization' => 0,
            'status'      => 'employee',
            'email'       => 'resb@example.com',
            'location'    => 'Jakarta',
            'skills'      => 'PHP',
            'manager'     => 'Manager'
        ]);
        $resBId = $db->insertID();

        $db->table('project_phase_subtasks')->insert([
            'phase_id'    => $phaseId,
            'name'        => 'Subtask B1',
            'start'       => '2026-06-01',
            'end'         => '2026-06-07',
            'status'      => 'active',
            'resource_id' => $resBId,
            'task_hours'  => 20.00
        ]);

        // Edge Case C: Resource with subtask of 1 day duration (start = end, e.g. 2026-06-01 to 2026-06-01 -> 1 week)
        $resCId = $db->table('resources')->insert([
            'name'        => 'Resource C',
            'department'  => 'Engineering',
            'role'        => 'Developer',
            'utilization' => 0,
            'status'      => 'employee',
            'email'       => 'resc@example.com',
            'location'    => 'Jakarta',
            'skills'      => 'PHP',
            'manager'     => 'Manager'
        ]);
        $resCId = $db->insertID();

        $db->table('project_phase_subtasks')->insert([
            'phase_id'    => $phaseId,
            'name'        => 'Subtask C1',
            'start'       => '2026-06-01',
            'end'         => '2026-06-01',
            'status'      => 'active',
            'resource_id' => $resCId,
            'task_hours'  => 8.00
        ]);

        // Edge Case D: Resource with subtask spanning 8 days (2 weeks)
        $resDId = $db->table('resources')->insert([
            'name'        => 'Resource D',
            'department'  => 'Engineering',
            'role'        => 'Developer',
            'utilization' => 0,
            'status'      => 'employee',
            'email'       => 'resd@example.com',
            'location'    => 'Jakarta',
            'skills'      => 'PHP',
            'manager'     => 'Manager'
        ]);
        $resDId = $db->insertID();

        $db->table('project_phase_subtasks')->insert([
            'phase_id'    => $phaseId,
            'name'        => 'Subtask D1',
            'start'       => '2026-06-01',
            'end'         => '2026-06-08', // 8 days
            'status'      => 'active',
            'resource_id' => $resDId,
            'task_hours'  => 16.00
        ]);

        // Run the calculation method
        \App\Controllers\Projects::recalculateAllResourceUtilizations();

        // Retrieve and Assert
        $resA = $db->table('resources')->where('id', $resAId)->get()->getRowArray();
        $resB = $db->table('resources')->where('id', $resBId)->get()->getRowArray();
        $resC = $db->table('resources')->where('id', $resCId)->get()->getRowArray();
        $resD = $db->table('resources')->where('id', $resDId)->get()->getRowArray();

        // Assert A: 0%
        $this->assertEquals(0, (int)$resA['utilization']);

        // Assert B: task_hours = 20, weeks = 1 -> load = 20. cap = 40. utilization = 50%
        $this->assertEquals(50, (int)$resB['utilization']);

        // Assert C: task_hours = 8, weeks = 1 -> load = 8. cap = 40. utilization = 20%
        $this->assertEquals(20, (int)$resC['utilization']);

        // Assert D: task_hours = 16, weeks = 2 -> load = 8. cap = 40. utilization = 20%
        $this->assertEquals(20, (int)$resD['utilization']);
    }
}
