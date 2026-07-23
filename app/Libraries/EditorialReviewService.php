<?php

namespace App\Libraries;

use App\Entities\Article;
use App\Entities\User;
use App\Libraries\Publishing\ArticleWorkflow;
use App\Libraries\Publishing\InvalidTransitionException;
use App\Models\ArticleModel;
use App\Models\EditorialReviewLogModel;
use DateTimeImmutable;
use InvalidArgumentException;

/**
 * THE sole gatekeeper for article editorial-status transitions.
 *
 * No other class in this codebase may set `articles.status = 'published'`
 * or stamp `published_at`. Every status change goes through here, is
 * validated against ArticleWorkflow::TRANSITIONS, and is written to
 * editorial_review_log for a full audit trail. This is what guarantees
 * that AI-drafted content can never reach a reader without an explicit,
 * logged, human `approve()` call followed by `publish()`.
 *
 * All methods take `\DateTimeImmutable $now` as an explicit parameter from
 * the caller — this class never resolves "current time" itself — so the
 * HTTP-request/CLI-command layer stays the single boundary for time, and
 * this service stays trivially unit-testable.
 */
class EditorialReviewService
{
    public function __construct(
        private ?ArticleModel $articles = null,
        private ?ArticleWorkflow $workflow = null,
        private ?EditorialReviewLogModel $log = null,
        private ?CorrectionsService $corrections = null,
    ) {
        $this->articles ??= model(ArticleModel::class);
        $this->workflow ??= new ArticleWorkflow();
        $this->log ??= model(EditorialReviewLogModel::class);
        $this->corrections ??= new CorrectionsService();
    }

    /**
     * draft|changes_requested -> in_review
     */
    public function submitForReview(Article $article, User $actor, DateTimeImmutable $now): Article
    {
        $this->workflow->assertCanTransition($article->status, 'in_review');

        $this->articles->update($article->id, ['status' => 'in_review']);

        $this->writeLog($article->id, $actor->id, 'submitted', null, $now);

        return $this->articles->find($article->id);
    }

    /**
     * in_review -> changes_requested
     */
    public function requestChanges(Article $article, User $editor, string $note, DateTimeImmutable $now): Article
    {
        if (trim($note) === '') {
            throw new InvalidArgumentException('A note is required when requesting changes.');
        }

        $this->workflow->assertCanTransition($article->status, 'changes_requested');

        $this->articles->update($article->id, ['status' => 'changes_requested']);

        $this->writeLog($article->id, $editor->id, 'changes_requested', $note, $now);

        return $this->articles->find($article->id);
    }

    /**
     * in_review -> rejected
     */
    public function reject(Article $article, User $editor, string $note, DateTimeImmutable $now): Article
    {
        if (trim($note) === '') {
            throw new InvalidArgumentException('A note is required when rejecting an article.');
        }

        $this->workflow->assertCanTransition($article->status, 'rejected');

        $this->articles->update($article->id, ['status' => 'rejected']);

        $this->writeLog($article->id, $editor->id, 'rejected', $note, $now);

        return $this->articles->find($article->id);
    }

    /**
     * in_review -> approved
     *
     * IMPORTANT GOVERNANCE NOTE: callers (the admin controller / route
     * filter) are responsible for ensuring $editor->role is 'editor' or
     * 'admin' BEFORE calling this — this service does not re-check role,
     * that's Filters\RoleFilter's job at the HTTP layer. This method only
     * enforces the state-machine transition + audit log.
     */
    public function approve(Article $article, User $editor, DateTimeImmutable $now): Article
    {
        $this->workflow->assertCanTransition($article->status, 'approved');

        $this->articles->update($article->id, [
            'status'    => 'approved',
            'editor_id' => $editor->id,
        ]);

        $this->writeLog($article->id, $editor->id, 'approved', null, $now);

        return $this->articles->find($article->id);
    }

    /**
     * approved -> published
     *
     * published_at is IMMUTABLE once set: it is only stamped on first
     * publish (when currently null). author_id is never touched here — it
     * represents the real human byline set at article-creation time.
     */
    public function publish(Article $article, User $editor, DateTimeImmutable $now): Article
    {
        $this->workflow->assertCanTransition($article->status, 'published');

        $update = ['status' => 'published'];

        if ($article->published_at === null) {
            $update['published_at'] = $now->format('Y-m-d H:i:s');
        }

        $this->articles->update($article->id, $update);

        $this->writeLog($article->id, $editor->id, 'published', null, $now);

        return $this->articles->find($article->id);
    }

    /**
     * Records a correction to an already-published article. Does NOT change
     * articles.status (it stays 'published' — see CorrectionsService for
     * why corrections never move status). Only valid on published articles.
     *
     * @throws InvalidTransitionException if the article is not published.
     */
    public function recordCorrection(
        Article $article,
        User $editor,
        string $newHeadline,
        string $newBodyHtml,
        bool $isSubstantive,
        ?string $correctionNote,
        DateTimeImmutable $now
    ): Article {
        if ($article->status !== 'published') {
            throw new InvalidTransitionException(
                'Corrections can only be recorded on published articles.'
            );
        }

        $updated = $this->corrections->recordEdit(
            $article,
            $newHeadline,
            $newBodyHtml,
            $editor,
            $isSubstantive,
            $correctionNote,
            $now
        );

        // By this point $this->corrections->recordEdit() has already
        // enforced that $correctionNote is non-empty for substantive
        // edits, so a plain null-coalesce is sufficient here.
        $this->writeLog(
            $article->id,
            $editor->id,
            'correction_made',
            $correctionNote ?? 'Minor edit',
            $now
        );

        return $updated;
    }

    private function writeLog(int $articleId, int $reviewerId, string $action, ?string $notes, DateTimeImmutable $now): void
    {
        $this->log->insert([
            'article_id'  => $articleId,
            'reviewer_id' => $reviewerId,
            'action'      => $action,
            'notes'       => $notes,
            'created_at'  => $now->format('Y-m-d H:i:s'),
        ]);
    }
}
