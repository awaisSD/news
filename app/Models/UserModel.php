<?php

namespace App\Models;

use App\Entities\User;
use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table          = 'users';
    protected $primaryKey     = 'id';
    protected $useAutoIncrement = true;
    protected $returnType     = User::class;
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'uuid',
        'name',
        'email',
        'password_hash',
        'role',
        'bio',
        'credentials',
        'avatar_media_id',
        'twitter_handle',
        'linkedin_url',
        'is_active',
        'last_login_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'uuid'          => 'required|max_length[36]',
        'name'          => 'required|max_length[150]',
        'email'         => 'required|valid_email|max_length[191]|is_unique[users.email,id,{id}]',
        'password_hash' => 'required|max_length[255]',
        'role'          => 'required|in_list[writer,editor,admin]',
    ];

    /**
     * @return User[]
     */
    public function findActiveByRole(string $role): array
    {
        return $this->where('role', $role)
            ->where('is_active', 1)
            ->findAll();
    }
}
