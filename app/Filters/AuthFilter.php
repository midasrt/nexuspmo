<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class AuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        $uri = method_exists($request, 'getPath') ? $request->getPath() : $request->getUri()->getPath();
        $uri = trim($uri, '/');

        // Allow access to login and reset password routes
        if (in_array($uri, ['login', 'reset-password'])) {
            return;
        }

        // If not logged in, redirect to login
        if (!$session->get('isLoggedIn')) {
            return redirect()->to(base_url('login'));
        }

        $role = $session->get('role');

        // ── Viewer restrictions ────────────────────────────────────────────────
        if ($role === 'viewer') {
            // Viewers cannot perform any POST/mutating operations, except logging out
            if ($uri !== 'logout' && ($request->getMethod() === 'POST' || $request->getMethod() === 'post')) {
                if ($request->isAJAX()) {
                    $response = service('response');
                    return $response->setJSON([
                        'status'  => 'error',
                        'message' => 'Access Denied: Viewer role is read-only.'
                    ])->setStatusCode(403);
                }
                return redirect()->back()->with('error', 'Access Denied: Viewer role is read-only.');
            }

            // Viewers cannot access the user management, settings, or budget pages
            if (strpos($uri, 'users') === 0 || strpos($uri, 'settings') === 0 || strpos($uri, 'budget') === 0) {
                return redirect()->to(base_url())->with('error', 'Access Denied: Viewers do not have access to this page.');
            }
        }

        // ── Manager restrictions ───────────────────────────────────────────────
        if ($role === 'manager') {
            // Managers cannot access Settings or User Management
            if (strpos($uri, 'settings') === 0 || strpos($uri, 'users') === 0) {
                if ($request->isAJAX()) {
                    $response = service('response');
                    return $response->setJSON([
                        'status'  => 'error',
                        'message' => 'Access Denied: Managers do not have access to this area.'
                    ])->setStatusCode(403);
                }
                return redirect()->to(base_url())->with('error', 'Access Denied: Managers do not have access to Settings or User Management.');
            }
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing
    }
}
