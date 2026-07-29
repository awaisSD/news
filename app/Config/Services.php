<?php

namespace Config;

use App\Libraries\AI\AIProviderInterface;
use App\Libraries\AI\ImageProviderInterface;
use App\Libraries\AI\Providers\AnthropicProvider;
use App\Libraries\AI\Providers\OpenAiImageProvider;
use App\Libraries\AI\Providers\OpenAiProvider;
use App\Libraries\AI\Providers\StabilityProvider;
use App\Models\AiSettingModel;
use CodeIgniter\Config\BaseService;
use RuntimeException;

/**
 * App-level service factories, resolved through Services::aiProvider()/
 * Services::imageProvider() everywhere (never `new AnthropicProvider(...)`
 * directly) so the concrete provider is swappable at runtime.
 *
 * Provider/model/API key resolution order: admin-configured value in the
 * ai_settings table (editable at /admin/settings/ai) if one has been set,
 * otherwise the .env-driven Config\AIPipeline deploy-time default. This
 * resolution deliberately happens HERE — at the point a provider is
 * actually instantiated — rather than inside Config\AIPipeline itself,
 * since Config classes can be constructed very early (including before
 * migrations have run on a fresh install) and a DB query inside a Config
 * constructor risks a chicken-and-egg failure. Confining the DB read to
 * this factory method means it only ever runs once the app is fully
 * bootstrapped and the ai_settings table is guaranteed to exist.
 *
 * Any method not defined here falls back to the framework's own
 * CodeIgniter\Config\Services (session, cache, request, etc.) via
 * BaseService's __callStatic magic.
 */
class Services extends BaseService
{
    public static function aiProvider(bool $getShared = true): AIProviderInterface
    {
        if ($getShared) {
            return static::getSharedInstance('aiProvider');
        }

        $config = static::resolveEffectivePipelineConfig();

        return match ($config->textProvider) {
            'anthropic' => new AnthropicProvider($config),
            'openai'    => new OpenAiProvider($config),
            default     => throw new RuntimeException('Unknown AI text provider: ' . $config->textProvider),
        };
    }

    public static function imageProvider(bool $getShared = true): ImageProviderInterface
    {
        if ($getShared) {
            return static::getSharedInstance('imageProvider');
        }

        $config = static::resolveEffectivePipelineConfig();

        return match ($config->imageProvider) {
            'openai'    => new OpenAiImageProvider($config),
            'stability' => new StabilityProvider($config),
            default     => throw new RuntimeException('Unknown AI image provider: ' . $config->imageProvider),
        };
    }

    /**
     * Builds an AIPipeline-shaped config object with ai_settings DB
     * overrides layered on top of the .env defaults. A clone (not the
     * shared config() instance) so nothing else observes these per-call
     * overrides.
     */
    private static function resolveEffectivePipelineConfig(): AIPipeline
    {
        /** @var AIPipeline $config */
        $config   = clone config(AIPipeline::class);
        $settings = model(AiSettingModel::class);

        $config->textProvider  = $settings->getValue('ai_text_provider') ?: $config->textProvider;
        $config->imageProvider = $settings->getValue('ai_image_provider') ?: $config->imageProvider;

        $config->anthropicModel  = $settings->getValue('ai_anthropic_model') ?: $config->anthropicModel;
        $config->openAiModel     = $settings->getValue('ai_openai_model') ?: $config->openAiModel;
        $config->openAiImageModel = $settings->getValue('ai_openai_image_model') ?: $config->openAiImageModel;
        $config->stabilityModel  = $settings->getValue('ai_stability_model') ?: $config->stabilityModel;

        $config->anthropicApiKey = $settings->getEncryptedValue('ai_anthropic_api_key') ?: $config->anthropicApiKey;
        $config->openAiApiKey    = $settings->getEncryptedValue('ai_openai_api_key') ?: $config->openAiApiKey;
        $config->stabilityApiKey = $settings->getEncryptedValue('ai_stability_api_key') ?: $config->stabilityApiKey;

        $timeout = $settings->getValue('ai_request_timeout_seconds');
        $config->requestTimeoutSeconds = $timeout !== null && $timeout !== '' ? (int) $timeout : $config->requestTimeoutSeconds;

        return $config;
    }
}
