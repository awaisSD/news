<?php

namespace App\Models;

use App\Entities\Tag;
use CodeIgniter\Model;

class TagModel extends Model
{
    protected $table          = 'tags';
    protected $primaryKey     = 'id';
    protected $useAutoIncrement = true;
    protected $returnType     = Tag::class;
    protected $useSoftDeletes = false;
    protected $useTimestamps  = false;

    protected $allowedFields = [
        'name',
        'slug',
    ];

    protected $validationRules = [
        'name' => 'required|max_length[100]',
        'slug' => 'required|max_length[120]|is_unique[tags.slug,id,{id}]',
    ];
}
