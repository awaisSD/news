<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * AI provider selection and non-secret pipeline tuning.
 *
 * Consumed exclusively through Services::aiProvider()/Services::imageProvider()
 * (see Config\Services) — provider classes never read env()/getenv() directly,
 * so adding/swapping a provider is a config change here, not a code change at
 * call sites. Secrets live only in .env / host environment variables, never
 * in the ai_settings database table (that table holds non-secret,
 * admin-editable knobs such as the daily generation cap and default model).
 */
class AIPipeline extends BaseConfig
{
    public string $textProvider = 'anthropic';

    public string $anthropicApiKey = '';
    public string $anthropicModel  = 'claude-sonnet-4-5';

    public string $openAiApiKey = '';
    public string $openAiModel  = 'gpt-5';

    public string $imageProvider = 'openai';

    public string $openAiImageModel = 'gpt-image-1';

    public string $stabilityApiKey = '';
    public string $stabilityModel  = 'stable-image-ultra';

    /**
     * Hard ceiling on how many generation jobs (article + style-pass
     * combined) may be created per day, enforced in
     * Libraries\AI\ArticleGenerationService before a job row is inserted.
     * Exists so throughput never outpaces what the editorial team can
     * genuinely review — the human-in-the-loop requirement is only real
     * if review capacity, not just generation capacity, bounds volume.
     * Admin-overridable at runtime via the ai_settings table; this value
     * is only the deploy-time default.
     */
    public int $dailyGenerationCap = 10;

    public int $requestTimeoutSeconds = 120;

    public function __construct()
    {
        parent::__construct();

        $this->textProvider          = env('ai.textProvider', $this->textProvider);
        $this->anthropicApiKey       = env('ai.anthropic.apiKey', $this->anthropicApiKey);
        $this->anthropicModel        = env('ai.anthropic.model', $this->anthropicModel);
        $this->openAiApiKey          = env('ai.openai.apiKey', $this->openAiApiKey);
        $this->openAiModel           = env('ai.openai.model', $this->openAiModel);
        $this->imageProvider         = env('ai.imageProvider', $this->imageProvider);
        $this->openAiImageModel      = env('ai.openai.imageModel', $this->openAiImageModel);
        $this->stabilityApiKey       = env('ai.stability.apiKey', $this->stabilityApiKey);
        $this->stabilityModel        = env('ai.stability.model', $this->stabilityModel);
        $this->dailyGenerationCap    = (int) env('ai.dailyGenerationCap', $this->dailyGenerationCap);
        $this->requestTimeoutSeconds = (int) env('ai.requestTimeoutSeconds', $this->requestTimeoutSeconds);
    }
}
