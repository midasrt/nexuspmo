<?php

namespace App\Models;

use CodeIgniter\Model;

class SquadModel extends Model
{
    protected $table            = 'squads';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'id', 'name', 'mission', 'lead'
    ];
}
