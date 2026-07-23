<?php

namespace App\Libraries\AI;

/**
 * DTO returned by ImageProviderInterface::generateImage(). Represents a
 * SUGGESTION only — ImageGenerationService::process() persists the bytes to
 * disk but never attaches the result to a live article; that only happens
 * when a human approves the job via the admin ImageJobsController.
 */
class ImageResult
{
    /**
     * @param array<string, mixed> $rawResponseMetadata Raw provider response
     *                                                   metadata (minus the
     *                                                   API key and minus
     *                                                   the binary image
     *                                                   data itself).
     */
    public function __construct(
        public readonly string $binaryData,
        public readonly string $mimeType,
        public readonly int $width,
        public readonly int $height,
        public readonly array $rawResponseMetadata,
        public readonly ?float $costUsd = null,
    ) {
    }
}
