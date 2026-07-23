<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\CorrectionsService;
use App\Models\ArticleModel;
use App\Models\ArticleRevisionModel;
use App\Models\AuditLogModel;
use App\Models\UserModel;
use DateTimeImmutable;

class RevisionController extends BaseController
{
    public function forArticle(int $articleId)
    {
        $article = model(ArticleModel::class)->find($articleId);

        if ($article === null) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException("Article #{$articleId} not found.");
        }

        // Newest-first, per ArticleRevisionModel::forArticle().
        $revisions = model(ArticleRevisionModel::class)->forArticle($articleId);

        // Word-level diffing is out of scope for this pass — this is a
        // deliberately simple line-based comparison against the next-older
        // snapshot (or the current live article for the newest revision),
        // good enough to spot what changed at a glance.
        // TODO: swap in a proper diff library (e.g. jfcherng/php-diff) for
        // word-level highlighting.
        $diffs = [];
        foreach ($revisions as $index => $revision) {
            $newerText = $index === 0
                ? strip_tags($article->body_html ?? '')
                : strip_tags($revisions[$index - 1]->body_html ?? '');

            $olderText = strip_tags($revision->body_html ?? '');

            $diffs[$revision->id] = $this->naiveLineDiff($olderText, $newerText);
        }

        $editorIds = array_unique(array_filter(array_map(static fn ($r) => $r->editor_id, $revisions)));
        $editorNames = [];
        if ($editorIds !== []) {
            foreach (model(UserModel::class)->find($editorIds) as $editor) {
                $editorNames[$editor->id] = $editor->name;
            }
        }

        return view('admin/revisions/index', [
            'title'       => 'Revisions — ' . $article->headline,
            'article'     => $article,
            'revisions'   => $revisions,
            'diffs'       => $diffs,
            'editorNames' => $editorNames,
        ]);
    }

    public function restore(int $revisionId)
    {
        $revision = model(ArticleRevisionModel::class)->find($revisionId);

        if ($revision === null) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException("Revision #{$revisionId} not found.");
        }

        $article = model(ArticleModel::class)->find($revision->article_id);

        if ($article === null) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException("Article #{$revision->article_id} not found.");
        }

        $before = ['headline' => $article->headline];
        $now     = new DateTimeImmutable();

        if ($article->status === 'published') {
            // The audited path for changing a published article's content —
            // this also snapshots the pre-restore state as a new revision.
            (new CorrectionsService())->recordEdit(
                $article,
                $revision->headline,
                $revision->body_html,
                $this->currentUser(),
                true,
                'Restored from a previous revision.',
                $now
            );
        } else {
            // Not yet published — a plain content update, no corrections
            // machinery needed pre-publish.
            model(ArticleModel::class)->update($article->id, [
                'headline'  => $revision->headline,
                'body_html' => $revision->body_html,
            ]);
        }

        model(AuditLogModel::class)->record(
            $this->currentUser()->id,
            'revision_restored',
            'article',
            $article->id,
            $before,
            ['headline' => $revision->headline, 'restored_from_revision_id' => $revision->id],
            $this->request->getIPAddress(),
            $now->format('Y-m-d H:i:s')
        );

        return redirect()->to('/admin/revisions/' . $article->id)->with('success', 'Revision restored.');
    }

    /**
     * @return array{added: string[], removed: string[]}
     */
    private function naiveLineDiff(string $older, string $newer): array
    {
        $olderLines = array_filter(array_map('trim', explode("\n", $older)));
        $newerLines = array_filter(array_map('trim', explode("\n", $newer)));

        return [
            'removed' => array_values(array_diff($olderLines, $newerLines)),
            'added'   => array_values(array_diff($newerLines, $olderLines)),
        ];
    }
}
