<?php

namespace App\Models;

use App\Entities\ArticleRevision;
use CodeIgniter\Model;

class ArticleRevisionModel extends Model
{
    protected $table          = 'article_revisions';
    protected $primaryKey     = 'id';
    protected $useAutoIncrement = true;
    protected $returnType     = ArticleRevision::class;
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'article_id',
        'editor_id',
        'status_at_revision',
        'headline',
        'body_html',
        'is_substantive',
        'correction_note',
        'diff_summary',
    ];

    // created_at only — revisions are immutable snapshots, never updated.
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    protected $validationRules = [
        'article_id'          => 'required|is_natural_no_zero',
        'status_at_revision'  => 'required|in_list[draft,in_review,changes_requested,approved,published,corrected,rejected,retracted]',
        'headline'            => 'required|max_length[255]',
    ];

    /**
     * @return ArticleRevision[]
     */
    public function forArticle(int $articleId): array
    {
        return $this->where('article_id', $articleId)
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }
}
