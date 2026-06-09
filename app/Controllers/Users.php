<?php

namespace App\Controllers;

use App\Models\UserModel;

class Users extends BaseController
{
    public function index()
    {
        // Double-check admin access
        if (session()->get('role') !== 'admin') {
            return redirect()->to(base_url())->with('error', 'Access Denied: Admin role required.');
        }

        $userModel = new UserModel();
        $users = $userModel->orderBy('name', 'ASC')->findAll();

        return view('users', [
            'users'       => $users,
            'currentPath' => '/users'
        ]);
    }

    public function create()
    {
        if (session()->get('role') !== 'admin') {
            return redirect()->to(base_url())->with('error', 'Access Denied: Admin role required.');
        }

        $email = $this->request->getPost('email');
        $password = $this->request->getPost('password');
        $name = $this->request->getPost('name');
        $role = $this->request->getPost('role');

        $userModel = new UserModel();

        // Check if email already exists
        if ($userModel->where('email', $email)->first()) {
            return redirect()->back()->withInput()->with('error', 'Email address is already in use.');
        }

        $userModel->insert([
            'email'      => $email,
            'password'   => password_hash($password, PASSWORD_DEFAULT),
            'name'       => $name,
            'role'       => $role,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        return redirect()->to(base_url('users'))->with('success', 'User created successfully.');
    }

    public function update($id)
    {
        if (session()->get('role') !== 'admin') {
            return redirect()->to(base_url())->with('error', 'Access Denied: Admin role required.');
        }

        $userModel = new UserModel();
        $user = $userModel->find($id);

        if (!$user) {
            return redirect()->to(base_url('users'))->with('error', 'User not found.');
        }

        $email = $this->request->getPost('email');
        $name = $this->request->getPost('name');
        $role = $this->request->getPost('role');
        $password = $this->request->getPost('password');

        // Check unique email (except itself)
        $existing = $userModel->where('email', $email)->where('id !=', $id)->first();
        if ($existing) {
            return redirect()->back()->with('error', 'Email address is already in use.');
        }

        $data = [
            'email'      => $email,
            'name'       => $name,
            'role'       => $role,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        // Only update password if provided
        if (!empty($password)) {
            $data['password'] = password_hash($password, PASSWORD_DEFAULT);
        }

        $userModel->update($id, $data);

        // If updating currently logged in user, refresh their session
        if (session()->get('user_id') == $id) {
            session()->set([
                'email' => $email,
                'name'  => $name,
                'role'  => $role
            ]);
        }

        return redirect()->to(base_url('users'))->with('success', 'User updated successfully.');
    }

    public function delete($id)
    {
        if (session()->get('role') !== 'admin') {
            return redirect()->to(base_url())->with('error', 'Access Denied: Admin role required.');
        }

        // Prevent self deletion
        if (session()->get('user_id') == $id) {
            return redirect()->to(base_url('users'))->with('error', 'You cannot delete your own account.');
        }

        $userModel = new UserModel();
        $userModel->delete($id);

        return redirect()->to(base_url('users'))->with('success', 'User deleted successfully.');
    }
}
