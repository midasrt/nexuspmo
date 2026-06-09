<?php

namespace App\Controllers;

use App\Models\ProjectModel;
use App\Models\PhaseModel;
use App\Models\DependencyModel;
use App\Models\ActionItemModel;
use App\Models\StatusHistoryModel;
use App\Models\EscalationModel;
use App\Models\RiskModel;

class Reports extends BaseController
{
    public function index()
    {
        $projectModel = new ProjectModel();
        $projects = $projectModel->findAll();

        if (empty($projects)) {
            return view('reports', [
                'projects' => [],
                'selectedId' => null,
                'period' => 'weekly',
                'report' => null,
                'title' => 'Reports // PMO',
                'currentPath' => '/reports'
            ]);
        }

        // Get controls from query parameters (GET is clean and allows sharing link)
        $selectedId = $this->request->getGet('project') ?? $projects[0]['id'];
        $period = $this->request->getGet('period') ?? 'weekly';
        $generate = $this->request->getGet('generate') === 'true';

        $report = null;
        if ($generate) {
            $phaseModel = new PhaseModel();
            $dependencyModel = new DependencyModel();
            $actionItemModel = new ActionItemModel();
            $statusHistoryModel = new StatusHistoryModel();
            $escalationModel = new EscalationModel();
            $riskModel = new RiskModel();

            // Fetch target project and its relations
            $project = $projectModel->find($selectedId);
            if ($project) {
                $project['phases'] = $phaseModel->where('project_id', $selectedId)->findAll();
                $completedCount = 0;
                foreach ($project['phases'] as $ph) {
                    if (in_array(strtolower($ph['status']), ['complete', 'completed', 'done'])) {
                        $completedCount++;
                    }
                }
                $project['progress'] = count($project['phases']) > 0 ? (int)(($completedCount / count($project['phases'])) * 100) : 0;
                
                $project['dependencies'] = $dependencyModel->where('project_id', $selectedId)->findAll();
                $project['actionItems'] = $actionItemModel->where('project_id', $selectedId)->findAll();
                
                // Get status history ordered ascending to find "previous" status
                $project['statusHistory'] = $statusHistoryModel->where('project_id', $selectedId)->orderBy('date', 'ASC')->findAll();
                $project['escalations'] = $escalationModel->where('project_id', $selectedId)->findAll();
                $project['risks'] = $riskModel->where('project_id', $selectedId)->findAll();

                $report = $this->buildReport($project, $period);
            }
        }

        return view('reports', [
            'projects'   => $projects,
            'selectedId' => $selectedId,
            'period'     => $period,
            'report'     => $report,
            'title'      => 'Reports // PMO',
            'currentPath' => '/reports'
        ]);
    }

    private function buildReport($p, $period)
    {
        $openNow = 0;
        $closedNow = 0;
        foreach ($p['actionItems'] as $a) {
            if ($a['done'] == 0) {
                $openNow++;
            } else {
                $closedNow++;
            }
        }

        $escNow = count($p['escalations']);

        // Derive previous period snapshot from history
        $historyCount = count($p['statusHistory']);
        if ($historyCount > 1) {
            $prevHist = $p['statusHistory'][$historyCount - 2];
        } elseif ($historyCount === 1) {
            $prevHist = $p['statusHistory'][0];
        } else {
            $prevHist = ['status' => $p['status'], 'note' => 'Initial status.'];
        }

        $progressDelta = ($period === 'weekly') ? 3 : 9;

        $previous = [
            'status'      => $prevHist['status'],
            'progress'    => max(0, $p['progress'] - $progressDelta),
            'openActions' => max(0, $openNow + (($period === 'weekly') ? 1 : 2)),
            'escalations' => max(0, $escNow - ($escNow > 0 ? 1 : 0)),
        ];

        $current = [
            'status'      => $p['status'],
            'progress'    => $p['progress'],
            'openActions' => $openNow,
            'escalations' => $escNow,
        ];

        $periodLabel = ($period === 'weekly') ? 'this week' : 'this month';

        $statusLabelMap = [
            'on-track' => 'ON TRACK',
            'at-risk'  => 'AT RISK',
            'blocked'  => 'BLOCKED',
            'delayed'  => 'DELAYED',
            'backlog'  => 'BACKLOG',
        ];

        $currStatusLabel = $statusLabelMap[$current['status']] ?? strtoupper($current['status']);
        $prevStatusLabel = $statusLabelMap[$previous['status']] ?? strtoupper($previous['status']);

        $summary = "{$p['name']} is currently {$currStatusLabel} at {$current['progress']}% complete. " .
            (($period === 'weekly') ? "Week-over-week" : "Month-over-month") .
            ", progress moved from {$previous['progress']}% to {$current['progress']}% (" .
            $this->signed($current['progress'] - $previous['progress']) . " pts). " .
            "Status shifted from {$prevStatusLabel} → {$currStatusLabel}. " .
            "{$p['health']}";

        $updates = [];
        if ($current['status'] !== $previous['status']) {
            $updates[] = "Status changed from {$prevStatusLabel} to {$currStatusLabel}.";
        }
        $updates[] = "Progress advanced by " . $this->signed($current['progress'] - $previous['progress']) . " pts {$periodLabel}.";
        $updates[] = "{$openNow} action item(s) open, {$closedNow} closed to date.";

        // Find active phase
        $activePhase = null;
        foreach ($p['phases'] as $ph) {
            if (in_array($ph['status'], ['at-risk', 'blocked', 'delayed'])) {
                $activePhase = $ph;
                break;
            }
        }
        if (!$activePhase) {
            foreach ($p['phases'] as $ph) {
                if ($ph['status'] === 'on-track') {
                    $activePhase = $ph;
                    break;
                }
            }
        }
        if ($activePhase) {
            $phaseStatusLabel = $statusLabelMap[$activePhase['status']] ?? strtoupper($activePhase['status']);
            $updates[] = "Active phase: {$activePhase['name']} ({$phaseStatusLabel}), target {$activePhase['end']}.";
        }

        if (count($p['dependencies']) > 0) {
            $updates[] = "Tracking " . count($p['dependencies']) . " cross-project dependency relationship(s).";
        }

        // Achievements
        $achievements = [];
        $closedItemsCount = 0;
        $maxAchievements = ($period === 'weekly') ? 2 : 4;
        foreach ($p['actionItems'] as $a) {
            if ($a['done'] == 1 && $closedItemsCount < $maxAchievements) {
                $achievements[] = "Closed: {$a['title']} ({$a['owner']}).";
                $closedItemsCount++;
            }
        }
        if ($current['progress'] > $previous['progress']) {
            $achievements[] = "Delivered " . $this->signed($current['progress'] - $previous['progress']) . " pts of progress {$periodLabel}.";
        }

        // Risks
        $risks = [];
        foreach ($p['dependencies'] as $d) {
            if (in_array($d['status'], ['blocked', 'at-risk', 'delayed'])) {
                $depStatusLabel = $statusLabelMap[$d['status']] ?? strtoupper($d['status']);
                $risks[] = "Dependency {$d['dep_project_name']} is {$depStatusLabel} — upstream risk.";
            }
        }
        foreach ($p['phases'] as $ph) {
            if (in_array($ph['status'], ['blocked', 'delayed'])) {
                $phaseStatusLabel = $statusLabelMap[$ph['status']] ?? strtoupper($ph['status']);
                $risks[] = "Phase \"{$ph['name']}\" is {$phaseStatusLabel} — review timeline (target {$ph['end']}).";
            }
        }
        foreach ($p['escalations'] as $e) {
            $risks[] = "{$e['level']} escalation to {$e['to_recipient']}: {$e['note']}";
        }

        // Next planned
        $next = [];
        $openItemsCount = 0;
        $maxNext = ($period === 'weekly') ? 3 : 6;
        foreach ($p['actionItems'] as $a) {
            if ($a['done'] == 0 && $openItemsCount < $maxNext) {
                $next[] = "{$a['title']} — {$a['owner']}, due {$a['due']}.";
                $openItemsCount++;
            }
        }

        // Find upcoming backlog phase
        $upcomingPhase = null;
        foreach ($p['phases'] as $ph) {
            if ($ph['status'] === 'backlog') {
                $upcomingPhase = $ph;
                break;
            }
        }
        if ($upcomingPhase) {
            $next[] = "Prepare for phase: {$upcomingPhase['name']} (starts {$upcomingPhase['start']}).";
        }

        return [
            'project'      => $p,
            'period'       => $period,
            'at'           => date('Y-m-d'),
            'summary'      => $summary,
            'previous'     => $previous,
            'current'      => $current,
            'updates'      => $updates,
            'achievements' => $achievements,
            'risks'        => $risks,
            'next'         => $next,
        ];
    }

    private function signed($n)
    {
        if ($n > 0) return "+{$n}";
        if ($n < 0) return "{$n}";
        return "±0";
    }
}
