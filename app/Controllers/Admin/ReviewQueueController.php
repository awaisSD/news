<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\ArticleModel;
use App\Models\ArticleRevisionModel;
use App\Models\CategoryModel;
use App\Models\EditorialReviewLogModel;
use App\Models\UserModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use DateTimeInterface;

/**
 * Read/navigation surface into the editorial review queue.
 *
 * IMPORTANT: this controller does NOT perform any status-transition
 * mutations itself. The Approve / Request Changes / Reject action forms
 * rendered on the show() screen post directly to ArticleController's
 * existing routes (articles/(:num)/approve, /request-changes, /reject —
 * see app/Config/Routes.php), which already wrap
 * App\Libraries\EditorialReviewService. Duplicating that logic here would
 * create two code paths that could drift apart on the one guarantee that
 * matters most in this app: no AI-assisted article reaches a reader
 * without a distinct, logged, human approve+publish action.
 */
class ReviewQueueController extends BaseController
{
    public function index()
    {
        $queue = model(ArticleModel::class)->listForReviewQueue();

        $userModel     = model(UserModel::class);
        $categoryModel = model(CategoryModel::class);

        $userIds = [];
        $categoryIds = [];
        foreach ($queue as $article) {
            if ($article->author_id !== null) {
                $userIds[$article->author_id] = true;
            }
            if ($article->assigned_editor_id !== null) {
                $userIds[$article->assigned_editor_id] = true;
            }
            if ($article->primary_category_id !== null) {
                $categoryIds[$article->primary_category_id] = true;
            }
        }

        $usersById = [];
        if ($userIds !== []) {
            foreach ($userModel->whereIn('id', array_keys($userIds))->findAll() as $user) {
                $usersById[$user->id] = $user;
            }
        }

        $categoriesById = [];
        if ($categoryIds !== []) {
            foreach ($categoryModel->whereIn('id', array_keys($categoryIds))->findAll() as $category) {
                $categoriesById[$category->id] = $category;
            }
        }

        $waitingFor = [];
        foreach ($queue as $article) {
            $waitingFor[$article->id] = $this->humanElapsed($article->updated_at);
        }

        return view('admin/review_queue/index', [
            'queue'          => $queue,
            'usersById'      => $usersById,
            'categoriesById' => $categoriesById,
            'waitingFor'     => $waitingFor,
        ]);
    }

    /**
     * The central review screen. Any article status can be viewed here for
     * context (e.g. an editor jumping back to something already approved),
     * but only in_review/changes_requested articles are "actionable" —
     * the view highlights this distinction rather than 404ing.
     */
    public function show(int $articleId)
    {
        $articleModel = model(ArticleModel::class);
        $article      = $articleModel->find($articleId);

        if ($article === null) {
            throw PageNotFoundException::forPageNotFound('No such article.');
        }

        $isActionable = in_array($article->status, ['in_review', 'changes_requested'], true);

        $history = model(EditorialReviewLogModel::class)->forArticle($articleId);

        $userModel = model(UserModel::class);

        $reviewersById = [];
        foreach ($history as $log) {
            if (! isset($reviewersById[$log->reviewer_id])) {
                $reviewersById[$log->reviewer_id] = $userModel->find($log->reviewer_id);
            }
        }

        // Most recent revision snapshot (pre-edit state), used to build a
        // naive "what changed" diff against the current body.
        $revisions        = model(ArticleRevisionModel::class)->forArticle($articleId);
        $previousRevision = $revisions[0] ?? null;

        $diff = ['added' => [], 'removed' => []];
        if ($previousRevision !== null) {
            // TODO: swap in jfcherng/php-diff for real word-level diff highlighting.
            $oldLines = array_values(array_filter(array_map('trim', explode(
                "\n",
                strip_tags((string) $previousRevision->body_html)
            ))));
            $newLines = array_values(array_filter(array_map('trim', explode(
                "\n",
                strip_tags((string) $article->body_html)
            ))));

            $diff['removed'] = array_values(array_diff($oldLines, $newLines));
            $diff['added']   = array_values(array_diff($newLines, $oldLines));
        }

        $author         = $article->author_id !== null ? $userModel->find($article->author_id) : null;
        $assignedEditor = $article->assigned_editor_id !== null ? $userModel->find($article->assigned_editor_id) : null;
        $category       = $article->primary_category_id !== null
            ? model(CategoryModel::class)->find($article->primary_category_id)
            : null;

        return view('admin/review_queue/show', [
            'article'          => $article,
            'isActionable'     => $isActionable,
            'history'          => $history,
            'reviewersById'    => $reviewersById,
            'previousRevision' => $previousRevision,
            'diff'             => $diff,
            'author'           => $author,
            'assignedEditor'   => $assignedEditor,
            'category'         => $category,
        ]);
    }

    /**
     * "3 hours ago" / "2 days ago" style helper for the queue's "how long
     * waiting" column. Deliberately a small hand-rolled helper rather than
     * a dependency — see class docblock for why this stays self-contained.
     */
    private function humanElapsed(?DateTimeInterface $since): string
    {
        if ($since === null) {
            return '—';
        }

        $diffSeconds = time() - $since->getTimestamp();

        if ($diffSeconds < 60) {
            return 'just now';
        }
        if ($diffSeconds < 3600) {
            $minutes = intdiv($diffSeconds, 60);

            return $minutes . ' minute' . ($minutes === 1 ? '' : 's') . ' ago';
        }
        if ($diffSeconds < 86400) {
            $hours = intdiv($diffSeconds, 3600);

            return $hours . ' hour' . ($hours === 1 ? '' : 's') . ' ago';
        }

        $days = intdiv($diffSeconds, 86400);

        return $days . ' day' . ($days === 1 ? '' : 's') . ' ago';
    }
}
