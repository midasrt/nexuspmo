<?php

namespace App\Controllers;

use App\Models\SquadModel;
use App\Models\StatusDefinitionModel;
use App\Models\OrgStructureModel;
use App\Models\ResourceModel;

class Settings extends BaseController
{
    public function index()
    {
        $squadModel = new SquadModel();
        $squads = $squadModel->findAll();

        $statusDefModel = new StatusDefinitionModel();
        $statusDefinitions = $statusDefModel->findAll();

        $orgModel = new OrgStructureModel();
        $orgStructures = $orgModel->findAll();

        $settingsModel = new \App\Models\SettingsModel();
        $resourceSettings = [
            'daily_work_hours' => $settingsModel->getSetting('daily_work_hours', 8),
            'work_days_per_week' => $settingsModel->getSetting('work_days_per_week', 5),
        ];

        return view('settings', [
            'squads'            => $squads,
            'statusDefinitions' => $statusDefinitions,
            'orgStructures'     => $orgStructures,
            'resourceSettings'  => $resourceSettings,
            'title'             => 'Settings // PMO',
            'currentPath'       => '/settings'
        ]);
    }

    public function updateResources()
    {
        $settingsModel = new \App\Models\SettingsModel();
        
        $dailyWorkHours = $this->request->getPost('daily_work_hours');
        $workDaysPerWeek = $this->request->getPost('work_days_per_week');
        
        if ($dailyWorkHours === null || $workDaysPerWeek === null) {
            return redirect()->back()->with('error', 'All settings fields are required.');
        }
        
        $settingsModel->setSetting('daily_work_hours', $dailyWorkHours);
        $settingsModel->setSetting('work_days_per_week', $workDaysPerWeek);
        
        // Trigger utilization update for all resources
        \App\Controllers\Projects::recalculateAllResourceUtilizations();
        
        return redirect()->to('/settings')->with('success', 'Resource management settings updated successfully.');
    }

    public function createStatus()
    {
        $statusDefModel = new StatusDefinitionModel();
        
        $status = $this->request->getPost('status');
        $label = $this->request->getPost('label');
        $criteria = $this->request->getPost('criteria');
        $color = $this->request->getPost('color') ?: '#6b7280';

        if (empty($status) || empty($label) || empty($criteria)) {
            return redirect()->back()->with('error', 'All status definition fields are required.');
        }

        // Format status to slug-like
        $status = preg_replace('/[^a-z0-9-]+/', '-', strtolower($status));

        if ($statusDefModel->where('status', $status)->first()) {
            return redirect()->back()->with('error', 'Status definition slug already exists.');
        }

        $statusDefModel->insert([
            'status'   => $status,
            'label'    => $label,
            'criteria' => $criteria,
            'color'    => $color,
        ]);

        return redirect()->to('/settings')->with('success', 'Status definition created successfully.');
    }

    public function updateStatus($id)
    {
        $statusDefModel = new StatusDefinitionModel();
        $definition = $statusDefModel->find($id);

        if (!$definition) {
            return redirect()->to('/settings')->with('error', 'Status definition not found.');
        }

        $status = $this->request->getPost('status');
        $label = $this->request->getPost('label');
        $criteria = $this->request->getPost('criteria');
        $color = $this->request->getPost('color') ?: '#6b7280';

        if (empty($status) || empty($label) || empty($criteria)) {
            return redirect()->back()->with('error', 'All status definition fields are required.');
        }

        $status = preg_replace('/[^a-z0-9-]+/', '-', strtolower($status));

        // Check unique constraint if changing status code
        if ($status !== $definition['status']) {
            if ($statusDefModel->where('status', $status)->first()) {
                return redirect()->back()->with('error', 'Status definition slug already exists.');
            }
        }

        $statusDefModel->update($id, [
            'status'   => $status,
            'label'    => $label,
            'criteria' => $criteria,
            'color'    => $color,
        ]);

        return redirect()->to('/settings')->with('success', 'Status definition updated successfully.');
    }

    public function deleteStatus($id)
    {
        $statusDefModel = new StatusDefinitionModel();
        if (!$statusDefModel->find($id)) {
            return redirect()->to('/settings')->with('error', 'Status definition not found.');
        }

        $statusDefModel->delete($id);
        return redirect()->to('/settings')->with('success', 'Status definition deleted successfully.');
    }

    public function createOrg()
    {
        $orgModel = new OrgStructureModel();
        $name = trim($this->request->getPost('name') ?? '');
        $description = trim($this->request->getPost('description') ?? '');

        if (empty($name)) {
            return redirect()->back()->with('error', 'Department name is required.');
        }

        if ($orgModel->where('name', $name)->first()) {
            return redirect()->back()->with('error', 'Department with this name already exists.');
        }

        $orgModel->insert([
            'name'        => $name,
            'description' => $description,
        ]);

        return redirect()->to('/settings')->with('success', 'Department created successfully.');
    }

    public function updateOrg($id)
    {
        $orgModel = new OrgStructureModel();
        $org = $orgModel->find($id);

        if (!$org) {
            return redirect()->to('/settings')->with('error', 'Department not found.');
        }

        $name = trim($this->request->getPost('name') ?? '');
        $description = trim($this->request->getPost('description') ?? '');

        if (empty($name)) {
            return redirect()->back()->with('error', 'Department name is required.');
        }

        if ($name !== $org['name']) {
            if ($orgModel->where('name', $name)->first()) {
                return redirect()->back()->with('error', 'Department with this name already exists.');
            }

            // Sync/update resources using this department
            $db = \Config\Database::connect();
            $db->table('resources')->where('department', $org['name'])->update(['department' => $name]);
        }

        $orgModel->update($id, [
            'name'        => $name,
            'description' => $description,
        ]);

        return redirect()->to('/settings')->with('success', 'Department updated successfully.');
    }

    public function deleteOrg($id)
    {
        $orgModel = new OrgStructureModel();
        $org = $orgModel->find($id);

        if (!$org) {
            return redirect()->to('/settings')->with('error', 'Department not found.');
        }

        // Check if resources are currently assigned to this department
        $resModel = new ResourceModel();
        $assignedResources = $resModel->where('department', $org['name'])->countAllResults();

        if ($assignedResources > 0) {
            return redirect()->to('/settings')->with('error', "Cannot delete department '{$org['name']}' as it is currently assigned to {$assignedResources} resource(s).");
        }

        $orgModel->delete($id);
        return redirect()->to('/settings')->with('success', 'Department deleted successfully.');
    }
}
