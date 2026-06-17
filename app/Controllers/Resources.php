<?php

namespace App\Controllers;

use App\Models\ResourceModel;
use App\Models\ProjectModel;
use App\Models\OrgStructureModel;

class Resources extends BaseController
{
    public function index()
    {
        \App\Controllers\Projects::recalculateAllResourceUtilizations();
        $resourceModel = new ResourceModel();
        $resources = $resourceModel->findAll();

        $orgModel = new OrgStructureModel();
        $departments = $orgModel->orderBy('name', 'ASC')->findAll();

        $roleFilter = $this->request->getGet('role') ?? 'ALL';
        $statusFilter = $this->request->getGet('status') ?? 'ALL';
        $search = $this->request->getGet('search') ?? '';

        $db = \Config\Database::connect();

        // Calculate statistics based on ALL resources
        $totalCount = count($resources);
        $empCount = 0;
        $utilSum = 0;
        foreach ($resources as $r) {
            if ($r['status'] === 'employee') {
                $empCount++;
            }
            $utilSum += $r['utilization'];
        }
        $outCount = $totalCount - $empCount;
        $avgUtil = $totalCount ? round($utilSum / $totalCount) : 0;

        // Populate projects for each resource
        foreach ($resources as &$r) {
            $r['skills'] = array_filter(explode(',', $r['skills']));
            $r['currentProjects'] = $db->table('projects p')
                ->select('p.code, p.name')
                ->join('resource_projects rp', 'p.id = rp.project_id')
                ->where('rp.resource_id', $r['id'])
                ->get()
                ->getResultArray();
        }
        unset($r);

        // Apply filters (role, status, and search)
        $filtered = [];
        foreach ($resources as $r) {
            $roleMatch = ($roleFilter === 'ALL' || $r['role'] === $roleFilter);
            $statusMatch = ($statusFilter === 'ALL' || $r['status'] === $statusFilter);
            
            $searchMatch = true;
            if ($search !== '') {
                $searchLower = strtolower($search);
                $skillsStr = implode(',', $r['skills']);
                $searchMatch = (
                    strpos(strtolower($r['name']), $searchLower) !== false ||
                    strpos(strtolower($r['department']), $searchLower) !== false ||
                    strpos(strtolower($r['role']), $searchLower) !== false ||
                    strpos(strtolower($r['email']), $searchLower) !== false ||
                    strpos(strtolower($r['manager']), $searchLower) !== false ||
                    strpos(strtolower($skillsStr), $searchLower) !== false
                );
            }

            if ($roleMatch && $statusMatch && $searchMatch) {
                $filtered[] = $r;
            }
        }

        $totalFiltered = count($filtered);

        return view('resource', [
            'resources'       => $resources,
            'departments'     => $departments,
            'roleFilter'      => $roleFilter,
            'statusFilter'    => $statusFilter,
            'search'          => $search,
            'totalFiltered'   => $totalFiltered,
            'totalCount'      => $totalCount,
            'empCount'        => $empCount,
            'outCount'        => $outCount,
            'avgUtil'         => $avgUtil,
            'title'           => 'Resource // PMO',
            'currentPath'     => '/resource'
        ]);
    }

    public function map()
    {
        \App\Controllers\Projects::recalculateAllResourceUtilizations();
        $db = \Config\Database::connect();
        
        // Fetch all projects
        $projectModel = new ProjectModel();
        $projects = $projectModel->findAll();

        // Build groups (projects mapping to allocated resources)
        $groups = [];
        foreach ($projects as $p) {
            $resources = $db->table('resources r')
                ->select('r.*')
                ->join('resource_projects rp', 'r.id = rp.resource_id')
                ->where('rp.project_id', $p['id'])
                ->get()
                ->getResultArray();

            if (count($resources) > 0) {
                $groups[] = [
                    'name'     => $p['name'],
                    'code'     => $p['code'],
                    'id'       => $p['id'],
                    'members'  => $resources
                ];
            }
        }

        // Sort by number of members descending
        usort($groups, function ($a, $b) {
            return count($b['members']) - count($a['members']);
        });

        return view('resource_map', [
            'groups' => $groups,
            'title'  => 'Resource Map // PMO',
            'currentPath' => '/resource'
        ]);
    }

    public function create()
    {
        $resourceModel = new ResourceModel();

        $name = $this->request->getPost('name');
        $department = $this->request->getPost('department');
        $role = $this->request->getPost('role');
        $utilization = 0;
        $status = $this->request->getPost('status');
        $email = $this->request->getPost('email');
        $location = $this->request->getPost('location') ?: 'Local';
        $skills = $this->request->getPost('skills') ?: '';
        $manager = $this->request->getPost('manager') ?: '';

        if (empty($name) || empty($department) || empty($role) || empty($status) || empty($email)) {
            return redirect()->back()->with('error', 'Required fields (Name, Department, Role, Status, Email) are missing.');
        }

        $resourceModel->insert([
            'name'        => $name,
            'department'  => $department,
            'role'        => $role,
            'utilization' => $utilization,
            'status'      => $status,
            'email'       => $email,
            'location'    => $location,
            'skills'      => $skills,
            'manager'     => $manager,
        ]);

        \App\Controllers\Projects::recalculateAllResourceUtilizations();

        return redirect()->to('/resource')->with('success', 'Resource created successfully.');
    }

    public function update($id)
    {
        $resourceModel = new ResourceModel();
        $resource = $resourceModel->find($id);

        if (!$resource) {
            return redirect()->to('/resource')->with('error', 'Resource not found.');
        }

        $name = $this->request->getPost('name');
        $department = $this->request->getPost('department');
        $role = $this->request->getPost('role');
        $utilization = $resource['utilization'];
        $status = $this->request->getPost('status');
        $email = $this->request->getPost('email');
        $location = $this->request->getPost('location') ?: 'Local';
        $skills = $this->request->getPost('skills') ?: '';
        $manager = $this->request->getPost('manager') ?: '';

        if (empty($name) || empty($department) || empty($role) || empty($status) || empty($email)) {
            return redirect()->back()->with('error', 'Required fields (Name, Department, Role, Status, Email) are missing.');
        }



        $resourceModel->update($id, [
            'name'        => $name,
            'department'  => $department,
            'role'        => $role,
            'utilization' => $utilization,
            'status'      => $status,
            'email'       => $email,
            'location'    => $location,
            'skills'      => $skills,
            'manager'     => $manager,
        ]);

        \App\Controllers\Projects::recalculateAllResourceUtilizations();

        return redirect()->to('/resource')->with('success', 'Resource updated successfully.');
    }

    public function delete($id)
    {
        $resourceModel = new ResourceModel();
        $resource = $resourceModel->find($id);

        if (!$resource) {
            return redirect()->to('/resource')->with('error', 'Resource not found.');
        }

        // Cascade delete resource associations
        $db = \Config\Database::connect();
        $db->table('resource_projects')->where('resource_id', $id)->delete();
        $db->table('squad_members')->where('resource_id', $id)->delete();

        $resourceModel->delete($id);

        \App\Controllers\Projects::recalculateAllResourceUtilizations();

        return redirect()->to('/resource')->with('success', 'Resource deleted successfully.');
    }

    public function memberDetail($id)
    {
        \App\Controllers\Projects::recalculateAllResourceUtilizations();
        
        $resourceModel = new ResourceModel();
        $member = $resourceModel->find($id);

        if (!$member) {
            return redirect()->to('/resource')->with('error', 'Resource not found.');
        }

        $db = \Config\Database::connect();
        
        // Target member initials
        $initials = '';
        $names = explode(' ', $member['name']);
        foreach ($names as $n) {
            $initials .= strtoupper(substr($n, 0, 1));
        }
        $member['initials'] = substr($initials, 0, 2);
        
        // Parse skills
        $member['skills'] = array_filter(explode(',', $member['skills']));

        // Get projects assigned to target member
        $projects = $db->table('projects p')
            ->select('p.*')
            ->join('resource_projects rp', 'p.id = rp.project_id')
            ->where('rp.resource_id', $id)
            ->get()
            ->getResultArray();

        // Get subtasks/tasks assigned to this resource
        $subtasks = $db->table('project_phase_subtasks s')
            ->select('s.*, ph.name as phase_name, p.name as project_name, p.code as project_code, p.id as project_id')
            ->join('project_phases ph', 's.phase_id = ph.id')
            ->join('projects p', 'ph.project_id = p.id')
            ->where('s.resource_id', $id)
            ->orderBy('s.start', 'ASC')
            ->get()
            ->getResultArray();

        // Get collaborators/peers assigned to same projects (excluding target member)
        $collaborators = [];
        if (!empty($projects)) {
            $projectIds = array_column($projects, 'id');
            
            $collaborators = $db->table('resources r')
                ->select('r.*, rp.project_id')
                ->join('resource_projects rp', 'r.id = rp.resource_id')
                ->whereIn('rp.project_id', $projectIds)
                ->where('r.id !=', $id)
                ->get()
                ->getResultArray();
        }

        // Map projects coordinates for radial layout
        // Center: (450, 300)
        $cx = 450;
        $cy = 300;
        $rInner = 150;
        $rOuter = 280;
        
        $projCoords = [];
        $numProjects = count($projects);
        foreach ($projects as $idx => $p) {
            $angle = $idx * (2 * M_PI / max(1, $numProjects));
            $x = $cx + $rInner * cos($angle);
            $y = $cy + $rInner * sin($angle);
            $projCoords[$p['id']] = ['x' => $x, 'y' => $y];
        }

        // Group collaborators to avoid multiple circles for the same person
        $collabGroups = [];
        foreach ($collaborators as $c) {
            $collabGroups[$c['id']]['info'] = $c;
            $collabGroups[$c['id']]['projects'][] = $c['project_id'];
        }
        
        $collabCoords = [];
        $numCollabs = count($collabGroups);
        $idx = 0;
        foreach ($collabGroups as $cid => $cData) {
            $angle = $idx * (2 * M_PI / max(1, $numCollabs));
            $x = $cx + $rOuter * cos($angle);
            $y = $cy + $rOuter * sin($angle);
            $collabCoords[$cid] = ['x' => $x, 'y' => $y];
            
            $collabInfo = $cData['info'];
            $cInitials = '';
            $cNames = explode(' ', $collabInfo['name']);
            foreach ($cNames as $cn) {
                $cInitials .= strtoupper(substr($cn, 0, 1));
            }
            $collabInfo['initials'] = substr($cInitials, 0, 2);
            $collabGroups[$cid]['info'] = $collabInfo;
            
            $idx++;
        }

        // Links: from target (center) to project, and from project to collaborator
        $links = [];
        $centerId = 'member_' . $id;

        foreach ($projCoords as $pid => $coord) {
            $links[] = [
                'from_id' => $centerId,
                'from_x' => $cx,
                'from_y' => $cy,
                'to_id' => 'proj_' . $pid,
                'to_x' => $coord['x'],
                'to_y' => $coord['y'],
                'class' => 'link-member-proj',
                'data_project' => $pid,
                'data_resource' => ''
            ];
        }

        foreach ($collabGroups as $cid => $cData) {
            $coord = $collabCoords[$cid];
            foreach ($cData['projects'] as $pid) {
                if (isset($projCoords[$pid])) {
                    $links[] = [
                        'from_id' => 'proj_' . $pid,
                        'from_x' => $projCoords[$pid]['x'],
                        'from_y' => $projCoords[$pid]['y'],
                        'to_id' => 'collab_' . $cid,
                        'to_x' => $coord['x'],
                        'to_y' => $coord['y'],
                        'class' => 'link-proj-collab',
                        'data_project' => $pid,
                        'data_resource' => $cid
                    ];
                }
            }
        }

        return view('member_detail', [
            'member'        => $member,
            'projects'      => $projects,
            'subtasks'      => $subtasks,
            'collabGroups'  => $collabGroups,
            'projCoords'    => $projCoords,
            'collabCoords'  => $collabCoords,
            'links'         => $links,
            'cx'            => $cx,
            'cy'            => $cy,
            'title'         => $member['name'] . ' // Resource // PMO',
            'currentPath'   => '/resource'
        ]);
    }
}
