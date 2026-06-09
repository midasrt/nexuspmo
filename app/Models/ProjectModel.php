<?php

namespace App\Models;

use CodeIgniter\Model;

class ProjectModel extends Model
{
    protected $table            = 'projects';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'id', 'code', 'name', 'owner', 'squad', 'status', 'health', 'startDate', 'endDate', 'progress', 'description'
    ];
}
