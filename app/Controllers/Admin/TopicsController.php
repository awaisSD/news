<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Entities\Topic;
use App\Libraries\AI\TopicDiscoveryService;
use App\Models\AuditLogModel;
use App\Models\TopicModel;
use App\Models\TopicSourceModel;
use App\Models\UserModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use DateTimeImmutable;

/**
 * Resourceful controller behind $routes->resource('topics', ...) in
 * app/Config/Routes.php: index, new, create, edit, update, show, delete.
 */
class TopicsController extends BaseController
{
    private const STATUSES = ['new', 'assigned', 'in_generation', 'used', 'archived'];

    public function index()
    {
        $status = (string) ($this->request->getGet('status') ?? '');

        $builder = model(TopicModel::class)->orderBy('created_at', 'DESC');

        if ($status !== '' && in_array($status, self::STATUSES, true)) {
            $builder = $builder->where('status', $status);
        }

        return view('admin/topics/index', [
            'topics'       => $builder->findAll(),
            'statuses'     => self::STATUSES,
            'statusFilter' => $status,
        ]);
    }

    public function new()
    {
        return view('admin/topics/edit', [
            'topic'    => new Topic(),
            'editors'  => $this->editorOptions(),
            'statuses' => self::STATUSES,
            'isNew'    => true,
        ]);
    }

    /**
     * Creates a manually-entered topic (as opposed to one discovered via
     * RSS/trending feeds). $brief is an editorial brief/angle for what the
     * generated draft should say — NOT a place to paste a competitor
     * article to rewrite; see the placeholder copy in the view.
     */
    public function create()
    {
        $title      = trim((string) $this->request->getPost('title'));
        $brief      = trim((string) $this->request->getPost('brief'));
        $angleNotes = trim((string) $this->request->getPost('angle_notes'));
        $editorRaw  = $this->request->getPost('assigned_editor_id');

        if ($title === '' || $brief === '') {
            return redirect()->back()->withInput()->with('error', 'Title and editorial brief are both required.');
        }

        $assignedEditorId = ($editorRaw !== null && $editorRaw !== '') ? (int) $editorRaw : null;

        $topic = (new TopicDiscoveryService())->manualTopic(
            $title,
            $brief,
            $angleNotes !== '' ? $angleNotes : null,
            $assignedEditorId,
            new DateTimeImmutable()
        );

        $this->audit('topic.create', $topic->id, null, [
            'title'  => $topic->title,
            'status' => $topic->status,
        ]);

        return redirect()->to(site_url('admin/topics/' . $topic->id))->with('success', 'Topic created.');
    }

    public function edit($id = null)
    {
        $topic = model(TopicModel::class)->find((int) $id);

        if ($topic === null) {
            throw PageNotFoundException::forPageNotFound('No such topic.');
        }

        return view('admin/topics/edit', [
            'topic'    => $topic,
            'editors'  => $this->editorOptions(),
            'statuses' => self::STATUSES,
            'isNew'    => false,
        ]);
    }

    public function update($id = null)
    {
        $model = model(TopicModel::class);
        $topic = $model->find((int) $id);

        if ($topic === null) {
            throw PageNotFoundException::forPageNotFound('No such topic.');
        }

        $title      = trim((string) $this->request->getPost('title'));
        $brief      = trim((string) $this->request->getPost('brief'));
        $angleNotes = trim((string) $this->request->getPost('angle_notes'));
        $editorRaw  = $this->request->getPost('assigned_editor_id');
        $status     = (string) $this->request->getPost('status');

        if ($title === '' || $brief === '') {
            return redirect()->back()->withInput()->with('error', 'Title and editorial brief are both required.');
        }

        if (! in_array($status, self::STATUSES, true)) {
            return redirect()->back()->withInput()->with('error', 'Invalid status.');
        }

        $before = $topic->toArray();

        $data = [
            'title'              => $title,
            'brief'              => $brief,
            'angle_notes'        => $angleNotes !== '' ? $angleNotes : null,
            'assigned_editor_id' => ($editorRaw !== null && $editorRaw !== '') ? (int) $editorRaw : null,
            'status'             => $status,
        ];

        $model->update($topic->id, $data);

        $this->audit('topic.update', $topic->id, $before, $data);

        return redirect()->to(site_url('admin/topics/' . $topic->id))->with('success', 'Topic updated.');
    }

    public function show($id = null)
    {
        $topic = model(TopicModel::class)->find((int) $id);

        if ($topic === null) {
            throw PageNotFoundException::forPageNotFound('No such topic.');
        }

        // NOTE: topic_sources has no topic_id column (see its migration,
        // 2024-01-01-000006_CreateTopicSourcesTable) — TopicSourceModel::
        // forTopic() filters on a column that doesn't exist on this table,
        // so it is NOT used here. Topics reference their sources via the
        // topic.source_ids JSON array of topic_source primary keys instead,
        // so sources are looked up by id directly.
        $sourceIds = $topic->source_ids ?? [];
        $sources   = $sourceIds !== []
            ? model(TopicSourceModel::class)->whereIn('id', $sourceIds)->findAll()
            : [];

        $assignedEditor = $topic->assigned_editor_id !== null
            ? model(UserModel::class)->find($topic->assigned_editor_id)
            : null;

        return view('admin/topics/show', [
            'topic'          => $topic,
            'sources'        => $sources,
            'assignedEditor' => $assignedEditor,
        ]);
    }

    public function delete($id = null)
    {
        $model = model(TopicModel::class);
        $topic = $model->find((int) $id);

        if ($topic === null) {
            throw PageNotFoundException::forPageNotFound('No such topic.');
        }

        $before = $topic->toArray();
        $model->delete($topic->id);

        $this->audit('topic.delete', $topic->id, $before, null);

        return redirect()->to(site_url('admin/topics'))->with('success', 'Topic deleted.');
    }

    /**
     * TopicDiscoveryService::suggestAngles() is presently a stub that may
     * return an empty array — this flashes a friendly "coming soon"
     * message in that case rather than a bare empty result.
     */
    public function suggestAngles(int $id)
    {
        $topic = model(TopicModel::class)->find($id);

        if ($topic === null) {
            throw PageNotFoundException::forPageNotFound('No such topic.');
        }

        $angles = (new TopicDiscoveryService())->suggestAngles($topic);

        $this->audit('topic.suggest_angles', $topic->id, null, ['angle_count' => count($angles)]);

        if ($angles === []) {
            return redirect()->to(site_url('admin/topics/' . $id))
                ->with('info', 'Angle suggestions coming soon — this feature is still in development.');
        }

        return redirect()->to(site_url('admin/topics/' . $id))
            ->with('success', 'Suggested angles generated below.')
            ->with('suggestedAngles', $angles);
    }

    /**
     * @return \App\Entities\User[]
     */
    private function editorOptions(): array
    {
        return model(UserModel::class)
            ->whereIn('role', ['editor', 'admin'])
            ->where('is_active', 1)
            ->orderBy('name', 'ASC')
            ->findAll();
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
            'topic',
            $subjectId,
            $before,
            $after,
            $this->request->getIPAddress(),
            date('Y-m-d H:i:s')
        );
    }
}
