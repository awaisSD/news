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
 * OpenAI Chat Completions API (https://api.openai.com/v1/chat/completions)
 * text provider, using JSON-mode output for a structured
 * headline/body_html/excerpt response. Constructed with the shared
 * \Config\AIPipeline instance — never read env()/getenv() directly (see
 * Services::aiProvider()).
 *
 * BEST EFFORT: the request/response shape below (endpoint, `response_format:
 * {type: "json_object"}`, `max_completion_tokens`, `choices[0].message.content`,
 * `usage.prompt_tokens`/`completion_tokens`) matches OpenAI's documented Chat
 * Completions contract as of this writing. Verify against current OpenAI API
 * docs before going live — in particular, confirm the configured
 * `$config->openAiModel` supports `max_completion_tokens` and JSON-mode
 * response_format (some models require the newer `/v1/responses` endpoint
 * instead; swap the endpoint/body shape here if so).
 */
class OpenAiProvider implements AIProviderInterface
{
    private const API_URL = 'https://api.openai.com/v1/chat/completions';

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
                    'Authorization' => 'Bearer ' . $this->config->openAiApiKey,
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'model'                 => $this->config->openAiModel,
                    'max_completion_tokens' => 4096,
                    'response_format'       => ['type' => 'json_object'],
                    'messages'              => [
                        ['role' => 'system', 'content' => $system],
                        ['role' => 'user', 'content' => $user],
                    ],
                ],
            ]);
        } catch (GuzzleException $e) {
            throw new AiProviderException(
                'OpenAI API request failed: ' . $e->getMessage(),
                previous: $e
            );
        }

        $statusCode = $response->getStatusCode();
        $rawBody    = (string) $response->getBody();

        if ($statusCode < 200 || $statusCode >= 300) {
            throw new AiProviderException(
                "OpenAI API returned non-2xx status {$statusCode}."
            );
        }

        try {
            $decoded = json_decode($rawBody, true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new AiProviderException(
                'OpenAI API returned a response that was not valid JSON.',
                previous: $e
            );
        }

        $text = $decoded['choices'][0]['message']['content'] ?? null;

        if (! is_string($text) || trim($text) === '') {
            throw new AiProviderException('OpenAI API response was missing choices[0].message.content.');
        }

        try {
            $parsed = json_decode($text, true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new AiProviderException(
                'OpenAI model output was not valid JSON (expected headline/body_html/excerpt).',
                previous: $e
            );
        }

        if (! is_array($parsed) || ! isset($parsed['headline'], $parsed['body_html'], $parsed['excerpt'])) {
            throw new AiProviderException(
                'OpenAI model output JSON is missing required keys (headline, body_html, excerpt).'
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
            // See AnthropicProvider — no per-token pricing table is
            // configured, so cost is left unknown rather than guessed.
            costUsd: null,
        );
    }

    public function name(): string
    {
        return 'openai';
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
}
