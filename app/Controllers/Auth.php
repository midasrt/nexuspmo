<?php

namespace App\Controllers;

use App\Models\UserModel;
use CodeIgniter\Controller;

class Auth extends BaseController
{
    public function login()
    {
        // If already logged in, redirect to home
        if (session()->get('isLoggedIn')) {
            return redirect()->to(base_url());
        }

        if ($this->request->getMethod() === 'POST' || $this->request->getMethod() === 'post') {
            $email = $this->request->getPost('email');
            $password = $this->request->getPost('password');

            $userModel = new UserModel();
            $user = $userModel->where('email', $email)->first();

            if ($user && password_verify($password, $user['password'])) {
                session()->set([
                    'user_id'    => $user['id'],
                    'email'      => $user['email'],
                    'name'       => $user['name'],
                    'role'       => $user['role'],
                    'isLoggedIn' => true,
                ]);
                return redirect()->to(base_url())->with('success', 'Logged in successfully as ' . $user['name']);
            }

            return redirect()->back()->withInput()->with('error', 'Invalid email or password.');
        }

        return view('login');
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to(base_url('login'))->with('success', 'Logged out successfully.');
    }

    public function resetPassword()
    {
        if (session()->get('isLoggedIn')) {
            return redirect()->to(base_url());
        }

        if ($this->request->getMethod() === 'POST' || $this->request->getMethod() === 'post') {
            $email = $this->request->getPost('email');

            $userModel = new UserModel();
            $user = $userModel->where('email', $email)->first();

            if ($user) {
                // Generate a random 10-character password
                $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()';
                $newPassword = '';
                for ($i = 0; $i < 10; $i++) {
                    $newPassword .= $chars[rand(0, strlen($chars) - 1)];
                }

                // Update user's password in DB
                $userModel->update($user['id'], [
                    'password'   => password_hash($newPassword, PASSWORD_DEFAULT),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);

                // Send reset email via CI4 Email service
                $emailService = \Config\Services::email();
                $emailService->setTo($email);
                $emailService->setSubject('NEXUS - Password Reset');
                $emailService->setMessage("Hello {$user['name']},\n\nYour temporary password has been reset to: {$newPassword}\n\nPlease use this to log in and update your password immediately.\n\nBest regards,\nNEXUS System");
                
                $sent = $emailService->send();
                
                if (!$sent) {
                    // Fallback log for local dev environment
                    log_message('error', "Password reset email failed to send to {$email}. Reset password is: {$newPassword}");
                    return redirect()->to(base_url('login'))->with('success', 'Password reset successfully! Local dev fallback: password is "' . $newPassword . '" (logged to system logs).');
                }

                return redirect()->to(base_url('login'))->with('success', 'A temporary password has been emailed to you.');
            }

            return redirect()->back()->withInput()->with('error', 'No account found with that email address.');
        }

        return view('reset_password');
    }
}
