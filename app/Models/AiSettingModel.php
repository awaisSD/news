<?php

namespace App\Models;

use CodeIgniter\Model;
use Config\Services;

/**
 * Key-value store for admin-editable AI pipeline settings: provider/model
 * choice, the daily generation cap, request timeout, and (encrypted)
 * provider API keys. Deploy-time .env values in Config\AIPipeline remain
 * the fallback for every one of these — see Config\Services::aiProvider()/
 * imageProvider(), which resolve "DB override if set, else .env default"
 * at the point a provider is actually instantiated. No Entity: callers
 * just want a scalar value back, not a hydrated object.
 *
 * Columns are named setting_key/setting_value (not key/value) because
 * `key` is a MySQL reserved word.
 *
 * API keys are stored via getEncryptedValue()/setEncryptedValue(), never
 * getValue()/setValue() directly — those encrypt with Config\Encryption
 * (see .env's encryption.key, generated via `php spark key:generate`)
 * before the ciphertext ever touches the database.
 */
class AiSettingModel extends Model
{
    protected $table          = 'ai_settings';
    protected $primaryKey     = 'id';
    protected $useAutoIncrement = true;
    protected $returnType     = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'setting_key',
        'setting_value',
    ];

    protected $useTimestamps = true;
    protected $createdField  = '';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'setting_key' => 'required|max_length[150]',
    ];

    public function getValue(string $key, $default = null)
    {
        $row = $this->where('setting_key', $key)->first();

        return $row['setting_value'] ?? $default;
    }

    public function setValue(string $key, string $value): void
    {
        $existing = $this->where('setting_key', $key)->first();

        if ($existing === null) {
            $this->insert([
                'setting_key'   => $key,
                'setting_value' => $value,
            ]);

            return;
        }

        $this->update($existing['id'], [
            'setting_value' => $value,
        ]);
    }

    /**
     * Decrypts and returns a previously-encrypted setting (e.g. an API key),
     * or null if it was never set. Returns null (rather than throwing) if
     * decryption fails — e.g. encryption.key was rotated without migrating
     * old values — so a bad/rotated key degrades to "not set" instead of a
     * 500, letting Services::aiProvider() fall back to the .env default.
     */
    public function getEncryptedValue(string $key): ?string
    {
        $stored = $this->getValue($key);

        if ($stored === null || $stored === '') {
            return null;
        }

        try {
            return Services::encrypter()->decrypt(base64_decode($stored, true));
        } catch (\Throwable $e) {
            log_message('error', 'AiSettingModel: failed to decrypt "{key}": {message}', [
                'key'     => $key,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Encrypts $plaintext with Config\Encryption before storing it — the
     * database only ever holds ciphertext (base64-encoded, since the raw
     * cipher output isn't safe to store in a utf8mb4 TEXT column as-is).
     */
    public function setEncryptedValue(string $key, string $plaintext): void
    {
        $ciphertext = base64_encode(Services::encrypter()->encrypt($plaintext));

        $this->setValue($key, $ciphertext);
    }

    /**
     * Whether an encrypted value has ever been stored for $key — used by
     * the settings UI to show "currently set" without ever decrypting/
     * displaying the actual key.
     */
    public function hasEncryptedValue(string $key): bool
    {
        $stored = $this->getValue($key);

        return $stored !== null && $stored !== '';
    }
}
