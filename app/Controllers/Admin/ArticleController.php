<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Entities\Article;
use App\Libraries\EditorialReviewService;
use App\Libraries\Publishing\InvalidTransitionException;
use App\Libraries\Publishing\SlugGenerator;
use App\Models\ArticleModel;
use App\Models\AuditLogModel;
use App\Models\CategoryModel;
use App\Models\MediaModel;
use App\Models\TagModel;
use App\Models\UserModel;
use CodeIgniter\HTTP\RedirectResponse;
use DateTimeImmutable;
use InvalidArgumentException;

class ArticleController extends BaseController
{
    public function index()
    {
        $model = model(ArticleModel::class);

        $status     = $this->request->getGet('status');
        $categoryId = $this->request->getGet('primary_category_id');
        $authorId   = $this->request->getGet('author_id');

        if ($status) {
            $model = $model->where('status', $status);
        }

        if ($categoryId) {
            $model = $model->where('primary_category_id', (int) $categoryId);
        }

        if ($authorId) {
            $model = $model->where('author_id', (int) $authorId);
        }

        $articles = $model->orderBy('updated_at', 'DESC')->paginate(25);
        $pager    = $model->pager;

        return view('admin/articles/index', [
            'title'      => 'Articles',
            'articles'   => $articles,
            'pager'      => $pager,
            'categories' => $this->flattenCategories(model(CategoryModel::class)->getTree()),
            'authors'    => model(UserModel::class)->orderBy('name', 'ASC')->findAll(),
            'filters'    => [
                'status'              => $status,
                'primary_category_id' => $categoryId,
                'author_id'           => $authorId,
            ],
        ]);
    }

    public function create()
    {
        return view('admin/articles/edit', $this->formData(new Article()));
    }

    public function store()
    {
        $rules = [
            'headline'            => 'required|max_length[255]',
            'body_html'           => 'required',
            'primary_category_id' => 'required|is_natural_no_zero',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $headline   = (string) $this->request->getPost('headline');
        $categoryId = (int) $this->request->getPost('primary_category_id');
        $bodyHtml   = (string) $this->request->getPost('body_html');
        $wordCount  = str_word_count(strip_tags($bodyHtml));

        $slug = (new SlugGenerator())->generate($headline, $categoryId);

        $data = [
            'uuid'                 => generate_uuid4(),
            'headline'             => $headline,
            'slug'                 => $slug,
            'subheadline'          => $this->request->getPost('subheadline') ?: null,
            'excerpt'              => $this->request->getPost('excerpt') ?: null,
            'body_html'            => $bodyHtml,
            'body_format'          => 'html',
            'featured_media_id'    => $this->request->getPost('featured_media_id') ?: null,
            'primary_category_id'  => $categoryId,
            'author_id'            => $this->currentUser()->id,
            'assigned_editor_id'   => $this->request->getPost('assigned_editor_id') ?: null,
            'status'               => 'draft',
            'ai_assisted'          => false,
            'is_breaking'          => (bool) $this->request->getPost('is_breaking'),
            'word_count'           => $wordCount,
            'reading_time_minutes' => (int) ceil(max($wordCount, 1) / 200),
            'meta_title'           => $this->request->getPost('meta_title') ?: null,
            'meta_description'     => $this->request->getPost('meta_description') ?: null,
            'canonical_url'        => $this->request->getPost('canonical_url') ?: null,
        ];

        $model      = model(ArticleModel::class);
        $articleId  = $model->insert($data, true);

        $this->syncTaxonomy((int) $articleId, $categoryId);

        model(AuditLogModel::class)->record(
            $this->currentUser()->id,
            'created',
            'article',
            (int) $articleId,
            null,
            ['status' => 'draft', 'headline' => $headline],
            $this->request->getIPAddress(),
            date('Y-m-d H:i:s')
        );

        return redirect()->to('/admin/articles/' . $articleId . '/edit')->with('success', 'Article created.');
    }

    public function edit(int $id)
    {
        $article = model(ArticleModel::class)->find($id);

        if ($article === null) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException("Article #{$id} not found.");
        }

        return view('admin/articles/edit', $this->formData($article));
    }

    public function update(int $id)
    {
        $article = model(ArticleModel::class)->find($id);

        if ($article === null) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException("Article #{$id} not found.");
        }

        if ($article->status === 'published') {
            return redirect()->to('/admin/articles/' . $id . '/edit')
                ->with('error', 'This article is published. Use "Record Correction" below to change its headline/body — direct edits to published articles are not allowed so every change stays in the audit trail.');
        }

        $rules = [
            'headline'            => 'required|max_length[255]',
            'body_html'           => 'required',
            'primary_category_id' => 'required|is_natural_no_zero',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $headline   = (string) $this->request->getPost('headline');
        $categoryId = (int) $this->request->getPost('primary_category_id');
        $bodyHtml   = (string) $this->request->getPost('body_html');
        $wordCount  = str_word_count(strip_tags($bodyHtml));

        // Slug is only ever regenerated here (pre-publish); once published,
        // update() is blocked entirely above, so a live article's slug/URL
        // never silently changes.
        $slug = $article->slug;
        if ($headline !== $article->headline) {
            $slug = (new SlugGenerator())->generate($headline, $categoryId, $article->id);
        }

        $before = ['headline' => $article->headline, 'status' => $article->status];

        $data = [
            'headline'             => $headline,
            'slug'                 => $slug,
            'subheadline'          => $this->request->getPost('subheadline') ?: null,
            'excerpt'              => $this->request->getPost('excerpt') ?: null,
            'body_html'            => $bodyHtml,
            'featured_media_id'    => $this->request->getPost('featured_media_id') ?: null,
            'primary_category_id'  => $categoryId,
            'assigned_editor_id'   => $this->request->getPost('assigned_editor_id') ?: null,
            'is_breaking'          => (bool) $this->request->getPost('is_breaking'),
            'word_count'           => $wordCount,
            'reading_time_minutes' => (int) ceil(max($wordCount, 1) / 200),
            'meta_title'           => $this->request->getPost('meta_title') ?: null,
            'meta_description'     => $this->request->getPost('meta_description') ?: null,
            'canonical_url'        => $this->request->getPost('canonical_url') ?: null,
        ];

        model(ArticleModel::class)->update($id, $data);
        $this->syncTaxonomy($id, $categoryId);

        model(AuditLogModel::class)->record(
            $this->currentUser()->id,
            'updated',
            'article',
            $id,
            $before,
            ['headline' => $headline, 'status' => $article->status],
            $this->request->getIPAddress(),
            date('Y-m-d H:i:s')
        );

        return redirect()->to('/admin/articles/' . $id . '/edit')->with('success', 'Article updated.');
    }

    public function delete(int $id)
    {
        $article = model(ArticleModel::class)->find($id);

        if ($article === null) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException("Article #{$id} not found.");
        }

        model(ArticleModel::class)->delete($id);

        model(AuditLogModel::class)->record(
            $this->currentUser()->id,
            'deleted',
            'article',
            $id,
            ['status' => $article->status],
            null,
            $this->request->getIPAddress(),
            date('Y-m-d H:i:s')
        );

        return redirect()->to('/admin/articles')->with('success', 'Article deleted.');
    }

    public function submitForReview(int $id)
    {
        return $this->runTransition($id, 'submit_review', 'submitted for review', function (Article $article, $user, DateTimeImmutable $now) {
            return $this->reviewService()->submitForReview($article, $user, $now);
        });
    }

    public function requestChanges(int $id)
    {
        $note = trim((string) $this->request->getPost('note'));

        if ($note === '') {
            return redirect()->back()->with('error', 'A note is required when requesting changes.');
        }

        return $this->runTransition($id, 'request_changes', 'sent back for changes', function (Article $article, $user, DateTimeImmutable $now) use ($note) {
            return $this->reviewService()->requestChanges($article, $user, $note, $now);
        });
    }

    public function reject(int $id)
    {
        $note = trim((string) $this->request->getPost('note'));

        if ($note === '') {
            return redirect()->back()->with('error', 'A note is required when rejecting an article.');
        }

        return $this->runTransition($id, 'reject', 'rejected', function (Article $article, $user, DateTimeImmutable $now) use ($note) {
            return $this->reviewService()->reject($article, $user, $note, $now);
        });
    }

    public function approve(int $id)
    {
        return $this->runTransition($id, 'approve', 'approved', function (Article $article, $user, DateTimeImmutable $now) {
            return $this->reviewService()->approve($article, $user, $now);
        });
    }

    public function publish(int $id)
    {
        return $this->runTransition($id, 'publish', 'published', function (Article $article, $user, DateTimeImmutable $now) {
            return $this->reviewService()->publish($article, $user, $now);
        });
    }

    public function recordCorrection(int $id)
    {
        $article = model(ArticleModel::class)->find($id);

        if ($article === null) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException("Article #{$id} not found.");
        }

        $headline       = (string) $this->request->getPost('headline');
        $bodyHtml       = (string) $this->request->getPost('body_html');
        $isSubstantive  = (bool) $this->request->getPost('is_substantive');
        $correctionNote = trim((string) $this->request->getPost('correction_note'));

        if (trim($headline) === '' || trim($bodyHtml) === '') {
            return redirect()->back()->with('error', 'Headline and body are required to record a correction.');
        }

        if ($isSubstantive && $correctionNote === '') {
            return redirect()->back()->with('error', 'A correction note is required for substantive corrections.');
        }

        $before = ['status' => $article->status, 'headline' => $article->headline];

        try {
            $this->reviewService()->recordCorrection(
                $article,
                $this->currentUser(),
                $headline,
                $bodyHtml,
                $isSubstantive,
                $correctionNote !== '' ? $correctionNote : null,
                new DateTimeImmutable()
            );
        } catch (InvalidTransitionException|InvalidArgumentException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        model(AuditLogModel::class)->record(
            $this->currentUser()->id,
            'correction_made',
            'article',
            $id,
            $before,
            ['headline' => $headline, 'is_substantive' => $isSubstantive],
            $this->request->getIPAddress(),
            date('Y-m-d H:i:s')
        );

        return redirect()->to('/admin/articles/' . $id . '/edit')->with('success', 'Correction recorded.');
    }

    public function preview(int $id)
    {
        $article = model(ArticleModel::class)->find($id);

        if ($article === null) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException("Article #{$id} not found.");
        }

        $media = $article->featured_media_id ? model(MediaModel::class)->find($article->featured_media_id) : null;
        $author = $article->author_id ? model(UserModel::class)->find($article->author_id) : null;

        return view('admin/articles/preview', [
            'title'   => 'Preview: ' . $article->headline,
            'article' => $article,
            'media'   => $media,
            'author'  => $author,
        ]);
    }

    /**
     * Shared runner for the six single-note-or-none workflow transitions.
     * Centralizes: loading the article, calling the service with "now",
     * catching InvalidTransitionException, writing the audit log entry,
     * and redirecting back to the edit page with a flash message.
     */
    private function runTransition(int $id, string $actionName, string $pastTenseLabel, callable $callback): RedirectResponse
    {
        $article = model(ArticleModel::class)->find($id);

        if ($article === null) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException("Article #{$id} not found.");
        }

        $before = ['status' => $article->status];

        try {
            $callback($article, $this->currentUser(), new DateTimeImmutable());
        } catch (InvalidTransitionException|InvalidArgumentException $e) {
            return redirect()->to('/admin/articles/' . $id . '/edit')->with('error', $e->getMessage());
        }

        $after = model(ArticleModel::class)->find($id);

        model(AuditLogModel::class)->record(
            $this->currentUser()->id,
            $actionName,
            'article',
            $id,
            $before,
            ['status' => $after->status],
            $this->request->getIPAddress(),
            date('Y-m-d H:i:s')
        );

        return redirect()->to('/admin/articles/' . $id . '/edit')->with('success', 'Article ' . $pastTenseLabel . '.');
    }

    private function reviewService(): EditorialReviewService
    {
        return new EditorialReviewService();
    }

    private function syncTaxonomy(int $articleId, int $primaryCategoryId): void
    {
        $secondaryCategoryIds = array_map('intval', (array) $this->request->getPost('category_ids'));
        $tagIds                = array_map('intval', (array) $this->request->getPost('tag_ids'));

        model(ArticleModel::class)->attachCategories($articleId, $secondaryCategoryIds, $primaryCategoryId);
        model(ArticleModel::class)->attachTags($articleId, $tagIds);
    }

    /**
     * Shared view-data builder for the create/edit form (same view template
     * for both, per the task's "reuse the same edit.php" instruction).
     */
    private function formData(Article $article): array
    {
        $selectedCategoryIds = [];
        $selectedTagIds      = [];

        if ($article->id !== null) {
            $db = \Config\Database::connect();

            $selectedCategoryIds = array_column(
                $db->table('article_categories')->select('category_id')->where('article_id', $article->id)->get()->getResultArray(),
                'category_id'
            );
            $selectedTagIds = array_column(
                $db->table('article_tags')->select('tag_id')->where('article_id', $article->id)->get()->getResultArray(),
                'tag_id'
            );
        }

        return [
            'title'      => $article->id ? 'Edit article' : 'New article',
            'article'    => $article,
            'categories' => $this->flattenCategories(model(CategoryModel::class)->getTree()),
            'tags'       => model(TagModel::class)->findAll(),
            'authors'    => model(UserModel::class)->where('is_active', 1)->orderBy('name', 'ASC')->findAll(),
            'editors'    => model(UserModel::class)->whereIn('role', ['editor', 'admin'])->where('is_active', 1)->orderBy('name', 'ASC')->findAll(),
            'media'      => model(MediaModel::class)->orderBy('created_at', 'DESC')->findAll(200),
            'currentRole'         => $this->currentUser()->role,
            'selectedCategoryIds' => array_map('intval', $selectedCategoryIds),
            'selectedTagIds'      => array_map('intval', $selectedTagIds),
        ];
    }

    /**
     * @param \App\Entities\Category[] $tree
     *
     * @return array<int, array{id:int, label:string}>
     */
    private function flattenCategories(array $tree): array
    {
        $flat = [];

        foreach ($tree as $top) {
            $flat[] = ['id' => $top->id, 'label' => $top->name];

            foreach ($top->getChildren() as $child) {
                $flat[] = ['id' => $child->id, 'label' => '— ' . $child->name];
            }
        }

        return $flat;
    }

    // generate_uuid4() comes from app/Helpers/uuid_helper.php (preloaded by BaseController).
}
