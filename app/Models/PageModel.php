<?php

namespace App\Models;

use App\Entities\Page;
use CodeIgniter\Model;

class PageModel extends Model
{
    protected $table          = 'pages';
    protected $primaryKey     = 'id';
    protected $useAutoIncrement = true;
    protected $returnType     = Page::class;
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'slug',
        'title',
        'body_html',
        'meta_description',
        'is_published',
        'updated_by',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'slug'  => 'required|max_length[150]|is_unique[pages.slug,id,{id}]',
        'title' => 'required|max_length[255]',
    ];

    public function findPublishedBySlug(string $slug): ?Page
    {
        return $this->where('slug', $slug)
            ->where('is_published', 1)
            ->first();
    }
}
