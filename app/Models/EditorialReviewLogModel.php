<?php

namespace App\Models;

use App\Entities\EditorialReviewLog;
use CodeIgniter\Model;

class EditorialReviewLogModel extends Model
{
    protected $table          = 'editorial_review_log';
    protected $primaryKey     = 'id';
    protected $useAutoIncrement = true;
    protected $returnType     = EditorialReviewLog::class;
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'article_id',
        'reviewer_id',
        'action',
        'notes',
        'diff_snapshot',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    protected $validationRules = [
        'article_id' => 'required|is_natural_no_zero',
        'action'     => 'required|in_list[submitted,edited,style_pass_applied,style_pass_rejected,changes_requested,rejected,approved,published,correction_made,cap_block]',
    ];

    /**
     * @return EditorialReviewLog[]
     */
    public function forArticle(int $articleId): array
    {
        return $this->where('article_id', $articleId)
            ->orderBy('created_at', 'ASC')
            ->findAll();
    }
}
