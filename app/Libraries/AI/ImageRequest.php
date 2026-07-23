<?php

namespace App\Libraries\AI;

/**
 * DTO passed to ImageProviderInterface::generateImage().
 */
class ImageRequest
{
    public function __construct(
        public readonly string $prompt,
        public readonly int $width = 1200,
        public readonly int $height = 630,
    ) {
    }
}
