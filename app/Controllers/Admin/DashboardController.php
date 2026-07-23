<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AiGenerationJobModel;
use App\Models\AiSettingModel;
use App\Models\ArticleModel;
use App\Models\AuditLogModel;
use App\Models\UserModel;
use Config\AIPipeline;

class DashboardController extends BaseController
{
    /**
     * Every legal article status (App\Libraries\Publishing\ArticleWorkflow),
     * duplicated here only for the purpose of always showing a zero-count
     * row on the dashboard rather than silently omitting empty buckets.
     */
    private const STATUSES = [
        'draft', 'in_review', 'changes_requested', 'approved',
        'published', 'corrected', 'rejected', 'retracted',
    ];

    public function index()
    {
        $articleModel = model(ArticleModel::class);

        $statusCounts = [];
        foreach (self::STATUSES as $status) {
            $statusCounts[$status] = model(ArticleModel::class)
                ->where('status', $status)
                ->countAllResults();
        }

        $todayStart = date('Y-m-d') . ' 00:00:00';
        $jobsToday  = model(AiGenerationJobModel::class)
            ->where('created_at >=', $todayStart)
            ->countAllResults();

        $cap = model(AiSettingModel::class)->getValue('daily_generation_cap');
        $cap = $cap !== null && $cap !== '' ? (int) $cap : config(AIPipeline::class)->dailyGenerationCap;

        $reviewQueueSize = count($articleModel->listForReviewQueue());

        $recentAuditLog = model(AuditLogModel::class)
            ->orderBy('created_at', 'DESC')
            ->findAll(10);

        $actorIds = array_unique(array_filter(array_map(
            static fn ($entry) => $entry->user_id,
            $recentAuditLog
        )));

        $actorNames = [];
        if ($actorIds !== []) {
            foreach (model(UserModel::class)->find($actorIds) as $actor) {
                $actorNames[$actor->id] = $actor->name;
            }
        }

        return view('admin/dashboard', [
            'title'           => 'Dashboard',
            'statusCounts'    => $statusCounts,
            'jobsToday'       => $jobsToday,
            'dailyCap'        => $cap,
            'reviewQueueSize' => $reviewQueueSize,
            'recentAuditLog'  => $recentAuditLog,
            'actorNames'      => $actorNames,
        ]);
    }
}
