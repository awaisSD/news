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

    /**
     * Tags assigned to a given article via the article_tags pivot — used
     * for both the tag-listing front-end pages and the meta keywords tag
     * on article pages (which has no ranking effect on Google/Bing, but is
     * a free byproduct of tags the editorial team already assigns).
     *
     * @return Tag[]
     */
    public function forArticle(int $articleId): array
    {
        return $this->select('tags.*')
            ->join('article_tags', 'article_tags.tag_id = tags.id')
            ->where('article_tags.article_id', $articleId)
            ->orderBy('tags.name', 'ASC')
            ->findAll();
    }
}
