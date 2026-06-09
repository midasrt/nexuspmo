<?php

namespace App\Controllers;

class Budget extends BaseController
{
    public function index()
    {
        return view('budget', [
            'title'       => 'Budget Control // PMO',
            'currentPath' => '/budget'
        ]);
    }
}
