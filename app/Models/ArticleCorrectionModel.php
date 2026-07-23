<?php

namespace App\Models;

use App\Entities\ArticleCorrection;
use CodeIgniter\Model;

class ArticleCorrectionModel extends Model
{
    protected $table          = 'article_corrections';
    protected $primaryKey     = 'id';
    protected $useAutoIncrement = true;
    protected $returnType     = ArticleCorrection::class;
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'article_id',
        'corrected_by',
        'correction_note',
        'severity',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    protected $validationRules = [
        'article_id'      => 'required|is_natural_no_zero',
        'correction_note' => 'required',
        'severity'        => 'required|in_list[minor,substantial]',
    ];

    /**
     * Public corrections log for an article page, newest first.
     *
     * @return ArticleCorrection[]
     */
    public function forArticle(int $articleId): array
    {
        return $this->where('article_id', $articleId)
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }
}
