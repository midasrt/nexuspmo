<?php

namespace App\Controllers;

use App\Models\ProjectModel;
use App\Models\PhaseModel;
use App\Models\DependencyModel;
use App\Models\ActionItemModel;
use App\Models\StatusHistoryModel;
use App\Models\EscalationModel;
use App\Models\RiskModel;
use App\Models\DocumentModel;
use App\Models\ResourceModel;
use App\Models\SquadModel;
use App\Models\ProjectNarrativeModel;
use App\Models\ProjectDetailedHistoryModel;

class Projects extends BaseController
{
    public function index()
    {
        $projectModel = new ProjectModel();
        $squadModel = new SquadModel();
        $resourceModel = new ResourceModel();
        $statusDefModel = new \App\Models\StatusDefinitionModel();

        $projects = $projectModel->findAll();
        $filter = $this->request->getGet('filter') ?? 'all';
        $statusDefinitions = $statusDefModel->findAll();

        // Load relations for count summaries
        $phaseModel = new PhaseModel();
        $dependencyModel = new DependencyModel();
        $actionItemModel = new ActionItemModel();
        $riskModel = new RiskModel();
        $escalationModel = new EscalationModel();

        foreach ($projects as &$p) {
            $p['phases'] = $phaseModel->where('project_id', $p['id'])->findAll();
            $subtaskModel = new \App\Models\SubtaskModel();
            $totalProgressSum = 0;
            foreach ($p['phases'] as $ph) {
                $subtasks = $subtaskModel->where('phase_id', $ph['id'])->findAll();
                if (count($subtasks) > 0) {
                    $comp = 0;
                    foreach ($subtasks as $sub) {
                        if (in_array(strtolower($sub['status']), ['complete', 'completed', 'done'])) {
                            $comp++;
                        }
                    }
                    $totalProgressSum += ($comp / count($subtasks));
                } else {
                    if (in_array(strtolower($ph['status']), ['complete', 'completed', 'done'])) {
                        $totalProgressSum += 1.0;
                    }
                }
            }
            $p['progress'] = count($p['phases']) > 0 ? (int)(($totalProgressSum / count($p['phases'])) * 100) : 0;
            
            $p['dependencies'] = $dependencyModel->where('project_id', $p['id'])->findAll();
            $p['actionItems'] = $actionItemModel->where('project_id', $p['id'])->findAll();
            $p['risks'] = $riskModel->where('project_id', $p['id'])->findAll();
            $p['escalations'] = $escalationModel->where('project_id', $p['id'])->findAll();
        }

        // Apply filter
        $allCount = count($projects);
        $filtered = $projects;
        if ($filter !== 'all') {
            $filtered = [];
            foreach ($projects as $p) {
                if ($p['status'] === $filter) {
                    $filtered[] = $p;
                }
            }
        }

        $squads = $squadModel->findAll();
        $resources = $resourceModel->findAll();

        return view('projects', [
            'projects'  => $projects,
            'filtered'  => $filtered,
            'filter'    => $filter,
            'squads'    => $squads,
            'resources' => $resources,
            'statusDefinitions' => $statusDefinitions,
            'title'     => 'Projects // PMO',
            'currentPath' => '/projects'
        ]);
    }

    public function detail($id)
    {
        self::recalculateAllResourceUtilizations();
        $projectModel = new ProjectModel();
        $project = $projectModel->find($id);

        if (!$project) {
            return view('errors/project_not_found', [
                'id' => $id,
                'title' => 'Project Not Found // PMO',
                'currentPath' => '/projects'
            ]);
        }

        $phaseModel = new PhaseModel();
        $dependencyModel = new DependencyModel();
        $actionItemModel = new ActionItemModel();
        $statusHistoryModel = new StatusHistoryModel();
        $escalationModel = new EscalationModel();
        $riskModel = new RiskModel();
        $documentModel = new DocumentModel();

        // Fetch relations
        $db = \Config\Database::connect();
        $project['phases'] = $db->table('project_phases p')
            ->select('p.*, r.name as resource_name, r.role as resource_role')
            ->join('resources r', 'p.resource_id = r.id', 'left')
            ->where('p.project_id', $id)
            ->orderBy('p.sequence', 'ASC')
            ->orderBy('p.start', 'ASC')
            ->get()
            ->getResultArray();

        $totalProgressSum = 0;
        foreach ($project['phases'] as &$ph) {
            $ph['subtasks'] = $db->table('project_phase_subtasks s')
                ->select('s.*, r.name as resource_name, r.role as resource_role')
                ->join('resources r', 's.resource_id = r.id', 'left')
                ->where('s.phase_id', $ph['id'])
                ->orderBy('s.sequence', 'ASC')
                ->orderBy('s.start', 'ASC')
                ->get()
                ->getResultArray();

            $phProgress = 0;
            if (count($ph['subtasks']) > 0) {
                $comp = 0;
                foreach ($ph['subtasks'] as $sub) {
                    if (in_array(strtolower($sub['status']), ['complete', 'completed', 'done'])) {
                        $comp++;
                    }
                }
                $phProgress = (int)(($comp / count($ph['subtasks'])) * 100);
                $totalProgressSum += ($comp / count($ph['subtasks']));
            } else {
                $phProgress = in_array(strtolower($ph['status']), ['complete', 'completed', 'done']) ? 100 : 0;
                if (in_array(strtolower($ph['status']), ['complete', 'completed', 'done'])) {
                    $totalProgressSum += 1.0;
                }
            }
            $ph['progress'] = $phProgress;
        }
        unset($ph);
        $project['progress'] = count($project['phases']) > 0 ? (int)(($totalProgressSum / count($project['phases'])) * 100) : 0;
        
        $project['dependencies'] = $dependencyModel->where('project_id', $id)->findAll();
        
        // Order action items so incomplete ones are first, or by ID
        $project['actionItems'] = $actionItemModel->where('project_id', $id)->orderBy('done', 'ASC')->findAll();
        
        // Order status history descending by date and ID
        $project['statusHistory'] = $statusHistoryModel->where('project_id', $id)->orderBy('date', 'DESC')->orderBy('id', 'DESC')->findAll();
        
        $project['escalations'] = $escalationModel->where('project_id', $id)->findAll();
        $project['risks'] = $riskModel->where('project_id', $id)->findAll();
        $project['documents'] = $documentModel->where('project_id', $id)->findAll();

        $narrativeModel = new ProjectNarrativeModel();
        $project['narratives'] = $narrativeModel->where('project_id', $id)->orderBy('id', 'DESC')->findAll();

        $detailedModel = new ProjectDetailedHistoryModel();
        $project['detailedHistory'] = $detailedModel->where('project_id', $id)->orderBy('id', 'DESC')->findAll();

        // Fetch assigned resources
        $db = \Config\Database::connect();
        $assignedResources = $db->table('resources r')
            ->select('r.*')
            ->join('resource_projects rp', 'r.id = rp.resource_id')
            ->where('rp.project_id', $id)
            ->get()
            ->getResultArray();

        $resourceModel = new ResourceModel();
        $allResources = $resourceModel->findAll();
        $allProjects = $projectModel->where('id !=', $id)->findAll();

        $squadModel = new \App\Models\SquadModel();
        $allSquads = $squadModel->findAll();

        $settingsModel = new \App\Models\SettingsModel();
        $dailyWorkHours = $settingsModel->getSetting('daily_work_hours', 8);
        $workDaysPerWeek = $settingsModel->getSetting('work_days_per_week', 5);

        return view('project_detail', [
            'project'           => $project,
            'assignedResources' => $assignedResources,
            'allResources'      => $allResources,
            'allProjects'       => $allProjects,
            'allSquads'         => $allSquads,
            'dailyWorkHours'    => $dailyWorkHours,
            'workDaysPerWeek'   => $workDaysPerWeek,
            'title'             => strtoupper($project['id']) . ' // Project Detail',
            'currentPath'       => '/projects'
        ]);
    }

    public function create()
    {
        $projectModel = new ProjectModel();

        $code = $this->request->getPost('code');
        $name = $this->request->getPost('name');
        $owner = $this->request->getPost('owner');
        $squad = $this->request->getPost('squad');
        $status = $this->request->getPost('status');
        $startDate = $this->request->getPost('startDate') ?: date('Y-m-d');
        $endDate = $this->request->getPost('endDate') ?: date('Y-m-d', strtotime('+6 months'));
        $progress = $this->request->getPost('progress') ?: 0;
        $description = $this->request->getPost('description');

        if (empty($code) || empty($name)) {
            return redirect()->back()->with('error', 'Code and Name are required.');
        }

        // Generate ID from code slugified
        $id = preg_replace('/[^a-z0-9]+/i', '-', strtolower($code));
        $id = trim($id, '-');

        // Check if ID exists
        if ($projectModel->find($id)) {
            $id = $id . '-' . rand(10, 99);
        }

        $data = [
            'id'          => $id,
            'code'        => $code,
            'name'        => $name,
            'owner'       => $owner,
            'squad'       => $squad,
            'status'      => $status,
            'health'      => $status,
            'startDate'   => $startDate,
            'endDate'     => $endDate,
            'progress'    => $progress,
            'description' => $description
        ];

        $projectModel->insert($data);

        // Insert default status history entry
        $statusHistoryModel = new StatusHistoryModel();
        $statusHistoryModel->insert([
            'project_id' => $id,
            'date'       => date('Y-m-d'),
            'status'     => $status,
            'note'       => 'Created.'
        ]);

        // Automatically assign owner resource to project
        $db = \Config\Database::connect();
        $resource = $db->table('resources')->where('name', $owner)->get()->getRowArray();
        if ($resource) {
            $db->table('resource_projects')->insert([
                'resource_id' => $resource['id'],
                'project_id' => $id
            ]);
        }

        // Initialize phases
        $phaseModel = new PhaseModel();
        $phases = $this->request->getPost('phases');
        if (is_array($phases) && !empty($phases)) {
            foreach ($phases as $ph) {
                if (!empty($ph['name'])) {
                    $phaseModel->insert([
                        'project_id'  => $id,
                        'name'        => $ph['name'],
                        'description' => $ph['description'] ?? '',
                        'start'       => $ph['start'] ?: $startDate,
                        'end'         => $ph['end'] ?: $endDate,
                        'status'      => $ph['status'] ?: 'backlog',
                    ]);
                }
            }
        }

        $this->updateProjectProgress($id);

        return redirect()->to('/projects')->with('success', 'Project created successfully.');
    }

    public function update($id)
    {
        $projectModel = new ProjectModel();
        $project = $projectModel->find($id);

        if (!$project) {
            return redirect()->to('/projects')->with('error', 'Project not found.');
        }

        $oldState = $this->getProjectState($id);

        $status = $this->request->getPost('status');
        $oldStatus = $project['status'];

        $data = [
            'code'        => $this->request->getPost('code'),
            'name'        => $this->request->getPost('name'),
            'owner'       => $this->request->getPost('owner'),
            'squad'       => $this->request->getPost('squad'),
            'status'      => $status,
            'health'      => $status,
            'startDate'   => $this->request->getPost('startDate'),
            'endDate'     => $this->request->getPost('endDate'),
            'progress'    => $this->request->getPost('progress'),
            'description' => $this->request->getPost('description'),
        ];

        $projectModel->update($id, $data);

        // Sync phases
        $phases = $this->request->getPost('phases');
        $phaseModel = new PhaseModel();
        if (is_array($phases)) {
            $submittedIds = [];
            foreach ($phases as $ph) {
                if (!empty($ph['id'])) {
                    $submittedIds[] = $ph['id'];
                }
            }

            // Find phases to delete
            $subtaskModel = new \App\Models\SubtaskModel();
            $queryToDelete = $phaseModel->where('project_id', $id);
            if (!empty($submittedIds)) {
                $queryToDelete->whereNotIn('id', $submittedIds);
            }
            $phasesToDelete = $queryToDelete->findAll();
            
            foreach ($phasesToDelete as $pDelete) {
                $subtaskModel->where('phase_id', $pDelete['id'])->delete();
                $phaseModel->delete($pDelete['id']);
            }

            // Update or Insert submitted phases
            foreach ($phases as $ph) {
                if (!empty($ph['name'])) {
                    $phaseData = [
                        'project_id'  => $id,
                        'name'        => $ph['name'],
                        'description' => $ph['description'] ?? '',
                        'start'       => $ph['start'] ?: $data['startDate'],
                        'end'         => $ph['end'] ?: $data['endDate'],
                        'status'      => $ph['status'] ?: 'backlog',
                    ];

                    if (!empty($ph['id'])) {
                        $phaseModel->update($ph['id'], $phaseData);
                    } else {
                        $maxSeq = $phaseModel->where('project_id', $id)->selectMax('sequence')->first();
                        $nextSeq = isset($maxSeq['sequence']) ? $maxSeq['sequence'] + 1 : 0;
                        $phaseData['sequence'] = $nextSeq;
                        
                        $phaseModel->insert($phaseData);
                    }
                }
            }
        }

        $this->updateProjectProgress($id);

        // Record changes in history
        $notes = [];
        if ($data['name'] !== $project['name']) $notes[] = "Name updated to '{$data['name']}'";
        if ($data['code'] !== $project['code']) $notes[] = "Code updated to '{$data['code']}'";
        if ($data['owner'] !== $project['owner']) $notes[] = "Owner updated to '{$data['owner']}'";
        if ($data['squad'] !== $project['squad']) $notes[] = "Squad updated to '{$data['squad']}'";
        if ($data['startDate'] !== $project['startDate']) $notes[] = "Start date updated to '{$data['startDate']}'";
        if ($data['endDate'] !== $project['endDate']) $notes[] = "End date updated to '{$data['endDate']}'";
        if ($data['progress'] != $project['progress']) $notes[] = "Progress updated to {$data['progress']}%";
        if ($status !== $oldStatus) $notes[] = "Status updated from " . strtoupper($oldStatus) . " to " . strtoupper($status);
        
        if (!empty($notes)) {
            $this->logProjectActivity($id, implode('; ', $notes), $oldState);
        }

        return redirect()->to('/projects')->with('success', 'Project updated successfully.');
    }

    public function delete($id)
    {
        $projectModel = new ProjectModel();
        if (!$projectModel->find($id)) {
            return redirect()->to('/projects')->with('error', 'Project not found.');
        }

        // Delete cascade items
        $db = \Config\Database::connect();
        $db->table('project_phases')->where('project_id', $id)->delete();
        $db->table('project_dependencies')->where('project_id', $id)->delete();
        $db->table('project_action_items')->where('project_id', $id)->delete();
        $db->table('project_status_history')->where('project_id', $id)->delete();
        $db->table('project_escalations')->where('project_id', $id)->delete();
        $db->table('project_risks')->where('project_id', $id)->delete();
        
        // Remove document files and database records
        $docModel = new DocumentModel();
        $docs = $docModel->where('project_id', $id)->findAll();
        foreach ($docs as $d) {
            $filePath = ROOTPATH . 'public/' . $d['file_path'];
            if (is_file($filePath)) {
                unlink($filePath);
            }
        }
        $db->table('project_documents')->where('project_id', $id)->delete();
        $db->table('resource_projects')->where('project_id', $id)->delete();

        // Finally delete project
        $projectModel->delete($id);

        return redirect()->to('/projects')->with('success', 'Project deleted successfully.');
    }

    public function uploadDocument($project_id)
    {
        $files = $this->request->getFiles();
        $uploadedNames = [];

        if (isset($files['documents'])) {
            $docModel = new DocumentModel();
            foreach ($files['documents'] as $file) {
                if ($file->isValid() && !$file->hasMoved()) {
                    $newName = $file->getRandomName();
                    
                    // Ensure upload path directory exists
                    $uploadDir = ROOTPATH . 'public/uploads';
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }

                    $originalName = $file->getClientName();
                    $fileSize = $file->getSize();

                    $file->move($uploadDir, $newName);

                    $docModel->insert([
                        'id'          => uniqid('doc_', true),
                        'project_id'  => $project_id,
                        'name'        => $originalName,
                        'size'        => $fileSize,
                        'uploaded_at' => date('Y-m-d'),
                        'file_path'   => 'uploads/' . $newName
                    ]);
                    $uploadedNames[] = $originalName;
                }
            }
        }

        if (!empty($uploadedNames)) {
            $this->logProjectActivity($project_id, "Uploaded document(s): " . implode(', ', $uploadedNames));
        }

        return redirect()->to('/project/' . $project_id)->with('success', 'Documents uploaded successfully.');
    }

    public function deleteDocument($project_id, $doc_id)
    {
        $docModel = new DocumentModel();
        $doc = $docModel->find($doc_id);

        if ($doc) {
            $filePath = ROOTPATH . 'public/' . $doc['file_path'];
            if (is_file($filePath)) {
                unlink($filePath);
            }
            $docModel->delete($doc_id);
            $this->logProjectActivity($project_id, "Deleted document: " . $doc['name']);
        }

        return redirect()->to('/project/' . $project_id)->with('success', 'Document deleted successfully.');
    }

    public function toggleActionItem($project_id, $action_id)
    {
        $actionModel = new ActionItemModel();
        $item = $actionModel->find($action_id);

        if ($item) {
            $newDone = $item['done'] ? 0 : 1;
            $resolvedDate = $newDone ? date('Y-m-d') : null;
            
            $actionModel->update($action_id, [
                'done'          => $newDone,
                'resolved_date' => $resolvedDate
            ]);
            
            if ($newDone) {
                $statusStr = "completed on {$resolvedDate}";
            } else {
                $statusStr = "pending";
            }
            $this->logProjectActivity($project_id, "Action Item '{$item['title']}' marked as {$statusStr}");
        }

        // Return JSON if requested, otherwise redirect back
        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['status' => 'success', 'done' => $newDone]);
        }

        return redirect()->to('/project/' . $project_id);
    }

    public function createPhaseAjax($project_id) {
        $oldState = $this->getProjectState($project_id);
        $phaseModel = new PhaseModel();
        $name = $this->request->getPost('name');
        $description = $this->request->getPost('description') ?: '';
        $start = $this->request->getPost('start') ?: date('Y-m-d');
        $end = $this->request->getPost('end') ?: date('Y-m-d');
        $status = $this->request->getPost('status') ?: 'backlog';
        
        $maxSeq = $phaseModel->where('project_id', $project_id)->selectMax('sequence')->first();
        $nextSeq = isset($maxSeq['sequence']) ? $maxSeq['sequence'] + 1 : 0;
        
        if (empty($name)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Phase Name is required.']);
        }

        // Subtasks inputs
        $subtaskNames = $this->request->getPost('subtask_names') ?: [];
        $subtaskStarts = $this->request->getPost('subtask_starts') ?: [];
        $subtaskEnds = $this->request->getPost('subtask_ends') ?: [];
        $subtaskStatuses = $this->request->getPost('subtask_statuses') ?: [];
        $subtaskDescriptions = $this->request->getPost('subtask_descriptions') ?: [];

        // If subtasks are provided, envelope the phase dates around them
        if (!empty($subtaskNames)) {
            $minStart = null;
            $maxEnd = null;
            foreach ($subtaskNames as $index => $subName) {
                if (empty(trim($subName))) continue;
                $sDate = $subtaskStarts[$index] ?? date('Y-m-d');
                $eDate = $subtaskEnds[$index] ?? date('Y-m-d');
                if ($minStart === null || $sDate < $minStart) {
                    $minStart = $sDate;
                }
                if ($maxEnd === null || $eDate > $maxEnd) {
                    $maxEnd = $eDate;
                }
            }
            if ($minStart !== null) {
                $start = $minStart;
            }
            if ($maxEnd !== null) {
                $end = $maxEnd;
            }
        }
        
        $phaseModel->insert([
            'project_id'  => $project_id,
            'name'        => $name,
            'description' => $description,
            'start'       => $start,
            'end'         => $end,
            'status'      => $status,
            'sequence'    => $nextSeq
        ]);

        $phase_id = $phaseModel->getInsertID();

        if (!empty($subtaskNames) && $phase_id) {
            $subtaskModel = new \App\Models\SubtaskModel();
            $seq = 0;
            foreach ($subtaskNames as $index => $subName) {
                if (empty(trim($subName))) continue;
                $subtaskModel->insert([
                    'phase_id'    => $phase_id,
                    'name'        => trim($subName),
                    'description' => $subtaskDescriptions[$index] ?? '',
                    'start'       => $subtaskStarts[$index] ?? date('Y-m-d'),
                    'end'         => $subtaskEnds[$index] ?? date('Y-m-d'),
                    'status'      => $subtaskStatuses[$index] ?? 'backlog',
                    'sequence'    => $seq++
                ]);
            }
        }

        $this->logProjectActivity($project_id, "Phase Added: '{$name}' (Status: " . strtoupper($status) . ")", $oldState);
        $this->updateProjectProgress($project_id);
        
        return $this->response->setJSON(['status' => 'success']);
    }

    public function updatePhaseAjax($project_id, $id) {
        $phaseModel = new PhaseModel();
        $phase = $phaseModel->find($id);
        if (!$phase || $phase['project_id'] !== $project_id) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Phase not found.']);
        }
        $oldState = $this->getProjectState($project_id);
        
        $name = $this->request->getPost('name');
        $description = $this->request->getPost('description') ?: '';
        $start = $this->request->getPost('start') ?: date('Y-m-d');
        $end = $this->request->getPost('end') ?: date('Y-m-d');
        $status = $this->request->getPost('status') ?: 'backlog';
        if (empty($name)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Phase Name is required.']);
        }
        
        $subtaskModel = new \App\Models\SubtaskModel();
        $subtasks = $subtaskModel->where('phase_id', $id)->findAll();
        if (count($subtasks) > 0) {
            $minStart = null;
            $maxEnd = null;
            foreach ($subtasks as $sub) {
                if ($minStart === null || $sub['start'] < $minStart) {
                    $minStart = $sub['start'];
                }
                if ($maxEnd === null || $sub['end'] > $maxEnd) {
                    $maxEnd = $sub['end'];
                }
            }
            $start = $minStart ?: $start;
            $end = $maxEnd ?: $end;
        }

        $phaseModel->update($id, [
            'name'        => $name,
            'description' => $description,
            'start'       => $start,
            'end'         => $end,
            'status'      => $status
        ]);

        $notes = [];
        if ($phase['name'] !== $name) $notes[] = "renamed to '{$name}'";
        if ($phase['status'] !== $status) $notes[] = "status changed to " . strtoupper($status);
        if ($phase['start'] !== $start || $phase['end'] !== $end) $notes[] = "dates updated to {$start} -> {$end}";
        $noteStr = !empty($notes) ? "Phase '{$phase['name']}' updated: " . implode(', ', $notes) : "Phase '{$phase['name']}' updated";
        
        $this->logProjectActivity($project_id, $noteStr, $oldState);
        $this->updateProjectProgress($project_id);
        
        return $this->response->setJSON(['status' => 'success']);
    }

    public function deletePhaseAjax($id) {
        $phaseModel = new PhaseModel();
        $phase = $phaseModel->find($id);
        if ($phase) {
            $project_id = $phase['project_id'];
            $oldState = $this->getProjectState($project_id);
            $phaseModel->delete($id);
            $this->logProjectActivity($project_id, "Phase Deleted: '{$phase['name']}'", $oldState);
            $this->updateProjectProgress($project_id);
            return $this->response->setJSON(['status' => 'success']);
        }
        return $this->response->setJSON(['status' => 'error', 'message' => 'Phase not found.']);
    }

    public function reorderPhasesAjax($project_id) {
        $phaseModel = new PhaseModel();
        $order = $this->request->getJSON(true);
        if (is_array($order)) {
            foreach ($order as $seq => $id) {
                $phaseModel->update($id, ['sequence' => $seq]);
            }
            $this->logProjectActivity($project_id, "Phases reordered");
            return $this->response->setJSON(['status' => 'success']);
        }
        return $this->response->setJSON(['status' => 'error', 'message' => 'Invalid data.']);
    }

    public function assignResourceAjax($project_id) {
        $resource_id = $this->request->getPost('resource_id');
        if (empty($resource_id)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Resource ID is required.']);
        }
        $db = \Config\Database::connect();
        $exists = $db->table('resource_projects')
            ->where('resource_id', $resource_id)
            ->where('project_id', $project_id)
            ->countAllResults();
        if ($exists === 0) {
            $db->table('resource_projects')->insert([
                'resource_id' => $resource_id,
                'project_id'  => $project_id
            ]);
            $resourceModel = new ResourceModel();
            $res = $resourceModel->find($resource_id);
            $resName = $res ? $res['name'] : 'Unknown Resource';
            $this->logProjectActivity($project_id, "Resource Assigned: {$resName}");
        }
        return $this->response->setJSON(['status' => 'success']);
    }

    public function unassignResourceAjax($project_id) {
        $resource_id = $this->request->getPost('resource_id');
        if (empty($resource_id)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Resource ID is required.']);
        }
        $db = \Config\Database::connect();
        $db->table('resource_projects')
            ->where('resource_id', $resource_id)
            ->where('project_id', $project_id)
            ->delete();
        $resourceModel = new ResourceModel();
        $res = $resourceModel->find($resource_id);
        $resName = $res ? $res['name'] : 'Unknown Resource';
        $this->logProjectActivity($project_id, "Resource Unassigned: {$resName}");
        return $this->response->setJSON(['status' => 'success']);
    }

    public function assignSquadAjax($project_id) {
        $squad_id = $this->request->getPost('squad_id');
        $projectModel = new ProjectModel();
        $project = $projectModel->find($project_id);
        if (!$project) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Project not found.']);
        }

        $projectModel->update($project_id, ['squad' => $squad_id ?: null]);

        $db = \Config\Database::connect();
        
        // Find PM resource IDs currently assigned to this project
        $pmResourceIds = $db->table('resource_projects rp')
            ->select('rp.resource_id')
            ->join('resources r', 'rp.resource_id = r.id')
            ->where('rp.project_id', $project_id)
            ->where('r.role', 'PM')
            ->get()
            ->getResultArray();
        $pmIds = array_column($pmResourceIds, 'resource_id');

        // 1. Remove all existing resources assigned to this project, EXCEPT PMs
        $deleteQuery = $db->table('resource_projects')->where('project_id', $project_id);
        if (!empty($pmIds)) {
            $deleteQuery->whereNotIn('resource_id', $pmIds);
        }
        $deleteQuery->delete();

        // Get all PM resource IDs in the system to protect them from task/phase unassignment
        $allPmResources = $db->table('resources')->where('role', 'PM')->select('id')->get()->getResultArray();
        $allPmIds = array_column($allPmResources, 'id');

        // 2. Set resource_id = null (unassigned) for all phases of this project, EXCEPT PMs
        $phaseUpdate = $db->table('project_phases')->where('project_id', $project_id);
        if (!empty($allPmIds)) {
            $phaseUpdate->whereNotIn('resource_id', $allPmIds);
        }
        $phaseUpdate->update(['resource_id' => null]);

        // 3. Set resource_id = null (unassigned) for all subtasks of all phases of this project, EXCEPT PMs
        $phaseIds = $db->table('project_phases')->where('project_id', $project_id)->select('id')->get()->getResultArray();
        $phaseIdList = array_column($phaseIds, 'id');
        if (!empty($phaseIdList)) {
            $subtaskUpdate = $db->table('project_phase_subtasks')->whereIn('phase_id', $phaseIdList);
            if (!empty($allPmIds)) {
                $subtaskUpdate->whereNotIn('resource_id', $allPmIds);
            }
            $subtaskUpdate->update(['resource_id' => null]);
        }

        $squadName = 'None';
        
        if (!empty($squad_id)) {
            $squadModel = new \App\Models\SquadModel();
            $squad = $squadModel->find($squad_id);
            if ($squad) {
                $squadName = $squad['name'];
                $members = $db->table('squad_members')->where('squad_id', $squad_id)->get()->getResultArray();
                foreach ($members as $m) {
                    $db->table('resource_projects')->insert([
                        'resource_id' => $m['resource_id'],
                        'project_id'  => $project_id
                    ]);
                }
            }
        }

        self::recalculateAllResourceUtilizations();

        $this->logProjectActivity($project_id, "Squad Assigned: {$squadName} (squad members auto-assigned, previous members and task assignments reset)");
        return $this->response->setJSON(['status' => 'success']);
    }

    public function assignPhaseResourceAjax($project_id, $phase_id) {
        $resource_id = $this->request->getPost('resource_id');
        $phaseModel = new PhaseModel();
        $phase = $phaseModel->find($phase_id);
        if (!$phase || $phase['project_id'] !== $project_id) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Phase not found.']);
        }
        
        $phaseModel->update($phase_id, ['resource_id' => $resource_id ?: null]);
        
        // Log activity
        $resourceModel = new ResourceModel();
        $resName = 'None';
        if (!empty($resource_id)) {
            $res = $resourceModel->find($resource_id);
            if ($res) $resName = $res['name'];
        }
        $this->logProjectActivity($project_id, "Resource '{$resName}' assigned to Phase '{$phase['name']}'");
        
        return $this->response->setJSON(['status' => 'success']);
    }

    public function assignSubtaskResourceAjax($project_id, $subtask_id) {
        $resource_id = $this->request->getPost('resource_id');
        $subtaskModel = new \App\Models\SubtaskModel();
        $subtask = $subtaskModel->find($subtask_id);
        if (!$subtask) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Subtask not found.']);
        }
        
        $subtaskModel->update($subtask_id, ['resource_id' => $resource_id ?: null]);
        
        self::recalculateAllResourceUtilizations();
        
        // Log activity
        $resourceModel = new ResourceModel();
        $resName = 'None';
        if (!empty($resource_id)) {
            $res = $resourceModel->find($resource_id);
            if ($res) $resName = $res['name'];
        }
        $this->logProjectActivity($project_id, "Resource '{$resName}' assigned to Subtask '{$subtask['name']}'");
        
        return $this->response->setJSON(['status' => 'success']);
    }

    public function createDependencyAjax($project_id) {
        $depModel = new DependencyModel();
        $dep_project_id = $this->request->getPost('dep_project_id');
        $type = $this->request->getPost('type') ?: 'blocks';
        $isOthers = ($type === 'others');
        if ($isOthers) {
            $type = $this->request->getPost('custom_type') ?: 'others';
        }
        $type = substr($type, 0, 20);
        
        if (empty($dep_project_id)) {
            if (!$isOthers) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Dependent project is required.']);
            }
            $dep_project_name = $this->request->getPost('dep_project_name') ?: 'External Dependency';
            $dep_status = '—';
        } else {
            $projectModel = new ProjectModel();
            $depProj = $projectModel->find($dep_project_id);
            if (!$depProj) {
                return $this->response->setJSON(['status' => 'error', 'message' => 'Selected project does not exist.']);
            }
            $dep_project_name = $depProj['name'];
            $dep_status = $depProj['status'];
        }
        
        $depModel->insert([
            'project_id'       => $project_id,
            'dep_project_id'   => $dep_project_id ?: '',
            'dep_project_name' => $dep_project_name,
            'type'             => $type,
            'status'           => $dep_status
        ]);

        $this->logProjectActivity($project_id, "Added Dependency: " . ($type === 'depends-on' ? "Depends on" : ($type === 'blocks' ? "Blocks" : $type)) . " '{$dep_project_name}'");
        
        return $this->response->setJSON(['status' => 'success']);
    }

    public function deleteDependencyAjax($id) {
        $depModel = new DependencyModel();
        $dep = $depModel->find($id);
        if ($dep) {
            $project_id = $dep['project_id'];
            $depModel->delete($id);
            $this->logProjectActivity($project_id, "Removed Dependency on '{$dep['dep_project_name']}'");
            return $this->response->setJSON(['status' => 'success']);
        }
        return $this->response->setJSON(['status' => 'error', 'message' => 'Dependency not found.']);
    }

    public function createEscalationAjax($project_id) {
        $escalationModel = new EscalationModel();
        $level = $this->request->getPost('level') ?: 1;
        $to = $this->request->getPost('to_recipient') ?: '';
        $note = $this->request->getPost('note') ?: '';
        $recipientEmail = $this->request->getPost('recipient_email') ?: '';
        $sendEmail = $this->request->getPost('send_email') === '1';
        
        if (empty($to) || empty($note)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Recipient and Note are required.']);
        }
        
        $escalationModel->insert([
            'project_id'   => $project_id,
            'date'         => date('Y-m-d'),
            'level'        => $level,
            'note'         => $note,
            'to_recipient' => $to
        ]);

        $activityMsg = "Added Escalation Level {$level} to {$to}";
        if ($sendEmail && !empty($recipientEmail)) {
            $activityMsg .= " (Email sent to {$recipientEmail})";
            
            try {
                $emailService = \Config\Services::email();
                $emailService->setFrom('system@nexus.com', 'Nexus System');
                $emailService->setTo($recipientEmail);
                $emailService->setSubject("Escalation Alert - Project: " . strtoupper($project_id));
                $emailService->setMessage("An escalation Level {$level} has been assigned to {$to}.\n\nDetails: {$note}");
                $emailService->send();
            } catch (\Exception $e) {
                // Ignore SMTP configuration issues
            }
        }

        $this->logProjectActivity($project_id, $activityMsg);
        
        return $this->response->setJSON(['status' => 'success']);
    }

    public function deleteEscalationAjax($id) {
        $escalationModel = new EscalationModel();
        $esc = $escalationModel->find($id);
        if ($esc) {
            $project_id = $esc['project_id'];
            $escalationModel->delete($id);
            $this->logProjectActivity($project_id, "Resolved/Removed Escalation (Level {$esc['level']} to {$esc['to_recipient']})");
            return $this->response->setJSON(['status' => 'success']);
        }
        return $this->response->setJSON(['status' => 'error', 'message' => 'Escalation not found.']);
    }

    public function createRiskAjax($project_id) {
        $riskModel = new RiskModel();
        $title = $this->request->getPost('title');
        $severity = $this->request->getPost('severity') ?: 'medium';
        $type = $this->request->getPost('type') ?: 'risk';
        $mitigation = $this->request->getPost('mitigation') ?: '';
        $owner = $this->request->getPost('owner') ?: '';
        $notificationEmail = $this->request->getPost('notification_email') ?: '';
        $sendEmail = $this->request->getPost('send_email') === '1';
        
        if (empty($title)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Title is required.']);
        }
        
        $riskModel->insert([
            'id'         => uniqid('risk_', true),
            'project_id' => $project_id,
            'title'      => $title,
            'severity'   => $severity,
            'type'       => $type,
            'mitigation' => $mitigation,
            'owner'      => $owner
        ]);

        $activityMsg = "Added Risk/Issue: '{$title}' (Severity: " . strtoupper($severity) . ")";
        if ($sendEmail && !empty($notificationEmail)) {
            $activityMsg .= " (Email sent to {$notificationEmail})";
            
            // Safe dispatch attempt
            try {
                $emailService = \Config\Services::email();
                $emailService->setFrom('system@nexus.com', 'Nexus System');
                $emailService->setTo($notificationEmail);
                $emailService->setSubject("Project Risk/Issue Alert - Project: " . strtoupper($project_id));
                $emailService->setMessage("A new risk/issue has been registered.\n\nTitle: {$title}\nSeverity: " . strtoupper($severity) . "\nType: " . strtoupper($type) . "\nOwner: {$owner}\nMitigation: {$mitigation}");
                $emailService->send();
            } catch (\Exception $e) {
                // Ignore SMTP configuration issues
            }
        }

        $this->logProjectActivity($project_id, $activityMsg);
        
        return $this->response->setJSON(['status' => 'success']);
    }

    public function deleteRiskAjax($id) {
        $riskModel = new RiskModel();
        $risk = $riskModel->find($id);
        if ($risk) {
            $project_id = $risk['project_id'];
            $riskModel->delete($id);
            $this->logProjectActivity($project_id, "Removed Risk/Issue: '{$risk['title']}'");
            return $this->response->setJSON(['status' => 'success']);
        }
        return $this->response->setJSON(['status' => 'error', 'message' => 'Risk/Issue not found.']);
    }

    public function updateEscalationAjax($project_id, $id) {
        $escalationModel = new EscalationModel();
        $esc = $escalationModel->find($id);
        if (!$esc || $esc['project_id'] !== $project_id) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Escalation not found.']);
        }
        
        $level = $this->request->getPost('level') ?: 1;
        $to = $this->request->getPost('to_recipient') ?: '';
        $note = $this->request->getPost('note') ?: '';
        $status = $this->request->getPost('status') ?: 'active';
        $reason = $this->request->getPost('reason') ?: '';
        
        if (empty($to) || empty($note)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Recipient and Note are required.']);
        }
        
        $escalationModel->update($id, [
            'level'        => $level,
            'note'         => $note,
            'to_recipient' => $to,
            'status'       => $status,
            'reason'       => $reason
        ]);
        
        $notes = [];
        if ($esc['level'] != $level) $notes[] = "level updated to {$level}";
        if ($esc['to_recipient'] !== $to) $notes[] = "recipient updated to {$to}";
        if ($esc['status'] !== $status) $notes[] = "status updated to " . strtoupper($status);
        if (!empty($reason) && $esc['reason'] !== $reason) $notes[] = "reason updated to '{$reason}'";
        
        $activityMsg = "Updated Escalation to {$to}: " . (empty($notes) ? "no changes" : implode(', ', $notes));
        $this->logProjectActivity($project_id, $activityMsg);
        
        return $this->response->setJSON(['status' => 'success']);
    }

    public function updateRiskAjax($project_id, $id) {
        $riskModel = new RiskModel();
        $risk = $riskModel->find($id);
        if (!$risk || $risk['project_id'] !== $project_id) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Risk/Issue not found.']);
        }
        
        $title = $this->request->getPost('title');
        $severity = $this->request->getPost('severity') ?: 'medium';
        $type = $this->request->getPost('type') ?: 'risk';
        $mitigation = $this->request->getPost('mitigation') ?: '';
        $owner = $this->request->getPost('owner') ?: '';
        $status = $this->request->getPost('status') ?: 'active';
        $reason = $this->request->getPost('reason') ?: '';
        
        if (empty($title)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Title is required.']);
        }
        
        $riskModel->update($id, [
            'title'      => $title,
            'severity'   => $severity,
            'type'       => $type,
            'mitigation' => $mitigation,
            'owner'      => $owner,
            'status'     => $status,
            'reason'     => $reason
        ]);
        
        $notes = [];
        if ($risk['title'] !== $title) $notes[] = "title updated to '{$title}'";
        if ($risk['severity'] !== $severity) $notes[] = "severity updated to " . strtoupper($severity);
        if ($risk['status'] !== $status) $notes[] = "status updated to " . strtoupper($status);
        if (!empty($reason) && $risk['reason'] !== $reason) $notes[] = "reason updated to '{$reason}'";
        
        $activityMsg = "Updated Risk/Issue '{$title}': " . (empty($notes) ? "no changes" : implode(', ', $notes));
        $this->logProjectActivity($project_id, $activityMsg);
        
        return $this->response->setJSON(['status' => 'success']);
    }

    public function createActionAjax($project_id) {
        $actionModel = new ActionItemModel();
        $title = $this->request->getPost('title');
        $owner = $this->request->getPost('owner') ?: '';
        $due = $this->request->getPost('due') ?: date('Y-m-d');
        
        if (empty($title)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Title is required.']);
        }
        
        $actionModel->insert([
            'id'         => uniqid('action_', true),
            'project_id' => $project_id,
            'title'      => $title,
            'owner'      => $owner,
            'due'        => $due,
            'done'       => 0
        ]);

        $this->logProjectActivity($project_id, "Added Action Item: '{$title}' (Due: {$due})");
        
        return $this->response->setJSON(['status' => 'success']);
    }

    public function deleteActionAjax($id) {
        $actionModel = new ActionItemModel();
        $action = $actionModel->find($id);
        if ($action) {
            $project_id = $action['project_id'];
            $actionModel->delete($id);
            $this->logProjectActivity($project_id, "Removed Action Item: '{$action['title']}'");
            return $this->response->setJSON(['status' => 'success']);
        }
        return $this->response->setJSON(['status' => 'error', 'message' => 'Action item not found.']);
    }

    private function logProjectActivity($projectId, $note, $oldState = null)
    {
        $projectModel = new ProjectModel();
        $project = $projectModel->find($projectId);
        if ($project) {
            $statusHistoryModel = new StatusHistoryModel();
            $statusHistoryModel->insert([
                'project_id' => $projectId,
                'date'       => date('Y-m-d'),
                'status'     => $project['status'],
                'note'       => $note
            ]);

            // Track Chronicle & Compare Snapshots
            $this->logChronicleAndCompare($projectId, $note, $oldState);
        }
    }

    private function getProjectState($projectId)
    {
        $db = \Config\Database::connect();
        
        $project = $db->table('projects')->where('id', $projectId)->get()->getRowArray();
        if (!$project) {
            return [];
        }

        $phasesCount = $db->table('project_phases')->where('project_id', $projectId)->countAllResults();
        
        $subtasksCount = 0;
        $phases = $db->table('project_phases')->where('project_id', $projectId)->get()->getResultArray();
        foreach ($phases as $ph) {
            $subtasksCount += $db->table('project_phase_subtasks')->where('phase_id', $ph['id'])->countAllResults();
        }

        $actionsCount = $db->table('project_action_items')->where('project_id', $projectId)->countAllResults();
        $risksCount = $db->table('project_risks')->where('project_id', $projectId)->countAllResults();
        $escalationsCount = $db->table('project_escalations')->where('project_id', $projectId)->countAllResults();
        $resourcesCount = $db->table('resource_projects')->where('project_id', $projectId)->countAllResults();

        return [
            'status'            => $project['status'],
            'progress'          => (int)$project['progress'],
            'phases_count'      => $phasesCount,
            'subtasks_count'    => $subtasksCount,
            'action_items_count'=> $actionsCount,
            'risks_count'       => $risksCount,
            'escalations_count' => $escalationsCount,
            'resources_count'   => $resourcesCount,
        ];
    }

    private function logChronicleAndCompare($projectId, $note, $oldState = null)
    {
        $newState = $this->getProjectState($projectId);
        if (empty($newState)) {
            return;
        }

        if ($oldState === null) {
            $oldState = [
                'status'             => 'backlog',
                'progress'           => 0,
                'phases_count'       => 0,
                'subtasks_count'     => 0,
                'action_items_count' => 0,
                'risks_count'        => 0,
                'escalations_count'  => 0,
                'resources_count'    => 0,
            ];
        }

        // Generate a professional project management narrative sentence
        $sentence = $this->generateNarrativeSentence($note, $oldState, $newState);

        // Insert into narrative history
        $narrativeModel = new ProjectNarrativeModel();
        $narrativeModel->insert([
            'project_id' => $projectId,
            'date'       => date('Y-m-d'),
            'sentence'   => $sentence,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        // Insert into detailed history for comparison
        $detailedModel = new ProjectDetailedHistoryModel();
        $detailedModel->insert([
            'project_id' => $projectId,
            'activity'   => $note,
            'old_state'  => json_encode($oldState),
            'new_state'  => json_encode($newState),
            'date'       => date('Y-m-d'),
            'created_at' => date('Y-m-d H:i:s')
        ]);
    }

    private function generateNarrativeSentence($note, $oldState, $newState)
    {
        // Default narrative if no specific triggers match
        $sentence = "Project activity recorded: {$note}.";

        // 1. Progress changes
        if ($oldState['progress'] !== $newState['progress']) {
            $diff = $newState['progress'] - $oldState['progress'];
            if ($diff > 0) {
                $positives = [
                    "Project progress advanced from {$oldState['progress']}% to {$newState['progress']}%, reflecting completed deliverables and milestone achievement.",
                    "Overall completion updated to {$newState['progress']}% (up {$diff}pp), confirming on-track execution against the project baseline.",
                    "Progress metric increased by {$diff} percentage points to {$newState['progress']}%, consistent with the current delivery plan."
                ];
                $sentence = $positives[array_rand($positives)];
            } else {
                $absDiff = abs($diff);
                $negatives = [
                    "Project progress was recalibrated from {$oldState['progress']}% to {$newState['progress']}% following a scope or task adjustment.",
                    "Completion percentage revised to {$newState['progress']}% (a reduction of {$absDiff}pp) to accurately reflect the updated project scope.",
                    "Progress adjusted downward by {$absDiff} percentage points to {$newState['progress']}%, incorporating revised deliverable estimates."
                ];
                $sentence = $negatives[array_rand($negatives)];
            }
            return $sentence;
        }

        // 2. Status / Health changes
        if ($oldState['status'] !== $newState['status']) {
            $statusStr = strtoupper(str_replace('-', ' ', $newState['status']));
            $oldStatusStr = strtoupper(str_replace('-', ' ', $oldState['status']));
            if (in_array($newState['status'], ['blocked', 'at-risk', 'delayed'])) {
                $warnings = [
                    "Project health status changed from {$oldStatusStr} to {$statusStr}. Immediate attention is required to address the identified impediments and restore delivery momentum.",
                    "Status escalated to {$statusStr}. The project team is directed to convene, identify root causes, and execute corrective action plans without delay.",
                    "Health indicator updated: {$oldStatusStr} → {$statusStr}. Risk mitigation measures and stakeholder communication should be initiated promptly."
                ];
                $sentence = $warnings[array_rand($warnings)];
            } else {
                $gains = [
                    "Project status updated from {$oldStatusStr} to {$statusStr}, reflecting successful resolution of prior impediments and resumed delivery trajectory.",
                    "Health reclassified as {$statusStr} following effective remediation efforts. The project is back on track with the approved schedule and budget.",
                    "Status transition recorded: {$oldStatusStr} → {$statusStr}. Delivery confidence has been restored; teams are aligned to the current project baseline."
                ];
                $sentence = $gains[array_rand($gains)];
            }
            return $sentence;
        }

        // 3. Phase Added/Deleted
        if ($oldState['phases_count'] !== $newState['phases_count']) {
            if ($newState['phases_count'] > $oldState['phases_count']) {
                $sentence = "A new project phase has been added to the work breakdown structure, expanding the delivery scope from {$oldState['phases_count']} to {$newState['phases_count']} phases. The project plan and timeline should be reviewed accordingly.";
            } else {
                $sentence = "A project phase has been removed from the schedule. The work breakdown structure now contains {$newState['phases_count']} phases. Impacted dependencies and resource allocations have been updated.";
            }
            return $sentence;
        }

        // 4. Subtasks added/deleted
        if ($oldState['subtasks_count'] !== $newState['subtasks_count']) {
            if ($newState['subtasks_count'] > $oldState['subtasks_count']) {
                $sentence = "A new subtask has been added to the project, increasing the total task count to {$newState['subtasks_count']}. The phase completion metrics have been updated to reflect this change.";
            } else {
                $sentence = "A subtask has been removed from the project scope. The total task count is now {$newState['subtasks_count']}. Phase progress calculations have been revised accordingly.";
            }
            return $sentence;
        }

        // 5. Risks added/deleted
        if ($oldState['risks_count'] !== $newState['risks_count']) {
            if ($newState['risks_count'] > $oldState['risks_count']) {
                $sentence = "A new risk has been identified and logged in the risk register. The project risk count now stands at {$newState['risks_count']}. A mitigation plan should be assigned and tracked to closure.";
            } else {
                $sentence = "A risk item has been closed and removed from the active risk register. Total active risks: {$newState['risks_count']}. Risk mitigation efforts are proving effective.";
            }
            return $sentence;
        }

        // 6. Escalations added/deleted
        if ($oldState['escalations_count'] !== $newState['escalations_count']) {
            if ($newState['escalations_count'] > $oldState['escalations_count']) {
                $sentence = "A formal escalation has been raised, bringing the total active escalations to {$newState['escalations_count']}. Stakeholder engagement and resolution ownership must be established immediately to prevent delivery impact.";
            } else {
                $sentence = "An escalation has been resolved and closed. Active escalation count reduced to {$newState['escalations_count']}. Follow-up actions should be documented to prevent recurrence.";
            }
            return $sentence;
        }

        // 7. Assigned resources added/deleted
        if ($oldState['resources_count'] !== $newState['resources_count']) {
            if ($newState['resources_count'] > $oldState['resources_count']) {
                $sentence = "A resource has been assigned to the project, bringing the total allocated headcount to {$newState['resources_count']}. Resource allocation and role responsibilities should be confirmed with the project team.";
            } else {
                $sentence = "A resource has been removed from the project assignment. Current allocated headcount: {$newState['resources_count']}. Capacity planning should be reviewed to ensure delivery commitments are maintained.";
            }
            return $sentence;
        }

        // 8. Specific notes triggers
        if (stripos($note, 'action item') !== false) {
            $sentence = "An action item has been updated. Ownership and target completion dates should be verified to ensure accountability and on-time resolution.";
        } elseif (stripos($note, 'dependency') !== false) {
            $sentence = "A project dependency has been modified. Impacted workstreams and downstream timelines should be reviewed to ensure alignment with the current delivery schedule.";
        } elseif (stripos($note, 'document') !== false) {
            $sentence = "Project documentation has been updated. All relevant stakeholders should be notified to ensure they are referencing the latest approved version.";
        }

        return $sentence;
    }

    public function updateHealthAjax($id) {
        $projectModel = new ProjectModel();
        $project = $projectModel->find($id);
        if (!$project) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Project not found.']);
        }
        
        $oldState = $this->getProjectState($id);
        
        $health = $this->request->getPost('health');
        $description = $this->request->getPost('description');
        
        $projectModel->update($id, [
            'health' => $health,
            'status' => $health,
            'description' => $description
        ]);
        
        $notes = [];
        if ($health !== $project['health']) {
            $notes[] = "Health summary and Status updated to '{$health}'";
        }
        if ($description !== $project['description']) {
            $notes[] = "Description updated";
        }
        
        if (!empty($notes)) {
            $this->logProjectActivity($id, implode('; ', $notes), $oldState);
        }
        
        return $this->response->setJSON(['status' => 'success']);
    }

    private function updateProjectProgress($projectId)
    {
        $phaseModel = new PhaseModel();
        $projectModel = new ProjectModel();
        $subtaskModel = new \App\Models\SubtaskModel();
        $phases = $phaseModel->where('project_id', $projectId)->findAll();
        $totalProgressSum = 0;
        foreach ($phases as $ph) {
            $subtasks = $subtaskModel->where('phase_id', $ph['id'])->findAll();
            if (count($subtasks) > 0) {
                $comp = 0;
                foreach ($subtasks as $sub) {
                    if (in_array(strtolower($sub['status']), ['complete', 'completed', 'done'])) {
                        $comp++;
                    }
                }
                $totalProgressSum += ($comp / count($subtasks));
            } else {
                if (in_array(strtolower($ph['status']), ['complete', 'completed', 'done'])) {
                    $totalProgressSum += 1.0;
                }
            }
        }
        $progress = count($phases) > 0 ? (int)(($totalProgressSum / count($phases)) * 100) : 0;
        $projectModel->update($projectId, ['progress' => $progress]);
    }

    private static function getWorkingDaysCount($startDateStr, $endDateStr, $workDaysPerWeek)
    {
        $start = new \DateTime($startDateStr);
        $end = new \DateTime($endDateStr);
        if ($start > $end) {
            return 0;
        }

        $workDays = 0;
        $period = new \DatePeriod(
            $start,
            new \DateInterval('P1D'),
            (clone $end)->modify('+1 day')
        );

        foreach ($period as $date) {
            $w = (int)$date->format('w'); // 0 = Sunday, 6 = Saturday
            if ($workDaysPerWeek == 5) {
                if ($w !== 0 && $w !== 6) {
                    $workDays++;
                }
            } elseif ($workDaysPerWeek == 6) {
                if ($w !== 0) {
                    $workDays++;
                }
            } else {
                $workDays++;
            }
        }

        return $workDays;
    }

    public static function recalculateAllResourceUtilizations()
    {
        $db = \Config\Database::connect();
        $settingsModel = new \App\Models\SettingsModel();
        
        $dailyHours = (float)$settingsModel->getSetting('daily_work_hours', 8);
        $workDaysPerWeek = (int)$settingsModel->getSetting('work_days_per_week', 5);
        $capacity = $dailyHours * $workDaysPerWeek;

        $resourceModel = new \App\Models\ResourceModel();
        $resources = $resourceModel->findAll();

        foreach ($resources as $r) {
            $subtasks = $db->table('project_phase_subtasks')
                           ->where('resource_id', $r['id'])
                           ->get()
                           ->getResultArray();

            $totalWeeklyLoad = 0.0;
            foreach ($subtasks as $sub) {
                // Skip completed tasks
                if (in_array(strtolower($sub['status'] ?? ''), ['complete', 'completed', 'done'])) {
                    continue;
                }

                $start = $sub['start'];
                $end = $sub['end'];
                $taskHours = (float)$sub['task_hours'];

                if (!empty($start) && !empty($end)) {
                    $workingDays = self::getWorkingDaysCount($start, $end, $workDaysPerWeek);
                    $weeks = max(1.0, ceil($workingDays / (float)$workDaysPerWeek));
                } else {
                    $weeks = 1.0;
                }

                $totalWeeklyLoad += $taskHours / $weeks;
            }

            $utilization = 0;
            if ($capacity > 0) {
                $utilization = round(($totalWeeklyLoad / $capacity) * 100);
            }

            $resourceModel->update($r['id'], [
                'utilization' => $utilization
            ]);
        }
    }

    private function addWorkDays($startDateStr, $daysToAdd, $workDaysPerWeek)
    {
        $current = new \DateTime($startDateStr);
        if ($daysToAdd <= 0) {
            return $current->format('Y-m-d');
        }

        $added = 0;
        $target = $daysToAdd - 1;

        while ($added < $target) {
            $current->modify('+1 day');
            $w = (int)$current->format('w'); // 0 = Sunday, 6 = Saturday
            if ($workDaysPerWeek == 5) {
                if ($w === 0 || $w === 6) {
                    continue;
                }
            } elseif ($workDaysPerWeek == 6) {
                if ($w === 0) {
                    continue;
                }
            }
            $added++;
        }
        return $current->format('Y-m-d');
    }

    private function getNextWorkDay($dateStr, $workDaysPerWeek)
    {
        $date = new \DateTime($dateStr);
        $date->modify('+1 day');
        if ($workDaysPerWeek == 5) {
            $w = (int)$date->format('w');
            if ($w === 6) {
                $date->modify('+2 days');
            } elseif ($w === 0) {
                $date->modify('+1 day');
            }
        } elseif ($workDaysPerWeek == 6) {
            $w = (int)$date->format('w');
            if ($w === 0) {
                $date->modify('+1 day');
            }
        }
        return $date->format('Y-m-d');
    }

    private function recalculateSubtaskAndPhaseDates($phaseId)
    {
        $phaseModel = new PhaseModel();
        $subtaskModel = new \App\Models\SubtaskModel();
        $settingsModel = new \App\Models\SettingsModel();
        
        $phase = $phaseModel->find($phaseId);
        if (!$phase) {
            return;
        }
        
        $workDaysPerWeek = (int)$settingsModel->getSetting('work_days_per_week', 5);
        $baseStartDate = $phase['start'] ?: date('Y-m-d');
        
        $subtasks = $subtaskModel->where('phase_id', $phaseId)
                                 ->orderBy('sequence', 'ASC')
                                 ->orderBy('id', 'ASC')
                                 ->findAll();
                                 
        if (count($subtasks) > 0) {
            $minStart = null;
            $maxEnd = null;
            
            foreach ($subtasks as $sub) {
                // Keep the subtask's own start date if available, otherwise default to phase start date
                $subStart = $sub['start'] ?: $baseStartDate;
                
                $duration = (int)ceil((float)$sub['man_days']);
                if ($duration <= 0) {
                    $subEnd = $subStart;
                } else {
                    $subEnd = $this->addWorkDays($subStart, $duration, $workDaysPerWeek);
                }
                
                $subtaskModel->update($sub['id'], [
                    'start' => $subStart,
                    'end'   => $subEnd
                ]);
                
                if ($minStart === null || $subStart < $minStart) {
                    $minStart = $subStart;
                }
                if ($maxEnd === null || $subEnd > $maxEnd) {
                    $maxEnd = $subEnd;
                }
            }
            
            if ($minStart !== null && $maxEnd !== null) {
                $phaseModel->update($phaseId, [
                    'start' => $minStart,
                    'end'   => $maxEnd
                ]);
            }
        }
    }

    private function recalculatePhaseDates($phaseId)
    {
        $this->recalculateSubtaskAndPhaseDates($phaseId);
    }

    public function createSubtaskAjax($project_id, $phase_id) {
        $oldState = $this->getProjectState($project_id);
        $subtaskModel = new \App\Models\SubtaskModel();
        $name = $this->request->getPost('name');
        $description = $this->request->getPost('description') ?: '';
        
        $phaseModel = new PhaseModel();
        $phase = $phaseModel->find($phase_id);
        $start = $this->request->getPost('start') ?: ($phase ? $phase['start'] : date('Y-m-d'));
        
        $status = $this->request->getPost('status') ?: 'backlog';
        $man_days = $this->request->getPost('man_days') !== null ? (float)$this->request->getPost('man_days') : 0.0;
        $task_hours = $this->request->getPost('task_hours') !== null ? (float)$this->request->getPost('task_hours') : 0.0;
        $resource_id = $this->request->getPost('resource_id') ?: null;

        if (empty($name)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Subtask Name is required.']);
        }

        $settingsModel = new \App\Models\SettingsModel();
        $workDaysPerWeek = (int)$settingsModel->getSetting('work_days_per_week', 5);
        $duration = (int)ceil($man_days);
        if ($duration <= 0) {
            $end = $start;
        } else {
            $end = $this->addWorkDays($start, $duration, $workDaysPerWeek);
        }

        $maxSeq = $subtaskModel->where('phase_id', $phase_id)->selectMax('sequence')->first();
        $nextSeq = isset($maxSeq['sequence']) ? $maxSeq['sequence'] + 1 : 0;

        $subtaskModel->insert([
            'phase_id'    => $phase_id,
            'name'        => $name,
            'description' => $description,
            'start'       => $start,
            'end'         => $end,
            'status'      => $status,
            'sequence'    => $nextSeq,
            'man_days'    => $man_days,
            'task_hours'  => $task_hours,
            'resource_id' => $resource_id
        ]);

        $phaseName = $phase ? $phase['name'] : "ID {$phase_id}";

        $this->recalculateSubtaskAndPhaseDates($phase_id);
        self::recalculateAllResourceUtilizations();
        $this->logProjectActivity($project_id, "Subtask Added: '{$name}' to Phase '{$phaseName}'", $oldState);
        $this->updateProjectProgress($project_id);

        return $this->response->setJSON(['status' => 'success']);
    }

    public function updateSubtaskAjax($project_id, $phase_id, $id) {
        $subtaskModel = new \App\Models\SubtaskModel();
        $subtask = $subtaskModel->find($id);
        if (!$subtask || (int)$subtask['phase_id'] !== (int)$phase_id) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Subtask not found.']);
        }
        $oldState = $this->getProjectState($project_id);

        $name = $this->request->getPost('name');
        $description = $this->request->getPost('description') ?: '';
        $start = $this->request->getPost('start') ?: $subtask['start'];
        $status = $this->request->getPost('status') ?: 'backlog';
        $man_days = $this->request->getPost('man_days') !== null ? (float)$this->request->getPost('man_days') : 0.0;
        $task_hours = $this->request->getPost('task_hours') !== null ? (float)$this->request->getPost('task_hours') : 0.0;
        $resource_id = $this->request->getPost('resource_id') ?: null;

        if (empty($name)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Subtask Name is required.']);
        }

        $settingsModel = new \App\Models\SettingsModel();
        $workDaysPerWeek = (int)$settingsModel->getSetting('work_days_per_week', 5);
        $duration = (int)ceil($man_days);
        if ($duration <= 0) {
            $end = $start;
        } else {
            $end = $this->addWorkDays($start, $duration, $workDaysPerWeek);
        }

        $subtaskModel->update($id, [
            'name'        => $name,
            'description' => $description,
            'start'       => $start,
            'end'         => $end,
            'status'      => $status,
            'man_days'    => $man_days,
            'task_hours'  => $task_hours,
            'resource_id' => $resource_id
        ]);

        $this->recalculateSubtaskAndPhaseDates($phase_id);
        self::recalculateAllResourceUtilizations();
        $this->logProjectActivity($project_id, "Subtask Updated: '{$name}'", $oldState);
        $this->updateProjectProgress($project_id);

        return $this->response->setJSON(['status' => 'success']);
    }

    public function echoCommand($project_id) {
        // Double-check authentication and viewer role restriction
        if (!session()->get('isLoggedIn')) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Unauthorized.']);
        }
        if (session()->get('role') === 'viewer') {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Access Denied: Viewer role is read-only.']);
        }

        $rawCommand = trim($this->request->getPost('command'));
        if (empty($rawCommand)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Command is empty.']);
        }

        // Split by semicolon OR newline
        $commands = preg_split('/[;\n]+/', $rawCommand);
        $results = [];
        $successCount = 0;
        $errorCount = 0;

        $db = \Config\Database::connect();
        $db->transBegin();

        $oldState = $this->getProjectState($project_id);
        $phaseModel = new PhaseModel();
        $subtaskModel = new \App\Models\SubtaskModel();

        // Helper to normalize input dates from d-m-Y or Y-m-d to standard Y-m-d
        $normalizeDate = function ($dateStr) {
            $dateStr = trim($dateStr);
            if (preg_match('/^\d{1,2}-\d{1,2}-\d{4}$/', $dateStr)) {
                $parts = explode('-', $dateStr);
                return sprintf('%04d-%02d-%02d', $parts[2], $parts[1], $parts[0]);
            }
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateStr)) {
                return $dateStr;
            }
            $time = strtotime($dateStr);
            return $time ? date('Y-m-d', $time) : date('Y-m-d');
        };

        $settingsModel = new \App\Models\SettingsModel();
        $workDaysPerWeek = (int)$settingsModel->getSetting('work_days_per_week', 5);
        $dailyHours = (float)$settingsModel->getSetting('daily_work_hours', 8);

        // Command regex patterns
        $createPhaseRegex = '#^Create\s+a\s+phase\s+called\s+\[?([^\]\n]+)\]?\s+for\s+start\s+date\s+on\s+\[?(\d{1,2}-\d{1,2}-\d{4}|\d{4}-\d{2}-\d{2})\]?\s+to\s+end\s*(?:date\s+)?on\s+\[?(\d{1,2}-\d{1,2}-\d{4}|\d{4}-\d{2}-\d{2})\]?\s+with\s+status\s+\[?([^\]\n]+)\]?#i';
        $createSubtaskRegex = '#^Create\s+a\s+subtask\s+on\s+\[?([^\]\n]+)\]?\s+called\s+\[?([^\]\n]+)\]?\s+for\s+start\s+date\s+on\s+\[?(\d{1,2}-\d{1,2}-\d{4}|\d{4}-\d{2}-\d{2})\]?\s+with\s+\[?([\d\.]+)\]?\s+mandays\s+assigned\s+to\s+\[?([^\]\n]*)\]?\s+with\s+status\s+\[?([^\]\n]+)\]?#i';
        $updateRegex = '#^Update\s+\[?([^\]\n]+)\]?\s+start\s+date\s+on\s+\[?(\d{1,2}-\d{1,2}-\d{4}|\d{4}-\d{2}-\d{2})\]?\s+with\s+\[?([\d\.]+)\]?\s+mandays\s+assigned\s+to\s+\[?([^\]\n]*)\]?\s+with\s+status\s+\[?([^\]\n]+)\]?#i';
        $updatePhaseRegex = '#^Update\s+\[?([^\]\n]+)\]?\s+start\s+date\s+on\s+\[?(\d{1,2}-\d{1,2}-\d{4}|\d{4}-\d{2}-\d{2})\]?\s+to\s+end\s*(?:date\s+)?on\s+\[?(\d{1,2}-\d{1,2}-\d{4}|\d{4}-\d{2}-\d{2})\]?\s+with\s+(?:the\s+)?status\s*(?:as\s+)?\[?([^\]\n]+)\]?#i';
        $updateStatusOnlyRegex = '#^Update\s+\[?([^\]\n]+)\]?\s+with\s+(?:the\s+)?status\s*(?:as\s+)?\[?([^\]\n]+)\]?#i';
        $assignResourceRegex = '@^Assign\s+resource\s+\[?([^\]\n]+)\]?\s+to\s+subtask\s+\[?#(\d+)\]?@i';
        $unassignResourceRegex = '@^(?:Unassign|Remove)\s+resource\s+from\s+subtask\s+\[?#(\d+)\]?@i';

        foreach ($commands as $cmd) {
            $cmd = trim($cmd);
            if (empty($cmd)) continue;

            $executed = false;
            $msg = '';

            // 1. Create Phase
            if (preg_match($createPhaseRegex, $cmd, $matches)) {
                $name = trim($matches[1]);
                $start = $normalizeDate($matches[2]);
                $end = $normalizeDate($matches[3]);
                $status = strtolower(trim($matches[4]));

                $maxSeq = $phaseModel->where('project_id', $project_id)->selectMax('sequence')->first();
                $nextSeq = isset($maxSeq['sequence']) ? $maxSeq['sequence'] + 1 : 0;

                $phaseModel->insert([
                    'project_id'  => $project_id,
                    'name'        => $name,
                    'description' => '',
                    'start'       => $start,
                    'end'         => $end,
                    'status'      => $status,
                    'sequence'    => $nextSeq
                ]);

                $this->logProjectActivity($project_id, "Phase Added via Echo: '{$name}' (Status: " . strtoupper($status) . ")", $oldState);
                $msg = "Successfully created phase '{$name}'.";
                $executed = true;
            } 
            // 2. Create Subtask
            elseif (preg_match($createSubtaskRegex, $cmd, $matches)) {
                $phaseSearch = trim($matches[1]);
                $name = trim($matches[2]);
                $start = $normalizeDate($matches[3]);
                $man_days = (float)$matches[4];
                $resourceSearch = trim($matches[5]);
                $status = strtolower(trim($matches[6]));

                $phase = $phaseModel->where('project_id', $project_id)->where('name', $phaseSearch)->first();
                if (!$phase) {
                    $phase = $phaseModel->where('project_id', $project_id)->like('name', $phaseSearch)->first();
                }

                if (!$phase) {
                    $msg = "Error: Phase '{$phaseSearch}' not found in this project.";
                    $errorCount++;
                    $results[] = $msg;
                    continue;
                }

                $resourceId = null;
                if (!empty($resourceSearch) && !in_array(strtolower($resourceSearch), ['unassigned', 'none', 'no one', 'nobody'])) {
                    $assignedResources = $db->table('resources r')
                        ->select('r.*')
                        ->join('resource_projects rp', 'r.id = rp.resource_id')
                        ->where('rp.project_id', $project_id)
                        ->get()
                        ->getResultArray();

                    foreach ($assignedResources as $res) {
                        if (strcasecmp($res['name'], $resourceSearch) === 0) {
                            $resourceId = $res['id'];
                            break;
                        }
                    }
                    if (!$resourceId) {
                        foreach ($assignedResources as $res) {
                            if (stripos($res['name'], $resourceSearch) !== false) {
                                $resourceId = $res['id'];
                                break;
                            }
                        }
                    }
                    if (!$resourceId) {
                        $resourceModel = new \App\Models\ResourceModel();
                        $allRes = $resourceModel->findAll();
                        foreach ($allRes as $res) {
                            if (strcasecmp($res['name'], $resourceSearch) === 0) {
                                $resourceId = $res['id'];
                                break;
                            }
                        }
                        if (!$resourceId) {
                            foreach ($allRes as $res) {
                                if (stripos($res['name'], $resourceSearch) !== false) {
                                    $resourceId = $res['id'];
                                    break;
                                }
                            }
                        }
                    }
                    if (!$resourceId) {
                        $msg = "Error: Resource '{$resourceSearch}' not found.";
                        $errorCount++;
                        $results[] = $msg;
                        continue;
                    }
                }

                $phase_id = $phase['id'];
                $maxSeq = $subtaskModel->where('phase_id', $phase_id)->selectMax('sequence')->first();
                $nextSeq = isset($maxSeq['sequence']) ? $maxSeq['sequence'] + 1 : 0;

                $duration = (int)ceil($man_days);
                if ($duration <= 0) {
                    $end = $start;
                } else {
                    $end = $this->addWorkDays($start, $duration, $workDaysPerWeek);
                }
                $task_hours = $man_days * $dailyHours;

                $subtaskModel->insert([
                    'phase_id'    => $phase_id,
                    'name'        => $name,
                    'description' => '',
                    'start'       => $start,
                    'end'         => $end,
                    'status'      => $status,
                    'sequence'    => $nextSeq,
                    'man_days'    => $man_days,
                    'task_hours'  => $task_hours,
                    'resource_id' => $resourceId
                ]);

                $this->recalculateSubtaskAndPhaseDates($phase_id);
                self::recalculateAllResourceUtilizations();
                $this->logProjectActivity($project_id, "Subtask Added via Echo: '{$name}' to Phase '{$phase['name']}'", $oldState);
                $msg = "Successfully created subtask '{$name}' under phase '{$phase['name']}'.";
                $executed = true;
            }
            // 3. Update Subtask (Dates, Mandays, Resource, Status)
            elseif (preg_match($updateRegex, $cmd, $matches)) {
                $targetName = trim($matches[1]);
                $start = $normalizeDate($matches[2]);
                $man_days = (float)$matches[3];
                $resourceSearch = trim($matches[4]);
                $status = strtolower(trim($matches[5]));

                $phases = $phaseModel->where('project_id', $project_id)->findAll();
                $phaseIds = array_column($phases, 'id');

                $targetSubtask = null;
                if (!empty($phaseIds)) {
                    if (preg_match('/^#(\d+)$/', $targetName, $idMatches)) {
                        $targetSubtask = $subtaskModel->whereIn('phase_id', $phaseIds)->find($idMatches[1]);
                    } else {
                        $targetSubtask = $subtaskModel->whereIn('phase_id', $phaseIds)->where('name', $targetName)->first();
                        if (!$targetSubtask) {
                            $targetSubtask = $subtaskModel->whereIn('phase_id', $phaseIds)->like('name', $targetName)->first();
                        }
                    }
                }

                if ($targetSubtask) {
                    $resourceId = null;
                    if (!empty($resourceSearch) && !in_array(strtolower($resourceSearch), ['unassigned', 'none', 'no one', 'nobody'])) {
                        $assignedResources = $db->table('resources r')
                            ->select('r.*')
                            ->join('resource_projects rp', 'r.id = rp.resource_id')
                            ->where('rp.project_id', $project_id)
                            ->get()
                            ->getResultArray();

                        foreach ($assignedResources as $res) {
                            if (strcasecmp($res['name'], $resourceSearch) === 0) {
                                $resourceId = $res['id'];
                                break;
                            }
                        }
                        if (!$resourceId) {
                            foreach ($assignedResources as $res) {
                                if (stripos($res['name'], $resourceSearch) !== false) {
                                    $resourceId = $res['id'];
                                    break;
                                }
                            }
                        }
                        if (!$resourceId) {
                            $resourceModel = new \App\Models\ResourceModel();
                            $allRes = $resourceModel->findAll();
                            foreach ($allRes as $res) {
                                if (strcasecmp($res['name'], $resourceSearch) === 0) {
                                    $resourceId = $res['id'];
                                    break;
                                }
                            }
                            if (!$resourceId) {
                                foreach ($allRes as $res) {
                                    if (stripos($res['name'], $resourceSearch) !== false) {
                                        $resourceId = $res['id'];
                                        break;
                                    }
                                }
                            }
                        }
                        if (!$resourceId) {
                            $msg = "Error: Resource '{$resourceSearch}' not found.";
                            $errorCount++;
                            $results[] = $msg;
                            continue;
                        }
                    }

                    $duration = (int)ceil($man_days);
                    if ($duration <= 0) {
                        $end = $start;
                    } else {
                        $end = $this->addWorkDays($start, $duration, $workDaysPerWeek);
                    }
                    $task_hours = $man_days * $dailyHours;

                    $subtaskModel->update($targetSubtask['id'], [
                        'start'       => $start,
                        'end'         => $end,
                        'status'      => $status,
                        'man_days'    => $man_days,
                        'task_hours'  => $task_hours,
                        'resource_id' => $resourceId
                    ]);
                    $this->recalculateSubtaskAndPhaseDates($targetSubtask['phase_id']);
                    self::recalculateAllResourceUtilizations();
                    $this->logProjectActivity($project_id, "Subtask Updated via Echo: '{$targetName}'", $oldState);
                    $msg = "Successfully updated subtask '{$targetName}'.";
                    $executed = true;
                } else {
                    $msg = "Error: Subtask '{$targetName}' not found in this project.";
                    $errorCount++;
                    $results[] = $msg;
                    continue;
                }
            }
            // 4. Assign resource to subtask by ID
            elseif (preg_match($assignResourceRegex, $cmd, $matches)) {
                $resourceSearch = trim($matches[1]);
                $subtaskId = (int)$matches[2];

                $phases = $phaseModel->where('project_id', $project_id)->findAll();
                $phaseIds = array_column($phases, 'id');

                $targetSubtask = null;
                if (!empty($phaseIds)) {
                    $targetSubtask = $subtaskModel->whereIn('phase_id', $phaseIds)->find($subtaskId);
                }

                if (!$targetSubtask) {
                    $msg = "Error: Subtask #{$subtaskId} not found in this project.";
                    $errorCount++;
                    $results[] = $msg;
                    continue;
                }

                $resourceId = null;
                if (!empty($resourceSearch) && !in_array(strtolower($resourceSearch), ['unassigned', 'none', 'no one', 'nobody'])) {
                    $assignedResources = $db->table('resources r')
                        ->select('r.*')
                        ->join('resource_projects rp', 'r.id = rp.resource_id')
                        ->where('rp.project_id', $project_id)
                        ->get()
                        ->getResultArray();

                    foreach ($assignedResources as $res) {
                        if (strcasecmp($res['name'], $resourceSearch) === 0) {
                            $resourceId = $res['id'];
                            break;
                        }
                    }
                    if (!$resourceId) {
                        foreach ($assignedResources as $res) {
                            if (stripos($res['name'], $resourceSearch) !== false) {
                                $resourceId = $res['id'];
                                break;
                            }
                        }
                    }
                    if (!$resourceId) {
                        $resourceModel = new \App\Models\ResourceModel();
                        $allRes = $resourceModel->findAll();
                        foreach ($allRes as $res) {
                            if (strcasecmp($res['name'], $resourceSearch) === 0) {
                                $resourceId = $res['id'];
                                break;
                            }
                        }
                        if (!$resourceId) {
                            foreach ($allRes as $res) {
                                if (stripos($res['name'], $resourceSearch) !== false) {
                                    $resourceId = $res['id'];
                                    break;
                                }
                            }
                        }
                    }
                    if (!$resourceId) {
                        $msg = "Error: Resource '{$resourceSearch}' not found.";
                        $errorCount++;
                        $results[] = $msg;
                        continue;
                    }
                }

                $subtaskModel->update($targetSubtask['id'], [
                    'resource_id' => $resourceId
                ]);
                self::recalculateAllResourceUtilizations();
                $this->logProjectActivity($project_id, "Subtask #{$subtaskId} Assigned to '{$resourceSearch}' via Echo", $oldState);
                $msg = "Successfully assigned resource '{$resourceSearch}' to subtask #{$subtaskId}.";
                $executed = true;
            }
            // 5. Unassign resource from subtask by ID
            elseif (preg_match($unassignResourceRegex, $cmd, $matches)) {
                $subtaskId = (int)$matches[1];

                $phases = $phaseModel->where('project_id', $project_id)->findAll();
                $phaseIds = array_column($phases, 'id');

                $targetSubtask = null;
                if (!empty($phaseIds)) {
                    $targetSubtask = $subtaskModel->whereIn('phase_id', $phaseIds)->find($subtaskId);
                }

                if (!$targetSubtask) {
                    $msg = "Error: Subtask #{$subtaskId} not found in this project.";
                    $errorCount++;
                    $results[] = $msg;
                    continue;
                }

                $subtaskModel->update($targetSubtask['id'], [
                    'resource_id' => null
                ]);
                self::recalculateAllResourceUtilizations();
                $this->logProjectActivity($project_id, "Subtask #{$subtaskId} Unassigned via Echo", $oldState);
                $msg = "Successfully unassigned resource from subtask #{$subtaskId}.";
                $executed = true;
            }
            // 6. Update Phase (Dates & Status)
            elseif (preg_match($updatePhaseRegex, $cmd, $matches)) {
                $targetName = trim($matches[1]);
                $start = $normalizeDate($matches[2]);
                $end = $normalizeDate($matches[3]);
                $status = strtolower(trim($matches[4]));

                $targetPhase = $phaseModel->where('project_id', $project_id)->where('name', $targetName)->first();
                if (!$targetPhase) {
                    $targetPhase = $phaseModel->where('project_id', $project_id)->like('name', $targetName)->first();
                }

                if ($targetPhase) {
                    $hasSubtasks = $subtaskModel->where('phase_id', $targetPhase['id'])->countAllResults() > 0;
                    if ($hasSubtasks) {
                        $phaseModel->update($targetPhase['id'], [
                            'status' => $status
                        ]);
                        $this->logProjectActivity($project_id, "Phase Status Updated via Echo: '{$targetName}' to " . strtoupper($status), $oldState);
                        $msg = "Successfully updated phase '{$targetName}' status to {$status}. Dates were not changed because the phase contains subtasks.";
                        $executed = true;
                    } else {
                        $phaseModel->update($targetPhase['id'], [
                            'start'  => $start,
                            'end'    => $end,
                            'status' => $status
                        ]);
                        $this->logProjectActivity($project_id, "Phase Updated via Echo: '{$targetName}'", $oldState);
                        $msg = "Successfully updated phase '{$targetName}'.";
                        $executed = true;
                    }
                } else {
                    $msg = "Error: Phase '{$targetName}' not found in this project.";
                    $errorCount++;
                    $results[] = $msg;
                    continue;
                }
            }
            // 7. Update Status Only
            elseif (preg_match($updateStatusOnlyRegex, $cmd, $matches)) {
                $targetName = trim($matches[1]);
                $status = strtolower(trim($matches[2]));

                $phases = $phaseModel->where('project_id', $project_id)->findAll();
                $phaseIds = array_column($phases, 'id');

                $targetSubtask = null;
                if (!empty($phaseIds)) {
                    if (preg_match('/^#(\d+)$/', $targetName, $idMatches)) {
                        $targetSubtask = $subtaskModel->whereIn('phase_id', $phaseIds)->find($idMatches[1]);
                    } else {
                        $targetSubtask = $subtaskModel->whereIn('phase_id', $phaseIds)->where('name', $targetName)->first();
                        if (!$targetSubtask) {
                            $targetSubtask = $subtaskModel->whereIn('phase_id', $phaseIds)->like('name', $targetName)->first();
                        }
                    }
                }

                if ($targetSubtask) {
                    $subtaskModel->update($targetSubtask['id'], [
                        'status' => $status
                    ]);
                    self::recalculateAllResourceUtilizations();
                    $this->logProjectActivity($project_id, "Subtask Status Updated via Echo: '{$targetName}' to " . strtoupper($status), $oldState);
                    $msg = "Successfully updated subtask '{$targetName}' status to {$status}.";
                    $executed = true;
                } else {
                    $targetPhase = $phaseModel->where('project_id', $project_id)->where('name', $targetName)->first();
                    if (!$targetPhase) {
                        $targetPhase = $phaseModel->where('project_id', $project_id)->like('name', $targetName)->first();
                    }

                    if ($targetPhase) {
                        $phaseModel->update($targetPhase['id'], [
                            'status' => $status
                        ]);
                        $this->logProjectActivity($project_id, "Phase Status Updated via Echo: '{$targetName}' to " . strtoupper($status), $oldState);
                        $msg = "Successfully updated phase '{$targetName}' status to {$status}.";
                        $executed = true;
                    } else {
                        $msg = "Error: Phase or Subtask '{$targetName}' not found in this project.";
                        $errorCount++;
                        $results[] = $msg;
                        continue;
                    }
                }
            }

            if ($executed) {
                $successCount++;
                $results[] = $msg;
            } else {
                $msg = "Error: Command not recognized: '{$cmd}'";
                $errorCount++;
                $results[] = $msg;
            }
        }

        // Recalculate progress & statistics if any command was successful and transaction will commit
        if ($errorCount > 0) {
            $db->transRollback();
            $summaryMessage = implode("\n", $results);
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => "Transaction rolled back due to error(s):\n" . $summaryMessage
            ]);
        } else {
            $db->transCommit();
            if ($successCount > 0) {
                $this->updateProjectProgress($project_id);
            }
            $summaryMessage = implode("\n", $results);
            return $this->response->setJSON([
                'status'  => 'success',
                'message' => empty($summaryMessage) ? 'No commands executed.' : $summaryMessage
            ]);
        }
    }

    public function toggleSubtaskAjax($project_id, $phase_id, $id) {
        $subtaskModel = new \App\Models\SubtaskModel();
        $subtask = $subtaskModel->find($id);
        if (!$subtask) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Subtask not found.']);
        }
        
        $oldState = $this->getProjectState($project_id);
        
        $currentStatus = strtolower($subtask['status']);
        $newStatus = in_array($currentStatus, ['complete', 'completed', 'done']) ? 'backlog' : 'complete';
        
        $subtaskModel->update($id, [
            'status' => $newStatus
        ]);
        
        $this->recalculateSubtaskAndPhaseDates($phase_id);
        self::recalculateAllResourceUtilizations();
        $this->logProjectActivity($project_id, "Subtask status toggled to '" . strtoupper($newStatus) . "': '{$subtask['name']}'", $oldState);
        $this->updateProjectProgress($project_id);
        
        return $this->response->setJSON([
            'status' => 'success',
            'newStatus' => $newStatus
        ]);
    }

    public function deleteSubtaskAjax($id) {
        $subtaskModel = new \App\Models\SubtaskModel();
        $subtask = $subtaskModel->find($id);
        if ($subtask) {
            $phaseId = $subtask['phase_id'];
            $phaseModel = new PhaseModel();
            $phase = $phaseModel->find($phaseId);
            $projectId = $phase ? $phase['project_id'] : '';
            $oldState = $projectId ? $this->getProjectState($projectId) : null;
            
            $subtaskModel->delete($id);
            $this->recalculateSubtaskAndPhaseDates($phaseId);
            self::recalculateAllResourceUtilizations();
            if ($projectId) {
                $this->logProjectActivity($projectId, "Subtask Deleted: '{$subtask['name']}'", $oldState);
                $this->updateProjectProgress($projectId);
            }
            return $this->response->setJSON(['status' => 'success']);
        }
        return $this->response->setJSON(['status' => 'error', 'message' => 'Subtask not found.']);
    }
}

