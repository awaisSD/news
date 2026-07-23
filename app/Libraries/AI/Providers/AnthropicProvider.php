<?php

namespace App\Libraries\AI\Providers;

use App\Libraries\AI\AIProviderInterface;
use App\Libraries\AI\AiProviderException;
use App\Libraries\AI\GenerationRequest;
use App\Libraries\AI\GenerationResult;
use Config\AIPipeline;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Anthropic Messages API (https://api.anthropic.com/v1/messages) text
 * provider. Constructed with the shared \Config\AIPipeline instance —
 * never read env()/getenv() directly (see Services::aiProvider()).
 *
 * BEST EFFORT: the Messages API request/response shape below (endpoint,
 * headers, `content` array of text blocks, `usage.input_tokens`/
 * `output_tokens`) matches the documented wire contract as of this
 * writing. Verify against current Anthropic API docs before going live,
 * particularly if the account uses a non-default `anthropic-version`.
 */
class AnthropicProvider implements AIProviderInterface
{
    private const API_URL     = 'https://api.anthropic.com/v1/messages';
    private const API_VERSION = '2023-06-01';

    private Client $http;

    public function __construct(private readonly AIPipeline $config)
    {
        $this->http = new Client([
            'timeout' => $this->config->requestTimeoutSeconds,
        ]);
    }

    public function generateArticle(GenerationRequest $request): GenerationResult
    {
        [$system, $user] = $this->buildPrompt($request);

        try {
            $response = $this->http->post(self::API_URL, [
                'headers' => [
                    'x-api-key'         => $this->config->anthropicApiKey,
                    'anthropic-version' => self::API_VERSION,
                    'content-type'      => 'application/json',
                ],
                'json' => [
                    'model'      => $this->config->anthropicModel,
                    'max_tokens' => 4096,
                    'system'     => $system,
                    'messages'   => [
                        ['role' => 'user', 'content' => $user],
                    ],
                ],
            ]);
        } catch (GuzzleException $e) {
            // Never leak the API key (it lives only in the request headers
            // Guzzle built internally, not in the exception message) or the
            // full request/response body into the exception message.
            throw new AiProviderException(
                'Anthropic API request failed: ' . $e->getMessage(),
                previous: $e
            );
        }

        $statusCode = $response->getStatusCode();
        $rawBody    = (string) $response->getBody();

        if ($statusCode < 200 || $statusCode >= 300) {
            throw new AiProviderException(
                "Anthropic API returned non-2xx status {$statusCode}."
            );
        }

        try {
            $decoded = json_decode($rawBody, true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new AiProviderException(
                'Anthropic API returned a response that was not valid JSON.',
                previous: $e
            );
        }

        $text = $this->extractText($decoded);

        try {
            $parsed = json_decode($text, true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new AiProviderException(
                'Anthropic model output was not valid JSON (expected headline/body_html/excerpt).',
                previous: $e
            );
        }

        if (! is_array($parsed) || ! isset($parsed['headline'], $parsed['body_html'], $parsed['excerpt'])) {
            throw new AiProviderException(
                'Anthropic model output JSON is missing required keys (headline, body_html, excerpt).'
            );
        }

        $bodyHtml  = (string) $parsed['body_html'];
        $wordCount = str_word_count(strip_tags($bodyHtml));

        return new GenerationResult(
            headline: (string) $parsed['headline'],
            bodyHtml: $bodyHtml,
            excerpt: (string) $parsed['excerpt'],
            wordCount: $wordCount,
            rawResponseMetadata: $decoded,
            // No per-token pricing table is configured for this provider,
            // so cost is left unknown here rather than guessed. Populate
            // this once Config\AIPipeline (or ai_settings) carries a
            // documented $/token rate for the configured model.
            costUsd: null,
        );
    }

    public function name(): string
    {
        return 'anthropic';
    }

    /**
     * @return array{0: string, 1: string} [system prompt, user prompt]
     */
    private function buildPrompt(GenerationRequest $request): array
    {
        if ($request->mode === 'style_pass') {
            $system = 'You are a house-style editorial assistant for a news publication. '
                . 'You perform a style/readability pass on an already-written draft. '
                . 'You NEVER invent new facts, figures, quotes, or claims, and you NEVER remove '
                . 'facts present in the original draft — you only adjust tone, phrasing, sentence '
                . 'structure, and readability to match the house style notes provided. '
                . 'Respond with ONLY a single JSON object (no markdown fences, no commentary) with '
                . 'exactly these keys: "headline" (string), "body_html" (string, semantic HTML using '
                . 'only <p>, <h2>, <h3>, <ul>/<li>, and <blockquote> tags), and "excerpt" (string, a '
                . 'one-to-two sentence summary).';

            $user = "House style notes to apply:\n{$request->houseStyleNotes}\n\n"
                . "Current headline:\n{$request->topicTitle}\n\n"
                . "Current draft body (rewrite this for house style, preserving every fact):\n{$request->brief}";

            return [$system, $user];
        }

        $system = 'You are a news writing assistant for a professional news publication. '
            . 'You write an ORIGINAL, factual, well-structured news or explainer article based on '
            . 'the topic, brief, and angle supplied by a human editor. Do NOT reproduce wording from '
            . 'any source verbatim — write entirely in your own words, synthesizing the facts into a '
            . 'new, original piece of writing. Use clean semantic HTML for the body, using ONLY the '
            . 'following tags: <p>, <h2>, <h3>, <ul>/<li>, and <blockquote>. Do not include a top-level '
            . '<h1> (the headline is returned separately). '
            . 'Respond with ONLY a single JSON object (no markdown fences, no commentary) with exactly '
            . 'these keys: "headline" (string), "body_html" (string), and "excerpt" (string, a '
            . 'one-to-two sentence summary suitable for a listing page).';

        $user = "Topic: {$request->topicTitle}\n\n"
            . "Brief: {$request->brief}\n\n"
            . 'Angle notes: ' . ($request->angleNotes ?? '(none provided)') . "\n\n"
            . "Target category: {$request->targetCategorySlug}\n"
            . "Target length: approximately {$request->wordCountTarget} words.";

        return [$system, $user];
    }

    /**
     * Concatenates every `text`-type content block in an Anthropic Messages
     * API response into a single string.
     *
     * @param array<string, mixed> $decoded
     */
    private function extractText(array $decoded): string
    {
        if (! isset($decoded['content']) || ! is_array($decoded['content'])) {
            throw new AiProviderException('Anthropic API response was missing a "content" array.');
        }

        $text = '';

        foreach ($decoded['content'] as $block) {
            if (is_array($block) && ($block['type'] ?? null) === 'text') {
                $text .= (string) ($block['text'] ?? '');
            }
        }

        if (trim($text) === '') {
            throw new AiProviderException('Anthropic API response contained no text content blocks.');
        }

        return $text;
    }
}
