<?php

namespace App\Models;

use App\Entities\Article;
use CodeIgniter\Model;
use DateTimeImmutable;

/**
 * Persistence only. Publish/approve/status-transition workflow logic lives
 * entirely in App\Libraries\EditorialReviewService and
 * App\Libraries\Publishing\ArticleWorkflow — this model must not gain any
 * business rules beyond reads/writes.
 */
class ArticleModel extends Model
{
    protected $table          = 'articles';
    protected $primaryKey     = 'id';
    protected $useAutoIncrement = true;
    protected $returnType     = Article::class;
    protected $useSoftDeletes = true;
    protected $deletedField   = 'deleted_at';

    protected $allowedFields = [
        'uuid',
        'headline',
        'slug',
        'subheadline',
        'excerpt',
        'body_html',
        'body_format',
        'featured_media_id',
        'primary_category_id',
        'author_id',
        'editor_id',
        'assigned_editor_id',
        'status',
        'ai_assisted',
        'ai_generation_job_id',
        'is_breaking',
        'word_count',
        'reading_time_minutes',
        'meta_title',
        'meta_description',
        'canonical_url',
        'published_at',
        'updated_at_content',
        'publish_at',
        'view_count',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'uuid'                 => 'required|max_length[36]',
        'headline'             => 'required|max_length[255]',
        'slug'                 => 'required|max_length[255]',
        'body_format'          => 'required|in_list[html,markdown]',
        'primary_category_id'  => 'required|is_natural_no_zero',
        'author_id'            => 'required|is_natural_no_zero',
        'status'               => 'required|in_list[draft,in_review,changes_requested,approved,published,corrected,rejected,retracted]',
    ];

    /**
     * List-view column set — deliberately excludes body_html so category/
     * listing pages never pull the full article body over the wire.
     */
    private const LIST_COLUMNS = [
        'id',
        'headline',
        'slug',
        'excerpt',
        'featured_media_id',
        'published_at',
        'author_id',
        'primary_category_id',
    ];

    public function findPublishedByCategoryAndSlug(string $categorySlug, string $slug): ?Article
    {
        return $this
            ->select('articles.*')
            ->join('categories', 'categories.id = articles.primary_category_id')
            ->where('categories.slug', $categorySlug)
            ->where('articles.slug', $slug)
            ->where('articles.status', 'published')
            ->first();
    }

    /**
     * Keyset (seek) pagination over published articles in a category,
     * ordered newest-first. Pass the last row's published_at/id from the
     * previous page as $beforePublishedAt/$beforeId to fetch the next page.
     *
     * @return Article[]
     */
    public function listPublishedForCategory(
        int $categoryId,
        ?string $beforePublishedAt = null,
        ?int $beforeId = null,
        int $limit = 20
    ): array {
        $builder = $this->select(self::LIST_COLUMNS)
            ->where('primary_category_id', $categoryId)
            ->where('status', 'published');

        if ($beforePublishedAt !== null && $beforeId !== null) {
            $builder->groupStart()
                ->where('published_at <', $beforePublishedAt)
                ->orGroupStart()
                    ->where('published_at', $beforePublishedAt)
                    ->where('id <', $beforeId)
                ->groupEnd()
            ->groupEnd();
        }

        return $builder
            ->orderBy('published_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->findAll($limit);
    }

    /**
     * Editorial review queue, FIFO — oldest-updated items first so no
     * article can be starved by newer submissions jumping the line.
     *
     * @return Article[]
     */
    public function listForReviewQueue(): array
    {
        return $this
            ->whereIn('status', ['in_review', 'changes_requested'])
            ->orderBy('updated_at', 'ASC')
            ->findAll();
    }

    /**
     * Syncs the article_categories pivot to exactly $categoryIds (which
     * must include $primaryCategoryId — the primary category itself is
     * authoritatively recorded on articles.primary_category_id; this pivot
     * only tracks the full set of categories, primary and secondary, an
     * article is filed under).
     *
     * @param int[] $categoryIds
     */
    public function attachCategories(int $articleId, array $categoryIds, int $primaryCategoryId): void
    {
        $categoryIds = array_unique(array_merge($categoryIds, [$primaryCategoryId]));

        $this->db->table('article_categories')->where('article_id', $articleId)->delete();

        $rows = [];
        foreach ($categoryIds as $categoryId) {
            $rows[] = [
                'article_id'  => $articleId,
                'category_id' => $categoryId,
                'is_primary'  => $categoryId === $primaryCategoryId ? 1 : 0,
            ];
        }

        if ($rows !== []) {
            $this->db->table('article_categories')->insertBatch($rows);
        }
    }

    /**
     * @param int[] $tagIds
     */
    public function attachTags(int $articleId, array $tagIds): void
    {
        $this->db->table('article_tags')->where('article_id', $articleId)->delete();

        $rows = [];
        foreach (array_unique($tagIds) as $tagId) {
            $rows[] = [
                'article_id' => $articleId,
                'tag_id'     => $tagId,
            ];
        }

        if ($rows !== []) {
            $this->db->table('article_tags')->insertBatch($rows);
        }
    }

    /**
     * Published articles newer than $cutoff, for the Google News sitemap
     * (which only wants articles from roughly the last 48h). The caller is
     * responsible for computing $cutoff (e.g. `new DateTimeImmutable('-48
     * hours')` at request/command time) — this model never calls
     * date()/now() itself, so $cutoff already encodes the max-age window.
     *
     * @return Article[]
     */
    public function recentForNewsSitemap(DateTimeImmutable $cutoff, int $limit = 1000): array
    {
        return $this
            ->where('status', 'published')
            ->where('published_at >=', $cutoff->format('Y-m-d H:i:s'))
            ->orderBy('published_at', 'DESC')
            ->findAll($limit);
    }
}
