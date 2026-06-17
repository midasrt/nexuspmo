<?php

namespace App\Controllers;

use App\Models\ActivityLogModel;

class ActivityLog extends BaseController
{
    public function index()
    {
        $role = session()->get('role');

        // Only admin and manager can access the activity log
        if (!in_array($role, ['admin', 'manager'])) {
            return redirect()->to(base_url())->with('error', 'Access Denied: Insufficient permissions.');
        }

        $logModel = new ActivityLogModel();

        // Filters from query string
        $filterModule = $this->request->getGet('module') ?? '';
        $filterAction = $this->request->getGet('action') ?? '';
        $filterUser   = $this->request->getGet('user')   ?? '';

        $query = $logModel->orderBy('created_at', 'DESC');

        if (!empty($filterModule)) {
            $query->where('module', $filterModule);
        }
        if (!empty($filterAction)) {
            $query->where('action', strtoupper($filterAction));
        }
        if (!empty($filterUser)) {
            $query->like('user_name', $filterUser);
        }

        // Paginate: 50 per page
        $logs = $query->paginate(50);
        $pager = $logModel->pager;

        // Get distinct modules for filter dropdown
        $db = \Config\Database::connect();
        $modules = $db->table('user_activity_log')
            ->select('DISTINCT module')
            ->orderBy('module', 'ASC')
            ->get()
            ->getResultArray();
        $modules = array_column($modules, 'module');

        return view('activity_log', [
            'logs'          => $logs,
            'pager'         => $pager,
            'modules'       => $modules,
            'filterModule'  => $filterModule,
            'filterAction'  => $filterAction,
            'filterUser'    => $filterUser,
            'title'         => 'Activity Log // PMO',
            'currentPath'   => '/activity-log',
        ]);
    }
}
