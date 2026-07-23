<?php $isNew = $user->id === null; ?>
<?= $this->extend('admin/layout/main') ?>

<?= $this->section('content') ?>

<div class="card" style="max-width:600px;">
    <?= form_open($isNew ? 'admin/users' : 'admin/users/' . $user->id, ['method' => $isNew ? 'post' : 'put']) ?>
        <?= csrf_field() ?>

        <div class="form-group">
            <label for="name">Name</label>
            <input type="text" id="name" name="name" required maxlength="150" value="<?= esc($user->name ?? '', 'attr') ?>">
        </div>

        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" id="email" name="email" required maxlength="191" value="<?= esc($user->email ?? '', 'attr') ?>">
        </div>

        <div class="form-group">
            <label for="role">Role</label>
            <select id="role" name="role" required>
                <?php foreach (['writer', 'editor', 'admin'] as $role): ?>
                    <option value="<?= esc($role, 'attr') ?>" <?= ($user->role ?? '') === $role ? 'selected' : '' ?>><?= esc(ucfirst($role)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="password"><?= $isNew ? 'Password' : 'New password (leave blank to keep current)' ?></label>
            <input type="password" id="password" name="password" minlength="8" <?= $isNew ? 'required' : '' ?>>
        </div>

        <div class="form-group">
            <label for="bio">Bio</label>
            <textarea id="bio" name="bio"><?= esc($user->bio ?? '') ?></textarea>
        </div>

        <div class="form-group">
            <label for="credentials">Credentials</label>
            <input type="text" id="credentials" name="credentials" value="<?= esc($user->credentials ?? '', 'attr') ?>">
        </div>

        <div class="form-group">
            <label for="twitter_handle">Twitter handle</label>
            <input type="text" id="twitter_handle" name="twitter_handle" value="<?= esc($user->twitter_handle ?? '', 'attr') ?>">
        </div>

        <div class="form-group">
            <label for="linkedin_url">LinkedIn URL</label>
            <input type="url" id="linkedin_url" name="linkedin_url" value="<?= esc($user->linkedin_url ?? '', 'attr') ?>">
        </div>

        <div class="form-group">
            <label><input type="checkbox" name="is_active" value="1" <?= ($user->is_active ?? true) ? 'checked' : '' ?>> Active</label>
        </div>

        <button type="submit" class="btn"><?= $isNew ? 'Create user' : 'Save changes' ?></button>
    <?= form_close() ?>
</div>

<?= $this->endSection() ?>
