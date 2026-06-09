<?php

namespace App\Controllers;

use App\Models\ResourceModel;
use App\Models\ProjectModel;
use App\Models\OrgStructureModel;

class Resources extends BaseController
{
    public function index()
    {
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

        // Pagination setup
        $totalFiltered = count($filtered);
        $perPage = 10;
        $currentPage = max(1, (int)($this->request->getGet('page') ?? 1));
        $totalPages = max(1, (ceil($totalFiltered / $perPage)));
        if ($currentPage > $totalPages) {
            $currentPage = $totalPages;
        }
        $offset = ($currentPage - 1) * $perPage;
        $paginatedResources = array_slice($filtered, $offset, $perPage);

        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'html' => view('partials/resource_rows', ['resources' => $paginatedResources]),
                'pagination' => view('partials/resource_pagination', [
                    'currentPage' => $currentPage,
                    'totalPages' => $totalPages,
                    'roleFilter' => $roleFilter,
                    'statusFilter' => $statusFilter,
                    'search' => $search,
                    'totalFiltered' => $totalFiltered,
                    'offset' => $offset,
                    'perPage' => $perPage
                ])
            ]);
        }

        return view('resource', [
            'resources'       => $paginatedResources,
            'departments'     => $departments,
            'roleFilter'      => $roleFilter,
            'statusFilter'    => $statusFilter,
            'search'          => $search,
            'currentPage'     => $currentPage,
            'totalPages'      => $totalPages,
            'totalFiltered'   => $totalFiltered,
            'offset'          => $offset,
            'perPage'         => $perPage,
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

        return redirect()->to('/resource')->with('success', 'Resource deleted successfully.');
    }
}
