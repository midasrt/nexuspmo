<?php

namespace App\Models;

use CodeIgniter\Model;

class ProjectNarrativeModel extends Model
{
    protected $table            = 'project_narrative_history';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'project_id', 'date', 'sentence', 'created_at'
    ];
}
