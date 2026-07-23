<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>

<div class="grid-2">
    <div class="card">
        <h2 class="section-title">Daily generation cap</h2>
        <?= form_open('admin/settings/ai') ?>
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="daily_generation_cap">Maximum generation jobs (article + style-pass combined) per day</label>
                <input type="number" id="daily_generation_cap" name="daily_generation_cap" min="1" required value="<?= (int) $cap ?>">
                <p class="hint">Exists so throughput never outpaces editorial review capacity. Overrides the deploy-time default below.</p>
            </div>
            <button type="submit" class="btn">Save</button>
        <?= form_close() ?>
    </div>

    <div class="card">
        <h2 class="section-title">Provider configuration</h2>
        <p class="flash flash-error" style="background:#fef3c7;color:#92400e;border-color:#fde68a;">
            Provider/API key selection is configured via server environment variables, not here.
        </p>
        <table class="admin-table">
            <tbody>
            <tr><th>Text provider</th><td><?= esc($pipeline->textProvider) ?></td></tr>
            <tr><th>Text model</th><td><?= esc($pipeline->textProvider === 'anthropic' ? $pipeline->anthropicModel : $pipeline->openAiModel) ?></td></tr>
            <tr><th>Image provider</th><td><?= esc($pipeline->imageProvider) ?></td></tr>
            <tr><th>Deploy-time default cap</th><td><?= (int) $pipeline->dailyGenerationCap ?></td></tr>
            <tr><th>Request timeout (s)</th><td><?= (int) $pipeline->requestTimeoutSeconds ?></td></tr>
            </tbody>
        </table>
    </div>
</div>

<?= $this->endSection() ?>
