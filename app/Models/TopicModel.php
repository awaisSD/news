<?php

namespace App\Models;

use App\Entities\Topic;
use CodeIgniter\Model;

class TopicModel extends Model
{
    protected $table          = 'topics';
    protected $primaryKey     = 'id';
    protected $useAutoIncrement = true;
    protected $returnType     = Topic::class;
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'title',
        'brief',
        'angle_notes',
        'source_ids',
        'created_via',
        'assigned_editor_id',
        'status',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'title'       => 'required|max_length[255]',
        'created_via' => 'required|in_list[rss,trending,manual]',
        'status'      => 'required|in_list[new,assigned,in_generation,used,archived]',
    ];
}
