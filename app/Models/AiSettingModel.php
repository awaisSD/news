<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Simple key-value store for non-secret, admin-editable AI pipeline knobs
 * (e.g. daily generation cap override, default model). Secrets never live
 * here — see Config\AIPipeline. No Entity: callers just want a scalar
 * value back, not a hydrated object.
 *
 * Columns are named setting_key/setting_value (not key/value) because
 * `key` is a MySQL reserved word.
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
}
