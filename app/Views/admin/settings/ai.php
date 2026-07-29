<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>

<div class="card" style="max-width:700px;">
    <h2 class="section-title">AI pipeline settings</h2>
    <p class="hint">
        Every field here overrides the deploy-time <code>.env</code> default the moment you save it.
        Leaving an API key field blank keeps whatever key is already set — it's never cleared or
        shown back to you once saved.
    </p>

    <?= form_open('admin/settings/ai') ?>
        <?= csrf_field() ?>

        <h3 class="section-title">Text generation</h3>

        <div class="form-group">
            <label for="ai_text_provider">Text provider</label>
            <select id="ai_text_provider" name="ai_text_provider">
                <option value="anthropic" <?= $values['ai_text_provider'] === 'anthropic' ? 'selected' : '' ?>>Anthropic</option>
                <option value="openai" <?= $values['ai_text_provider'] === 'openai' ? 'selected' : '' ?>>OpenAI</option>
            </select>
        </div>

        <div class="form-group">
            <label for="ai_anthropic_model">Anthropic model</label>
            <input type="text" id="ai_anthropic_model" name="ai_anthropic_model" required maxlength="100"
                   value="<?= esc($values['ai_anthropic_model'], 'attr') ?>">
        </div>

        <div class="form-group">
            <label for="ai_anthropic_api_key">Anthropic API key</label>
            <input type="password" id="ai_anthropic_api_key" name="ai_anthropic_api_key" autocomplete="off"
                   placeholder="Leave blank to keep current key">
            <p class="hint">Status: <?= esc($keyStatus['ai_anthropic_api_key']) ?></p>
        </div>

        <div class="form-group">
            <label for="ai_openai_model">OpenAI model (text)</label>
            <input type="text" id="ai_openai_model" name="ai_openai_model" required maxlength="100"
                   value="<?= esc($values['ai_openai_model'], 'attr') ?>">
        </div>

        <div class="form-group">
            <label for="ai_openai_api_key">OpenAI API key</label>
            <input type="password" id="ai_openai_api_key" name="ai_openai_api_key" autocomplete="off"
                   placeholder="Leave blank to keep current key">
            <p class="hint">Status: <?= esc($keyStatus['ai_openai_api_key']) ?> — shared between text and image generation below.</p>
        </div>

        <h3 class="section-title">Image generation</h3>

        <div class="form-group">
            <label for="ai_image_provider">Image provider</label>
            <select id="ai_image_provider" name="ai_image_provider">
                <option value="openai" <?= $values['ai_image_provider'] === 'openai' ? 'selected' : '' ?>>OpenAI</option>
                <option value="stability" <?= $values['ai_image_provider'] === 'stability' ? 'selected' : '' ?>>Stability AI</option>
            </select>
        </div>

        <div class="form-group">
            <label for="ai_openai_image_model">OpenAI image model</label>
            <input type="text" id="ai_openai_image_model" name="ai_openai_image_model" required maxlength="100"
                   value="<?= esc($values['ai_openai_image_model'], 'attr') ?>">
        </div>

        <div class="form-group">
            <label for="ai_stability_model">Stability model</label>
            <input type="text" id="ai_stability_model" name="ai_stability_model" required maxlength="100"
                   value="<?= esc($values['ai_stability_model'], 'attr') ?>">
        </div>

        <div class="form-group">
            <label for="ai_stability_api_key">Stability API key</label>
            <input type="password" id="ai_stability_api_key" name="ai_stability_api_key" autocomplete="off"
                   placeholder="Leave blank to keep current key">
            <p class="hint">Status: <?= esc($keyStatus['ai_stability_api_key']) ?></p>
        </div>

        <h3 class="section-title">Governance</h3>

        <div class="form-group">
            <label for="daily_generation_cap">Daily generation cap (article + style-pass jobs combined)</label>
            <input type="number" id="daily_generation_cap" name="daily_generation_cap" min="1" required value="<?= (int) $cap ?>">
            <p class="hint">Exists so throughput never outpaces editorial review capacity.</p>
        </div>

        <div class="form-group">
            <label for="ai_request_timeout_seconds">Request timeout (seconds)</label>
            <input type="number" id="ai_request_timeout_seconds" name="ai_request_timeout_seconds" min="1" required value="<?= (int) $timeout ?>">
        </div>

        <button type="submit" class="btn">Save</button>
    <?= form_close() ?>
</div>

<?= $this->endSection() ?>
