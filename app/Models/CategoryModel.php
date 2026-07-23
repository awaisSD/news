<?php

namespace App\Models;

use App\Entities\Category;
use CodeIgniter\Model;
use RuntimeException;

class CategoryModel extends Model
{
    protected $table          = 'categories';
    protected $primaryKey     = 'id';
    protected $useAutoIncrement = true;
    protected $returnType     = Category::class;
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'parent_id',
        'name',
        'slug',
        'description',
        'sort_order',
        'is_active',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'name' => 'required|max_length[100]',
        'slug' => 'required|max_length[120]|is_unique[categories.slug,id,{id}]',
    ];

    /**
     * Slugs that must never be assigned to a category because they would
     * collide with static routes registered in app/Config/Routes.php.
     * CategoryController must enforce the same list so the two layers of
     * defense (route table + model) stay in sync.
     */
    public const RESERVED_SLUGS = [
        'admin',
        'feed',
        'sitemap.xml',
        'news-sitemap.xml',
        'robots.txt',
        'author',
        'search',
        'tag',
        'page',
        'about-us',
        'contact-us',
        'editorial-policy',
        'corrections-policy',
        'privacy-policy',
        'terms-conditions',
    ];

    // Reject reserved slugs before they ever reach the database, in
    // addition to $validationRules above (which only enforces NOT NULL /
    // length / uniqueness constraints).
    protected $beforeInsert = ['rejectReservedSlug'];
    protected $beforeUpdate = ['rejectReservedSlug'];

    protected $afterInsert = ['clearTreeCache'];
    protected $afterUpdate = ['clearTreeCache'];
    protected $afterDelete = ['clearTreeCache'];

    private const TREE_CACHE_KEY = 'category_tree';
    private const TREE_CACHE_TTL = 3600;

    public function isSlugReserved(string $slug): bool
    {
        return in_array(strtolower($slug), self::RESERVED_SLUGS, true);
    }

    /**
     * Model callback (beforeInsert/beforeUpdate). Throws rather than
     * silently failing so the calling controller/service gets a clear,
     * unambiguous error instead of a category quietly saved with a slug
     * that will 404 forever behind a static route.
     */
    protected function rejectReservedSlug(array $data): array
    {
        $slug = $data['data']['slug'] ?? null;

        if ($slug !== null && $this->isSlugReserved($slug)) {
            throw new RuntimeException(sprintf(
                'The slug "%s" is reserved and cannot be used for a category.',
                $slug
            ));
        }

        return $data;
    }

    /**
     * Active top-level categories with their active children nested one
     * level deep, cached for 3600s under the `category_tree` key.
     *
     * @return Category[]
     */
    public function getTree(): array
    {
        $cache  = service('cache');
        $cached = $cache->get(self::TREE_CACHE_KEY);

        if ($cached !== null) {
            return $cached;
        }

        $all = $this->where('is_active', 1)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('name', 'ASC')
            ->findAll();

        $byParent = [];
        foreach ($all as $category) {
            $parentId = $category->parent_id ?? 0;
            $byParent[$parentId][] = $category;
        }

        $topLevel = $byParent[0] ?? [];
        foreach ($topLevel as $category) {
            $category->setChildren($byParent[$category->id] ?? []);
        }

        $cache->save(self::TREE_CACHE_KEY, $topLevel, self::TREE_CACHE_TTL);

        return $topLevel;
    }

    /**
     * @param array $data model callback payload
     */
    protected function clearTreeCache(array $data): array
    {
        service('cache')->delete(self::TREE_CACHE_KEY);

        return $data;
    }
}
