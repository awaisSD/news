<?php

namespace App\Libraries\Publishing;

/**
 * Pure, stateless validator for article editorial-status transitions.
 *
 * This class performs NO database access and holds NO mutable state — it
 * exists purely so every code path that ever changes `articles.status`
 * (controllers, CLI commands, services) can ask the same single source of
 * truth "is this transition legal?" instead of re-implementing the rules.
 *
 * There is deliberately no transition that leads to `published` except via
 * `approved -> published`, and no transition reaches `approved` except via
 * `in_review -> approved`. This is what guarantees, structurally, that an
 * article can never be published without first passing through a human
 * approval step (see EditorialReviewService::approve() / ::publish()).
 */
class ArticleWorkflow
{
    /**
     * Map of fromStatus => list of allowed toStatuses.
     */
    public const TRANSITIONS = [
        'draft'             => ['in_review'],
        'in_review'         => ['changes_requested', 'rejected', 'approved'],
        'changes_requested' => ['in_review'],
        'approved'          => ['published'],
        'published'         => ['published'], // corrections don't change status away from published
        'corrected'         => [],
        'rejected'          => [],
        'retracted'         => [],
    ];

    /**
     * Whether moving an article from $from to $to is a legal transition.
     */
    public function canTransition(string $from, string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    /**
     * Same check as canTransition(), but throws instead of returning false.
     *
     * @throws InvalidTransitionException
     */
    public function assertCanTransition(string $from, string $to): void
    {
        if (! $this->canTransition($from, $to)) {
            throw new InvalidTransitionException(
                "Cannot transition article from '{$from}' to '{$to}'."
            );
        }
    }
}
