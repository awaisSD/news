<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AiSettingModel;
use App\Models\AuditLogModel;
use Config\AIPipeline;

class AiSettingsController extends BaseController
{
    /**
     * Non-secret keys, plain getValue()/setValue().
     */
    private const PLAIN_KEYS = [
        'daily_generation_cap',
        'ai_request_timeout_seconds',
        'ai_text_provider',
        'ai_image_provider',
        'ai_anthropic_model',
        'ai_openai_model',
        'ai_openai_image_model',
        'ai_stability_model',
    ];

    /**
     * Secrets — encrypted via getEncryptedValue()/setEncryptedValue(),
     * never redisplayed once set (the edit form only shows a status,
     * not the value; leaving the input blank on submit keeps it unchanged).
     */
    private const SECRET_KEYS = [
        'ai_anthropic_api_key',
        'ai_openai_api_key',
        'ai_stability_api_key',
    ];

    public function index()
    {
        $settings = model(AiSettingModel::class);
        $pipeline = config(AIPipeline::class);

        $cap = $settings->getValue('daily_generation_cap');
        $cap = $cap !== null && $cap !== '' ? (int) $cap : $pipeline->dailyGenerationCap;

        $timeout = $settings->getValue('ai_request_timeout_seconds');
        $timeout = $timeout !== null && $timeout !== '' ? (int) $timeout : $pipeline->requestTimeoutSeconds;

        return view('admin/settings/ai', [
            'title'    => 'AI settings',
            'pipeline' => $pipeline,
            'cap'      => $cap,
            'timeout'  => $timeout,
            'values'   => [
                'ai_text_provider'      => $settings->getValue('ai_text_provider') ?: $pipeline->textProvider,
                'ai_image_provider'     => $settings->getValue('ai_image_provider') ?: $pipeline->imageProvider,
                'ai_anthropic_model'    => $settings->getValue('ai_anthropic_model') ?: $pipeline->anthropicModel,
                'ai_openai_model'       => $settings->getValue('ai_openai_model') ?: $pipeline->openAiModel,
                'ai_openai_image_model' => $settings->getValue('ai_openai_image_model') ?: $pipeline->openAiImageModel,
                'ai_stability_model'    => $settings->getValue('ai_stability_model') ?: $pipeline->stabilityModel,
            ],
            'keyStatus' => [
                'ai_anthropic_api_key' => $settings->hasEncryptedValue('ai_anthropic_api_key')
                    ? 'Set via admin panel'
                    : ($pipeline->anthropicApiKey !== '' ? 'Set via .env' : 'Not set'),
                'ai_openai_api_key' => $settings->hasEncryptedValue('ai_openai_api_key')
                    ? 'Set via admin panel'
                    : ($pipeline->openAiApiKey !== '' ? 'Set via .env' : 'Not set'),
                'ai_stability_api_key' => $settings->hasEncryptedValue('ai_stability_api_key')
                    ? 'Set via admin panel'
                    : ($pipeline->stabilityApiKey !== '' ? 'Set via .env' : 'Not set'),
            ],
        ]);
    }

    public function update()
    {
        $rules = [
            'daily_generation_cap'       => 'required|is_natural_no_zero',
            'ai_request_timeout_seconds' => 'required|is_natural_no_zero',
            'ai_text_provider'           => 'required|in_list[anthropic,openai]',
            'ai_image_provider'          => 'required|in_list[openai,stability]',
            'ai_anthropic_model'         => 'required|max_length[100]',
            'ai_openai_model'            => 'required|max_length[100]',
            'ai_openai_image_model'      => 'required|max_length[100]',
            'ai_stability_model'         => 'required|max_length[100]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $settings = model(AiSettingModel::class);
        $before   = [];
        $after    = [];

        foreach (self::PLAIN_KEYS as $key) {
            $value = (string) $this->request->getPost($key);
            $before[$key] = $settings->getValue($key);
            $settings->setValue($key, $value);
            $after[$key] = $value;
        }

        // Secrets: only touch a key if a new, non-empty value was actually
        // typed. An empty field means "leave the current key alone" — never
        // interpret a blank submit as "clear this key."
        foreach (self::SECRET_KEYS as $key) {
            $value = trim((string) $this->request->getPost($key));

            if ($value !== '') {
                $settings->setEncryptedValue($key, $value);
                $after[$key] = 'updated';
            }
        }

        model(AuditLogModel::class)->record(
            $this->currentUser()->id,
            'updated',
            'ai_setting',
            (int) ($settings->where('setting_key', 'daily_generation_cap')->first()['id'] ?? 0) ?: 1,
            $before,
            $after, // API key values never appear here — only the literal string 'updated'
            $this->request->getIPAddress(),
            date('Y-m-d H:i:s')
        );

        return redirect()->to('/admin/settings/ai')->with('success', 'AI settings updated.');
    }
}
