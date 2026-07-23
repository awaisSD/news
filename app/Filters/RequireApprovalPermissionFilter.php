<?php

namespace App\Filters;

use App\Models\ArticleModel;
use App\Models\UserModel;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Human-in-the-loop gate for the approve/publish actions.
 *
 * This is deliberately a separate permission check from RoleFilter: RoleFilter only
 * confirms the caller *is* an editor/admin, this filter is the seam where we can later
 * confirm the caller is an *appropriate* reviewer for this specific article (i.e. not
 * simply rubber-stamping their own AI-assisted draft).
 *
 * The hard gate enforced here is: role must be editor or admin. That alone is a real,
 * blocking security control (see RoleFilter, applied first on these routes).
 *
 * The soft governance check — flagging when the approving/publishing editor is also the
 * article's original author/assigned editor, which would make the review meaningless —
 * is logged as a warning only and does NOT block the request. Turning this into a hard
 * block (e.g. a DB constraint such as `approved_by != requested_by`) is a deliberate
 * future enhancement, intentionally not implemented now: small teams may legitimately
 * have only one editor available, and a hard block here would create outages for a
 * policy that is advisory, not a data-integrity requirement.
 */
class RequireApprovalPermissionFilter implements FilterInterface
{
    /**
     * @param array|null $arguments
     *
     * @return RequestInterface|ResponseInterface|string|void
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $userId = session('user_id');
        $user   = $userId === null ? null : model(UserModel::class)->find($userId);

        if ($user === null || ! in_array($user->role, ['editor', 'admin'], true)) {
            return service('response')
                ->setStatusCode(403)
                ->setBody('Forbidden — insufficient role.');
        }

        $articleId = $this->resolveArticleId($request);

        if ($articleId === null) {
            // Nothing to check against; the hard role gate above already applies.
            return;
        }

        $article = model(ArticleModel::class)->find($articleId);

        if ($article === null) {
            return;
        }

        $isSoleReviewer = ! empty($article->assigned_editor_id)
            && (int) $article->assigned_editor_id === (int) $user->id
            && ! empty($article->author_id)
            && (int) $article->author_id === (int) $user->id;

        if ($isSoleReviewer) {
            log_message(
                'warning',
                'Article #{articleId} was approved/published by user #{userId}, who is both the ' .
                'assigned editor and the original author — review may be effectively self-certified.',
                ['articleId' => $articleId, 'userId' => $user->id]
            );
        }
    }

    /**
     * Best-effort extraction of the numeric article id from the current route,
     * e.g. admin/articles/123/approve -> 123.
     */
    private function resolveArticleId(RequestInterface $request): ?int
    {
        $segments = $request->getUri()->getSegments();

        foreach ($segments as $index => $segment) {
            if ($segment === 'articles' && isset($segments[$index + 1]) && ctype_digit((string) $segments[$index + 1])) {
                return (int) $segments[$index + 1];
            }
        }

        return null;
    }

    /**
     * @param array|null $arguments
     *
     * @return ResponseInterface|void
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Nothing to do.
    }
}
