<?php

namespace App\Models;

use App\Entities\Redirect;
use CodeIgniter\Model;

class RedirectModel extends Model
{
    protected $table          = 'redirects';
    protected $primaryKey     = 'id';
    protected $useAutoIncrement = true;
    protected $returnType     = Redirect::class;
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'old_path',
        'new_path',
        'redirect_type',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    protected $validationRules = [
        'old_path' => 'required|max_length[500]|is_unique[redirects.old_path,id,{id}]',
        'new_path' => 'required|max_length[500]',
    ];

    public function findByOldPath(string $path): ?Redirect
    {
        return $this->where('old_path', $path)->first();
    }
}
