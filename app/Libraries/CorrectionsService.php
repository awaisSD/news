<?php

namespace App\Libraries;

use App\Entities\Article;
use App\Entities\User;
use App\Models\ArticleCorrectionModel;
use App\Models\ArticleModel;
use App\Models\ArticleRevisionModel;
use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Records the audit trail for edits made to an article's content
 * (headline / body), independent of editorial status transitions.
 *
 * This class NEVER changes `articles.status` — that is the exclusive
 * responsibility of ArticleWorkflow / EditorialReviewService. Keeping the
 * two concerns separate means "what changed in the text" and "what stage
 * of the editorial pipeline is this in" can never be conflated.
 */
class CorrectionsService
{
    public function __construct(
        private ?ArticleModel $articles = null,
        private ?ArticleRevisionModel $revisions = null,
        private ?ArticleCorrectionModel $corrections = null,
    ) {
        $this->articles ??= model(ArticleModel::class);
        $this->revisions ??= model(ArticleRevisionModel::class);
        $this->corrections ??= model(ArticleCorrectionModel::class);
    }

    /**
     * Record an edit to an article's headline/body, always writing a
     * revision snapshot of the PRE-edit content and, for substantive edits,
     * a public-facing correction entry.
     *
     * @throws InvalidArgumentException if $isSubstantive is true but no
     *                                   correction note was supplied.
     */
    public function recordEdit(
        Article $article,
        string $newHeadline,
        string $newBodyHtml,
        User $editor,
        bool $isSubstantive,
        ?string $correctionNote,
        DateTimeImmutable $now
    ): Article {
        if ($isSubstantive && (! is_string($correctionNote) || trim($correctionNote) === '')) {
            throw new InvalidArgumentException('A correction note is required for substantive edits.');
        }

        $nowFormatted = $now->format('Y-m-d H:i:s');

        // (a) Always snapshot the PRE-edit state into article_revisions.
        $this->revisions->insert([
            'article_id'         => $article->id,
            'editor_id'          => $editor->id,
            'status_at_revision' => $article->status,
            'headline'           => $article->headline,
            'body_html'          => $article->body_html,
            'is_substantive'     => $isSubstantive,
            'correction_note'    => $correctionNote,
            'created_at'         => $nowFormatted,
        ]);

        // (b) Substantive edits also get a public-facing correction record.
        if ($isSubstantive) {
            $this->corrections->insert([
                'article_id'      => $article->id,
                'corrected_by'    => $editor->id,
                'correction_note' => $correctionNote,
                'severity'        => 'substantial',
                'created_at'      => $nowFormatted,
            ]);
        }

        // (c) Update the live content. Only bump updated_at_content when the
        // change is substantive, or when the article is already published
        // (any edit to a live article should move its public dateModified).
        $update = [
            'headline'  => $newHeadline,
            'body_html' => $newBodyHtml,
        ];

        if ($isSubstantive || $article->status === 'published') {
            $update['updated_at_content'] = $nowFormatted;
        }

        $this->articles->update($article->id, $update);

        return $this->articles->find($article->id);
    }
}
