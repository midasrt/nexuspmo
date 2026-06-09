<?php

namespace App\Models;

use CodeIgniter\Model;

class RiskModel extends Model
{
    protected $table            = 'project_risks';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = false;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'id', 'project_id', 'title', 'severity', 'type', 'mitigation', 'owner', 'status', 'reason'
    ];
}
