<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AuditLogModel;
use App\Models\UserModel;

class AuditLogController extends BaseController
{
    public function index()
    {
        $model = model(AuditLogModel::class)->orderBy('created_at', 'DESC');

        $subjectType = $this->request->getGet('subject_type');
        $userId      = $this->request->getGet('user_id');

        if ($subjectType) {
            $model = $model->where('subject_type', $subjectType);
        }

        if ($userId) {
            $model = $model->where('user_id', (int) $userId);
        }

        $entries = $model->paginate(50);
        $pager   = $model->pager;

        $actorIds = array_unique(array_filter(array_map(static fn ($e) => $e->user_id, $entries)));
        $actorNames = [];
        if ($actorIds !== []) {
            foreach (model(UserModel::class)->find($actorIds) as $actor) {
                $actorNames[$actor->id] = $actor->name;
            }
        }

        return view('admin/audit-log/index', [
            'title'       => 'Audit log',
            'entries'     => $entries,
            'pager'       => $pager,
            'actorNames'  => $actorNames,
            'users'       => model(UserModel::class)->orderBy('name', 'ASC')->findAll(),
            'filters'     => ['subject_type' => $subjectType, 'user_id' => $userId],
        ]);
    }
}
