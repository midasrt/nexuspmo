<?php

namespace App\Models;

use CodeIgniter\Model;

class StatusDefinitionModel extends Model
{
    protected $table            = 'status_definitions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'id', 'status', 'label', 'criteria', 'color'
    ];
}
