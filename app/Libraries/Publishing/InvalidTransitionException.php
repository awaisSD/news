<?php

namespace App\Libraries\Publishing;

/**
 * Thrown whenever code attempts an article status change that is not
 * permitted by App\Libraries\Publishing\ArticleWorkflow::TRANSITIONS.
 *
 * This is also reused by EditorialReviewService::recordCorrection() to
 * signal "wrong preconditions" outside of the pure status-machine (e.g.
 * attempting to record a correction on an article that isn't published).
 */
class InvalidTransitionException extends \RuntimeException
{
}
