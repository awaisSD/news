<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Entities\Media;
use App\Models\AuditLogModel;
use App\Models\MediaModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use Config\Media as MediaConfig;

class MediaController extends BaseController
{
    public function index()
    {
        $model = model(MediaModel::class)->orderBy('created_at', 'DESC');
        $media = $model->paginate(24);

        return view('admin/media/index', [
            'title' => 'Media library',
            'media' => $media,
            'pager' => $model->pager,
        ]);
    }

    /**
     * The upload form is rendered inline on index.php per the task's "your
     * call, keep it simple" — new() just redirects there rather than
     * duplicating a separate template.
     */
    public function new()
    {
        return redirect()->to('/admin/media');
    }

    public function create()
    {
        $file = $this->request->getFile('image');

        if ($file === null || ! $file->isValid()) {
            return redirect()->to('/admin/media')->with('error', 'Please choose a valid image file to upload.');
        }

        $config = config(MediaConfig::class);

        if (! is_dir($config->uploadPath)) {
            mkdir($config->uploadPath, 0755, true);
        }

        $newName = $file->getRandomName();
        $file->move($config->uploadPath, $newName);

        $fullPath   = rtrim($config->uploadPath, '/') . '/' . $newName;
        $dimensions = @getimagesize($fullPath);

        $data = [
            'uuid'            => generate_uuid4(),
            'disk'            => $config->disk,
            'path'            => $newName,
            'cdn_url'         => null,
            'width'           => $dimensions[0] ?? null,
            'height'          => $dimensions[1] ?? null,
            'mime_type'       => $file->getClientMimeType(),
            'alt_text'        => $this->request->getPost('alt_text') ?: null,
            'alt_text_source' => $this->request->getPost('alt_text') ? 'manual' : null,
            'caption'         => $this->request->getPost('caption') ?: null,
            'credit'          => $this->request->getPost('credit') ?: null,
            'source'          => 'upload',
            'generated_by'    => 'human',
            'uploaded_by'     => $this->currentUser()->id,
        ];

        $id = model(MediaModel::class)->insert($data, true);

        model(AuditLogModel::class)->record(
            $this->currentUser()->id, 'created', 'media', (int) $id, null,
            ['path' => $newName], $this->request->getIPAddress(), date('Y-m-d H:i:s')
        );

        return redirect()->to('/admin/media')->with('success', 'Image uploaded.');
    }

    public function edit($id)
    {
        $item = model(MediaModel::class)->find((int) $id);

        if ($item === null) {
            throw new PageNotFoundException("Media #{$id} not found.");
        }

        return view('admin/media/edit', ['title' => 'Edit media #' . $item->id, 'media' => $item]);
    }

    public function update($id)
    {
        $item = model(MediaModel::class)->find((int) $id);

        if ($item === null) {
            throw new PageNotFoundException("Media #{$id} not found.");
        }

        $altText = trim((string) $this->request->getPost('alt_text'));

        if ($altText === '') {
            return redirect()->back()->withInput()->with('error', 'Alt text is required for accessibility and SEO.');
        }

        $before = ['alt_text' => $item->alt_text, 'caption' => $item->caption, 'credit' => $item->credit];
        $data   = [
            'alt_text'        => $altText,
            'alt_text_source' => 'manual',
            'caption'         => $this->request->getPost('caption') ?: null,
            'credit'          => $this->request->getPost('credit') ?: null,
        ];

        model(MediaModel::class)->update((int) $id, $data);

        model(AuditLogModel::class)->record(
            $this->currentUser()->id, 'updated', 'media', (int) $id, $before, $data,
            $this->request->getIPAddress(), date('Y-m-d H:i:s')
        );

        return redirect()->to('/admin/media')->with('success', 'Media updated.');
    }

    public function show($id)
    {
        return redirect()->to('/admin/media/' . $id . '/edit');
    }

    public function delete($id)
    {
        $item = model(MediaModel::class)->find((int) $id);

        if ($item === null) {
            throw new PageNotFoundException("Media #{$id} not found.");
        }

        // Note: this removes the DB row only, not the underlying file on
        // disk — left as a follow-up since a shared/CDN-fronted file may
        // still be referenced elsewhere (e.g. cached responses); safe
        // deletion of the on-disk asset is out of scope for this pass.
        model(MediaModel::class)->delete((int) $id);

        model(AuditLogModel::class)->record(
            $this->currentUser()->id, 'deleted', 'media', (int) $id,
            ['path' => $item->path], null, $this->request->getIPAddress(), date('Y-m-d H:i:s')
        );

        return redirect()->to('/admin/media')->with('success', 'Media deleted.');
    }

    // generate_uuid4() comes from app/Helpers/uuid_helper.php (preloaded by BaseController).
}
