<?php

namespace App\Libraries\AI\Providers;

use App\Libraries\AI\AiProviderException;
use App\Libraries\AI\ImageProviderInterface;
use App\Libraries\AI\ImageRequest;
use App\Libraries\AI\ImageResult;
use Config\AIPipeline;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Stability AI v2beta image-generation REST API image provider.
 * Constructed with the shared \Config\AIPipeline instance — never read
 * env()/getenv() directly (see Services::imageProvider()).
 *
 * BEST EFFORT — VERIFY BEFORE GOING LIVE: Stability's v2beta REST API
 * uses multipart/form-data requests (even for text-only fields) and
 * distinguishes endpoints by generation tier (e.g. `/generate/ultra`,
 * `/generate/core`, `/generate/sd3`) rather than a single endpoint that
 * takes an arbitrary `model` string. This implementation targets the
 * `/v2beta/stable-image/generate/sd3` endpoint, which accepts a `model`
 * field (e.g. `sd3.5-large`) — mapped here from `$config->stabilityModel`.
 * If the configured model string corresponds to a fixed-model tier instead
 * (e.g. "ultra" or "core", which do NOT take a `model` field), switch the
 * endpoint accordingly and drop the `model` form field. Confirm the exact
 * endpoint path, accepted `model` values, and `aspect_ratio` enum against
 * current Stability AI API docs before going live.
 */
class StabilityProvider implements ImageProviderInterface
{
    private const API_URL = 'https://api.stability.ai/v2beta/stable-image/generate/sd3';

    /**
     * Aspect ratios accepted by the Stability v2beta image endpoints, as
     * width/height ratios, used to map an arbitrary requested width/height
     * onto the nearest supported enum value.
     *
     * @var array<string, float>
     */
    private const SUPPORTED_ASPECT_RATIOS = [
        '21:9' => 21 / 9,
        '16:9' => 16 / 9,
        '3:2'  => 3 / 2,
        '5:4'  => 5 / 4,
        '1:1'  => 1.0,
        '4:5'  => 4 / 5,
        '2:3'  => 2 / 3,
        '9:16' => 9 / 16,
        '9:21' => 9 / 21,
    ];

    private Client $http;

    public function __construct(private readonly AIPipeline $config)
    {
        $this->http = new Client([
            'timeout' => $this->config->requestTimeoutSeconds,
        ]);
    }

    public function generateImage(ImageRequest $request): ImageResult
    {
        $aspectRatio = $this->nearestAspectRatio($request->width, $request->height);

        try {
            $response = $this->http->post(self::API_URL, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->config->stabilityApiKey,
                    // Requesting raw image bytes directly is the simplest
                    // integration — avoids a second base64 decode step and
                    // an extra hop for a hosted URL.
                    'Accept' => 'image/*',
                ],
                'multipart' => [
                    ['name' => 'prompt', 'contents' => $request->prompt],
                    ['name' => 'model', 'contents' => $this->config->stabilityModel],
                    ['name' => 'aspect_ratio', 'contents' => $aspectRatio],
                    ['name' => 'output_format', 'contents' => 'png'],
                ],
            ]);
        } catch (GuzzleException $e) {
            throw new AiProviderException(
                'Stability AI API request failed: ' . $e->getMessage(),
                previous: $e
            );
        }

        $statusCode = $response->getStatusCode();

        if ($statusCode < 200 || $statusCode >= 300) {
            throw new AiProviderException(
                "Stability AI API returned non-2xx status {$statusCode}."
            );
        }

        $binary = (string) $response->getBody();

        if ($binary === '') {
            throw new AiProviderException('Stability AI API returned an empty image payload.');
        }

        $contentType = $response->getHeaderLine('Content-Type');
        $mimeType    = $contentType !== '' ? explode(';', $contentType)[0] : 'image/png';

        [$width, $height] = $this->dimensionsForAspectRatio($aspectRatio, $request->width, $request->height);

        return new ImageResult(
            binaryData: $binary,
            mimeType: trim($mimeType),
            width: $width,
            height: $height,
            // No JSON body is returned alongside raw image bytes (Accept:
            // image/*), so there is no structured metadata to record beyond
            // what we already know about the request.
            rawResponseMetadata: [
                'aspect_ratio' => $aspectRatio,
                'model'        => $this->config->stabilityModel,
            ],
            costUsd: null,
        );
    }

    public function name(): string
    {
        return 'stability';
    }

    private function nearestAspectRatio(int $width, int $height): string
    {
        $targetRatio = $width / max($height, 1);

        $bestRatio = '1:1';
        $bestDiff  = null;

        foreach (self::SUPPORTED_ASPECT_RATIOS as $label => $ratio) {
            $diff = abs($targetRatio - $ratio);

            if ($bestDiff === null || $diff < $bestDiff) {
                $bestDiff  = $diff;
                $bestRatio = $label;
            }
        }

        return $bestRatio;
    }

    /**
     * The API does not report the generated pixel dimensions in a JSON body
     * when raw bytes are requested, so approximate them by scaling the
     * originally requested width to the chosen aspect ratio.
     *
     * @return array{0: int, 1: int}
     */
    private function dimensionsForAspectRatio(string $aspectRatio, int $requestedWidth, int $requestedHeight): array
    {
        $ratio = self::SUPPORTED_ASPECT_RATIOS[$aspectRatio] ?? ($requestedWidth / max($requestedHeight, 1));

        $width  = $requestedWidth;
        $height = (int) round($width / $ratio);

        return [$width, $height];
    }
}
