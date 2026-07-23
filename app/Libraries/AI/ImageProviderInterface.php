<?php

namespace App\Libraries\AI;

/**
 * Contract for an image-generation AI provider. Implementations MUST NOT
 * write to the database, create media rows, or attach anything to a live
 * article — they only translate an ImageRequest into a provider API call
 * and translate the response back into raw image bytes. Every generated
 * image is a suggestion a human must explicitly approve before it becomes a
 * media row (see ImageGenerationService).
 */
interface ImageProviderInterface
{
    /**
     * @throws AiProviderException on a non-2xx provider response or an
     *                              unusable/empty image payload.
     */
    public function generateImage(ImageRequest $request): ImageResult;

    /**
     * Short machine-readable identifier for this provider (e.g. 'openai',
     * 'stability') — stored in ai_image_jobs.provider.
     */
    public function name(): string;
}
