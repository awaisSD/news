<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Libraries\AI\ArticleGenerationService;
use App\Models\AiGenerationJobModel;
use App\Models\AuditLogModel;
use App\Models\TopicModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use Config\AIPipeline;
use DateTimeImmutable;

/**
 * Kicks off AI draft generation from a Topic. The actual provider call
 * happens asynchronously via the `php spark ai:process-queue` cron worker
 * — store() only ever creates a job row and returns immediately; the
 * editor is sent to a polling page (jobStatus()) rather than being made to
 * wait on the request.
 */
class GenerationController extends BaseController
{
    public function create()
    {
        $topics = model(TopicModel::class)
            ->whereIn('status', ['new', 'assigned'])
            ->orderBy('created_at', 'ASC')
            ->findAll();

        $now = new DateTimeImmutable();

        // The daily cap number shown here is the deploy-time default from
        // Config\AIPipeline. Whether generation is ACTUALLY blocked right
        // now is decided solely by ArticleGenerationService::dailyCapReached()
        // below (which may consult an admin override in ai_settings) —
        // this display figure is for operator visibility only, never used
        // to gate the store() action itself.
        $cap = config(AIPipeline::class)->dailyGenerationCap;

        $usedToday = model(AiGenerationJobModel::class)
            ->where('created_at >=', $now->format('Y-m-d') . ' 00:00:00')
            ->where('status !=', 'blocked_by_cap')
            ->countAllResults();

        $capReached = (new ArticleGenerationService())->dailyCapReached($now);

        return view('admin/generation/create', [
            'topics'     => $topics,
            'cap'        => $cap,
            'usedToday'  => $usedToday,
            'capReached' => $capReached,
        ]);
    }

    public function store()
    {
        $topicId = (int) $this->request->getPost('topic_id');
        $topic   = model(TopicModel::class)->find($topicId);

        if ($topic === null) {
            return redirect()->to(site_url('admin/generate'))->with('error', 'Please choose a valid topic.');
        }

        $job = (new ArticleGenerationService())->createJob($topic, $this->currentUser(), new DateTimeImmutable());

        $this->audit('generation.job_created', $job->id, null, [
            'topic_id' => $topic->id,
            'status'   => $job->status,
        ]);

        if ($job->status === 'blocked_by_cap') {
            return redirect()->to(site_url('admin/generate'))
                ->with('error', 'Daily generation cap reached — ask an admin to raise it in AI Settings, or wait until tomorrow.');
        }

        return redirect()->to(site_url('admin/generate/jobs/' . $job->id))
            ->with('success', 'Draft generation queued — it will appear in the Review Queue once the background worker processes it (usually within a couple minutes).');
    }

    public function jobStatus(int $jobId)
    {
        $job = model(AiGenerationJobModel::class)->find($jobId);

        if ($job === null) {
            throw PageNotFoundException::forPageNotFound('No such generation job.');
        }

        return view('admin/generation/job_status', ['job' => $job]);
    }

    private function audit(string $action, int $subjectId, ?array $before, ?array $after): void
    {
        $user = $this->currentUser();

        if ($user === null) {
            return;
        }

        model(AuditLogModel::class)->record(
            $user->id,
            $action,
            'ai_generation_job',
            $subjectId,
            $before,
            $after,
            $this->request->getIPAddress(),
            date('Y-m-d H:i:s')
        );
    }
}
