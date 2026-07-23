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
 * OpenAI Images API (https://api.openai.com/v1/images/generations) image
 * provider. Constructed with the shared \Config\AIPipeline instance —
 * never read env()/getenv() directly (see Services::imageProvider()).
 *
 * BEST EFFORT: the request/response shape below (endpoint, `size`,
 * `response_format: "b64_json"`, `data[0].b64_json`) matches OpenAI's
 * documented Images API contract as of this writing. Verify against
 * current OpenAI API docs before going live — in particular, confirm the
 * configured `$config->openAiImageModel` supports the `response_format`
 * parameter (some image models only return a hosted URL, never base64,
 * in which case this class would need to download the URL instead).
 */
class OpenAiImageProvider implements ImageProviderInterface
{
    private const API_URL = 'https://api.openai.com/v1/images/generations';

    /**
     * Supported output sizes for the configured model, as [width, height].
     * The requested width/height is mapped to whichever of these has the
     * closest aspect ratio.
     *
     * @var array<string, array{0: int, 1: int}>
     */
    private const SUPPORTED_SIZES = [
        '1024x1024' => [1024, 1024],
        '1792x1024' => [1792, 1024],
        '1024x1792' => [1024, 1792],
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
        [$size, $width, $height] = $this->nearestSupportedSize($request->width, $request->height);

        try {
            $response = $this->http->post(self::API_URL, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->config->openAiApiKey,
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'model'           => $this->config->openAiImageModel,
                    'prompt'          => $request->prompt,
                    'size'            => $size,
                    'n'               => 1,
                    'response_format' => 'b64_json',
                ],
            ]);
        } catch (GuzzleException $e) {
            throw new AiProviderException(
                'OpenAI Images API request failed: ' . $e->getMessage(),
                previous: $e
            );
        }

        $statusCode = $response->getStatusCode();
        $rawBody    = (string) $response->getBody();

        if ($statusCode < 200 || $statusCode >= 300) {
            throw new AiProviderException(
                "OpenAI Images API returned non-2xx status {$statusCode}."
            );
        }

        try {
            $decoded = json_decode($rawBody, true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new AiProviderException(
                'OpenAI Images API returned a response that was not valid JSON.',
                previous: $e
            );
        }

        $b64 = $decoded['data'][0]['b64_json'] ?? null;

        if (! is_string($b64) || $b64 === '') {
            throw new AiProviderException('OpenAI Images API response was missing data[0].b64_json.');
        }

        $binary = base64_decode($b64, true);

        if ($binary === false || $binary === '') {
            throw new AiProviderException('OpenAI Images API returned an unusable/empty image payload.');
        }

        // Don't persist the (potentially large) base64 payload itself into
        // the audit trail — keep response metadata to non-binary fields.
        unset($decoded['data'][0]['b64_json']);

        return new ImageResult(
            binaryData: $binary,
            mimeType: 'image/png',
            width: $width,
            height: $height,
            rawResponseMetadata: $decoded,
            costUsd: null,
        );
    }

    public function name(): string
    {
        return 'openai';
    }

    /**
     * @return array{0: string, 1: int, 2: int} [size string, width, height]
     */
    private function nearestSupportedSize(int $width, int $height): array
    {
        $targetRatio = $width / max($height, 1);

        $bestSize = '1024x1024';
        $bestDiff = null;

        foreach (self::SUPPORTED_SIZES as $size => [$w, $h]) {
            $diff = abs($targetRatio - ($w / $h));

            if ($bestDiff === null || $diff < $bestDiff) {
                $bestDiff = $diff;
                $bestSize = $size;
            }
        }

        [$w, $h] = self::SUPPORTED_SIZES[$bestSize];

        return [$bestSize, $w, $h];
    }
}
