<?php

namespace App\Models;

use App\Entities\TopicSource;
use CodeIgniter\Model;

class TopicSourceModel extends Model
{
    protected $table          = 'topic_sources';
    protected $primaryKey     = 'id';
    protected $useAutoIncrement = true;
    protected $returnType     = TopicSource::class;
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'source_name',
        'source_url',
        'title',
        'summary',
        'published_at_source',
        'fetched_at',
    ];

    // Single timestamp column only — no updated_at on this table.
    protected $useTimestamps = true;
    protected $createdField  = 'fetched_at';
    protected $updatedField  = '';

    protected $validationRules = [
        'source_name' => 'required|max_length[150]',
        'source_url'  => 'required|max_length[500]',
    ];

    /**
     * There is no topic_id column on topic_sources — the relationship is
     * one-directional via topics.source_ids (a JSON array of topic_source
     * ids), so a source row can be referenced by more than one topic and
     * carries no back-reference of its own. Look sources up by id list
     * instead, e.g. `forIds(json_decode($topic->source_ids, true) ?? [])`.
     *
     * @param int[] $ids
     * @return TopicSource[]
     */
    public function forIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return $this->whereIn('id', $ids)
            ->orderBy('fetched_at', 'DESC')
            ->findAll();
    }
}
