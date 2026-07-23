<?php

namespace App\Models;

use App\Entities\AiGenerationJob;
use CodeIgniter\Model;

class AiGenerationJobModel extends Model
{
    protected $table          = 'ai_generation_jobs';
    protected $primaryKey     = 'id';
    protected $useAutoIncrement = true;
    protected $returnType     = AiGenerationJob::class;
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'topic_id',
        'article_id',
        'job_type',
        'provider',
        'model',
        'status',
        'prompt_payload',
        'response_metadata',
        'cost_usd',
        'requested_by',
        'error_message',
        'locked_by',
        'locked_at',
        'started_at',
        'completed_at',
    ];

    // created_at only — started_at/completed_at are explicit lifecycle
    // columns managed by the caller, not a generic "updated_at".
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    protected $validationRules = [
        'job_type' => 'required|in_list[article,style_pass]',
        'provider' => 'required|max_length[50]',
        'status'   => 'required|in_list[pending,processing,completed,failed,cancelled,blocked_by_cap]',
    ];

    /**
     * Atomically claims the oldest pending job for the given worker using
     * a single `UPDATE ... ORDER BY ... LIMIT 1` (supported by MySQL,
     * unlike the query builder's update() which can't express ORDER BY/
     * LIMIT portably) so two workers can never claim the same row.
     *
     * $lockedAt is supplied by the caller (Command) — this model never
     * calls date()/now() itself.
     */
    public function claimNextPending(string $workerId, string $lockedAt): ?AiGenerationJob
    {
        $table = $this->db->protectIdentifiers($this->table, true, null, false);

        $sql = "UPDATE {$table}
                SET status = ?, locked_by = ?, locked_at = ?
                WHERE status = ?
                ORDER BY created_at ASC
                LIMIT 1";

        $this->db->query($sql, ['processing', $workerId, $lockedAt, 'pending']);

        return $this->where('status', 'processing')
            ->where('locked_by', $workerId)
            ->orderBy('locked_at', 'DESC')
            ->orderBy('id', 'DESC')
            ->first();
    }
}
