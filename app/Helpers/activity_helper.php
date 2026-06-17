<?php

/**
 * Activity Logger Helper
 *
 * Call log_activity() from any controller after a successful CRUD operation.
 *
 * @param string      $action      CREATE | UPDATE | DELETE
 * @param string      $module      projects | resources | squads | users | settings | ...
 * @param int|null    $targetId    Primary key of the affected record
 * @param string|null $targetName  Human-readable label of the affected record
 * @param string|null $description Optional extra context
 */
function log_activity(
    string  $action,
    string  $module,
    ?int    $targetId   = null,
    ?string $targetName = null,
    ?string $description = null
): void {
    try {
        $session = session();

        // Only log if a user is authenticated
        if (!$session->get('isLoggedIn')) {
            return;
        }

        $logModel = new \App\Models\ActivityLogModel();
        $request  = \Config\Services::request();

        $logModel->insert([
            'user_id'     => (int) $session->get('user_id'),
            'user_name'   => $session->get('name')  ?? 'Unknown',
            'user_role'   => $session->get('role')  ?? 'unknown',
            'action'      => strtoupper($action),
            'module'      => strtolower($module),
            'target_id'   => $targetId,
            'target_name' => $targetName,
            'description' => $description,
            'ip_address'  => $request->getIPAddress(),
            'created_at'  => date('Y-m-d H:i:s'),
        ]);
    } catch (\Throwable $e) {
        // Never let logging failures break the main flow
        log_message('error', '[ActivityLog] Failed to write log: ' . $e->getMessage());
    }
}
