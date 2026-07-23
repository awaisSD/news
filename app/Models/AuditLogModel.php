<?php

namespace App\Models;

use App\Entities\AuditLog;
use CodeIgniter\Model;

class AuditLogModel extends Model
{
    protected $table          = 'audit_log';
    protected $primaryKey     = 'id';
    protected $useAutoIncrement = true;
    protected $returnType     = AuditLog::class;
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'user_id',
        'action',
        'subject_type',
        'subject_id',
        'before_json',
        'after_json',
        'ip_address',
        'created_at',
    ];

    // Timestamps are handled explicitly via the $createdAt parameter on
    // record() rather than $useTimestamps, because this is a single
    // narrow write helper and callers (services throughout the app) are
    // expected to supply the moment of the audited action explicitly.
    protected $useTimestamps = false;

    protected $validationRules = [
        'action'       => 'required|max_length[100]',
        'subject_type' => 'required|max_length[100]',
        'subject_id'   => 'required|is_natural_no_zero',
    ];

    /**
     * Single write helper other services call to record an audited change.
     * $createdAt is supplied by the caller — this model never calls
     * date()/now() itself.
     */
    public function record(
        int $userId,
        string $action,
        string $subjectType,
        int $subjectId,
        ?array $before,
        ?array $after,
        ?string $ip,
        string $createdAt
    ): void {
        $this->insert([
            'user_id'      => $userId,
            'action'       => $action,
            'subject_type' => $subjectType,
            'subject_id'   => $subjectId,
            'before_json'  => $before === null ? null : json_encode($before),
            'after_json'   => $after === null ? null : json_encode($after),
            'ip_address'   => $ip,
            'created_at'   => $createdAt,
        ]);
    }
}
