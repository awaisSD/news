<?php

namespace App\Models;

use App\Entities\AiImageJob;
use CodeIgniter\Model;

class AiImageJobModel extends Model
{
    protected $table          = 'ai_image_jobs';
    protected $primaryKey     = 'id';
    protected $useAutoIncrement = true;
    protected $returnType     = AiImageJob::class;
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'article_id',
        'provider',
        'prompt',
        'status',
        'generated_path',
        'width',
        'height',
        'cost_usd',
        'requested_by',
        'completed_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    protected $validationRules = [
        'article_id' => 'required|is_natural_no_zero',
        'provider'   => 'required|max_length[50]',
        'prompt'     => 'required',
        'status'     => 'required|in_list[pending,processing,completed,failed,cancelled]',
    ];

    /**
     * @return AiImageJob[]
     */
    public function forArticle(int $articleId): array
    {
        return $this->where('article_id', $articleId)
            ->orderBy('created_at', 'DESC')
            ->findAll();
    }
}
