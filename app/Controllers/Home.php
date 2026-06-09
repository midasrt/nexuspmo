<?php

namespace App\Controllers;

use App\Models\ProjectModel;
use App\Models\PhaseModel;
use App\Models\DependencyModel;
use App\Models\ActionItemModel;
use App\Models\RiskModel;
use App\Models\EscalationModel;

class Home extends BaseController
{
    public function index()
    {
        $projectModel = new ProjectModel();
        $phaseModel = new PhaseModel();
        $dependencyModel = new DependencyModel();
        $actionItemModel = new ActionItemModel();
        $riskModel = new RiskModel();
        $escalationModel = new EscalationModel();

        $projects = $projectModel->findAll();

        // Get filter from query parameter
        $filter = $this->request->getGet('filter') ?? 'all';

        // Calculate status counts
        $counts = [
            'on-track' => 0,
            'at-risk'  => 0,
            'blocked'  => 0,
            'delayed'  => 0,
            'backlog'  => 0,
        ];

        foreach ($projects as &$p) {
            if (isset($counts[$p['status']])) {
                $counts[$p['status']]++;
            }

            // Fetch relations
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
        if ($filter !== 'all') {
            $filteredProjects = [];
            foreach ($projects as $p) {
                if ($p['status'] === $filter) {
                    $filteredProjects[] = $p;
                }
            }
            $projects = $filteredProjects;
        }

        return view('portfolio', [
            'projects' => $projects,
            'counts'   => $counts,
            'total'    => count($projectModel->findAll()),
            'filter'   => $filter,
            'title'    => 'PMO // Portfolio Tracker 2026-2027',
            'currentPath' => '/'
        ]);
    }
}
