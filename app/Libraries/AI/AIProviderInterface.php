<?php

namespace App\Libraries\AI;

/**
 * Contract for a text-generation AI provider (article drafts and editorial
 * style-pass rewrites). Implementations MUST NOT write to the database or
 * flip any article's status — they only translate a GenerationRequest into
 * a provider API call and translate the response back into a
 * GenerationResult. Every result is a suggestion a human must review.
 */
interface AIProviderInterface
{
    /**
     * @throws AiProviderException on a non-2xx provider response or
     *                              malformed/unparseable provider output.
     */
    public function generateArticle(GenerationRequest $request): GenerationResult;

    /**
     * Short machine-readable identifier for this provider (e.g. 'anthropic',
     * 'openai') — stored in ai_generation_jobs.provider.
     */
    public function name(): string;
}
