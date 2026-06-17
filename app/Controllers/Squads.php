<?php

namespace App\Controllers;

use App\Models\SquadModel;
use App\Models\ProjectModel;

class Squads extends BaseController
{
    public function index()
    {
        $squadModel = new SquadModel();
        $projectModel = new ProjectModel();
        $resourceModel = new \App\Models\ResourceModel();
        
        $squads = $squadModel->findAll();
        $resources = $resourceModel->findAll();

        $db = \Config\Database::connect();

        foreach ($squads as &$s) {
            $s['members'] = $db->table('resources r')
                ->select('r.*')
                ->join('squad_members sm', 'r.id = sm.resource_id')
                ->where('sm.squad_id', $s['id'])
                ->get()
                ->getResultArray();

            // Fetch assigned projects
            $s['projects'] = $projectModel->where('squad', $s['name'])->findAll();
        }

        return view('squads', [
            'squads' => $squads,
            'resources' => $resources,
            'title'  => 'Squads // PMO',
            'currentPath' => '/squads'
        ]);
    }

    public function map()
    {
        $squadModel = new SquadModel();
        $projectModel = new ProjectModel();
        $squads = $squadModel->findAll();

        $db = \Config\Database::connect();
        $groups = [];

        foreach ($squads as $s) {
            $members = $db->table('resources r')
                ->select('r.*')
                ->join('squad_members sm', 'r.id = sm.resource_id')
                ->where('sm.squad_id', $s['id'])
                ->get()
                ->getResultArray();

            $projects = $projectModel->where('squad', $s['name'])->findAll();

            $groups[] = [
                'squad'    => $s,
                'members'  => $members,
                'projects' => $projects
            ];
        }

        // Sort by total projects + members count descending
        usort($groups, function ($a, $b) {
            $countA = count($a['projects']) + count($a['members']);
            $countB = count($b['projects']) + count($b['members']);
            return $countB - $countA;
        });

        return view('squad_map', [
            'groups' => $groups,
            'title'  => 'Squad Map // PMO',
            'currentPath' => '/squads'
        ]);
    }

    public function create()
    {
        $squadModel = new SquadModel();

        $name = $this->request->getPost('name');
        $lead = $this->request->getPost('lead');
        $mission = $this->request->getPost('mission');

        if (empty($name) || empty($lead)) {
            return redirect()->back()->with('error', 'Required fields (Name, Lead) are missing.');
        }

        // Check unique constraint
        if ($squadModel->where('name', $name)->first()) {
            return redirect()->back()->with('error', 'A squad with this name already exists.');
        }

        $squadModel->insert([
            'name'    => $name,
            'lead'    => $lead,
            'mission' => $mission,
        ]);

        helper('activity');
        log_activity('CREATE', 'squads', null, $name, "Created squad led by [{$lead}]");

        return redirect()->to('/squads')->with('success', 'Squad created successfully.');
    }

    public function update($id)
    {
        $squadModel = new SquadModel();
        $squad = $squadModel->find($id);

        if (!$squad) {
            return redirect()->to('/squads')->with('error', 'Squad not found.');
        }

        $name = $this->request->getPost('name');
        $lead = $this->request->getPost('lead');
        $mission = $this->request->getPost('mission');

        if (empty($name) || empty($lead)) {
            return redirect()->back()->with('error', 'Required fields (Name, Lead) are missing.');
        }

        // Check unique constraint if name changes
        if ($name !== $squad['name']) {
            if ($squadModel->where('name', $name)->first()) {
                return redirect()->back()->with('error', 'A squad with this name already exists.');
            }

            // Update references
            $db = \Config\Database::connect();
            $db->table('projects')->where('squad', $squad['name'])->update(['squad' => $name]);
        }

        $squadModel->update($id, [
            'name'    => $name,
            'lead'    => $lead,
            'mission' => $mission,
        ]);

        helper('activity');
        log_activity('UPDATE', 'squads', (int)$id, $name, "Updated squad [{$squad['name']}] → [{$name}]");

        return redirect()->to('/squads')->with('success', 'Squad updated successfully.');
    }

    public function delete($id)
    {
        $squadModel = new SquadModel();
        $squad = $squadModel->find($id);

        if (!$squad) {
            return redirect()->to('/squads')->with('error', 'Squad not found.');
        }

        $db = \Config\Database::connect();
        
        // Remove squad members
        $db->table('squad_members')->where('squad_id', $squad['id'])->delete();
        
        // Unassign projects assigned to this squad
        $db->table('projects')->where('squad', $squad['name'])->update(['squad' => null]);

        $squadModel->delete($id);

        helper('activity');
        log_activity('DELETE', 'squads', (int)$id, $squad['name'], "Deleted squad [{$squad['name']}]");

        return redirect()->to('/squads')->with('success', 'Squad deleted successfully.');
    }

    public function addMember()
    {
        $squadId = $this->request->getPost('squad_id');
        $resourceId = $this->request->getPost('resource_id');

        if (empty($squadId) || empty($resourceId)) {
            return redirect()->back()->with('error', 'Squad ID and Resource ID are required.');
        }

        $db = \Config\Database::connect();
        
        // Check if member already exists in the squad
        $exists = $db->table('squad_members')
            ->where('squad_id', $squadId)
            ->where('resource_id', $resourceId)
            ->countAllResults();

        if ($exists === 0) {
            $db->table('squad_members')->insert([
                'squad_id'    => $squadId,
                'resource_id' => $resourceId
            ]);
            helper('activity');
            log_activity('UPDATE', 'squads', (int)$squadId, null, "Added resource ID [{$resourceId}] to squad ID [{$squadId}]");
        }

        return redirect()->to('/squads')->with('success', 'Resource added to squad successfully.');
    }

    public function removeMember($squadId, $resourceId)
    {
        $db = \Config\Database::connect();
        $db->table('squad_members')
            ->where('squad_id', $squadId)
            ->where('resource_id', $resourceId)
            ->delete();

        helper('activity');
        log_activity('UPDATE', 'squads', (int)$squadId, null, "Removed resource ID [{$resourceId}] from squad ID [{$squadId}]");

        return redirect()->to('/squads')->with('success', 'Resource removed from squad successfully.');
    }
}
