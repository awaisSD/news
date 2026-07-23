<?php

namespace Config;

use App\Libraries\AI\AIProviderInterface;
use App\Libraries\AI\ImageProviderInterface;
use App\Libraries\AI\Providers\AnthropicProvider;
use App\Libraries\AI\Providers\OpenAiImageProvider;
use App\Libraries\AI\Providers\OpenAiProvider;
use App\Libraries\AI\Providers\StabilityProvider;
use CodeIgniter\Config\BaseService;
use RuntimeException;

/**
 * App-level service factories, resolved through Services::aiProvider()/
 * Services::imageProvider() everywhere (never `new AnthropicProvider(...)`
 * directly) so the concrete provider is swappable via Config\AIPipeline /
 * .env alone. Any method not defined here falls back to the framework's
 * own CodeIgniter\Config\Services (session, cache, request, etc.) via
 * BaseService's __callStatic magic.
 */
class Services extends BaseService
{
    public static function aiProvider(bool $getShared = true): AIProviderInterface
    {
        if ($getShared) {
            return static::getSharedInstance('aiProvider');
        }

        /** @var AIPipeline $config */
        $config = config(AIPipeline::class);

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

        /** @var AIPipeline $config */
        $config = config(AIPipeline::class);

        return match ($config->imageProvider) {
            'openai'    => new OpenAiImageProvider($config),
            'stability' => new StabilityProvider($config),
            default     => throw new RuntimeException('Unknown AI image provider: ' . $config->imageProvider),
        };
    }
}
