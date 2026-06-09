<?php

namespace App\Models;

use CodeIgniter\Model;

class OrgStructureModel extends Model
{
    protected $table            = 'org_structures';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'id', 'name', 'description'
    ];
}
