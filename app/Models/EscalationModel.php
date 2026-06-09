<?php

namespace App\Models;

use CodeIgniter\Model;

class EscalationModel extends Model
{
    protected $table            = 'project_escalations';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'project_id', 'date', 'level', 'note', 'to_recipient', 'status', 'reason'
    ];
}
