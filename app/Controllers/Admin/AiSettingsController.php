<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AiSettingModel;
use App\Models\AuditLogModel;
use Config\AIPipeline;

class AiSettingsController extends BaseController
{
    public function index()
    {
        $model = model(AiSettingModel::class);
        $cap   = $model->getValue('daily_generation_cap');
        $cap   = $cap !== null && $cap !== '' ? (int) $cap : config(AIPipeline::class)->dailyGenerationCap;

        return view('admin/settings/ai', [
            'title'    => 'AI settings',
            'cap'      => $cap,
            'pipeline' => config(AIPipeline::class),
        ]);
    }

    public function update()
    {
        $rules = ['daily_generation_cap' => 'required|is_natural_no_zero'];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('error', implode(' ', $this->validator->getErrors()));
        }

        $cap = (int) $this->request->getPost('daily_generation_cap');

        $settingModel = model(AiSettingModel::class);
        $settingModel->setValue('daily_generation_cap', (string) $cap);

        // AuditLogModel::record() requires a natural-number subject_id, so
        // look the row back up to get its real id rather than passing 0.
        $row = $settingModel->where('setting_key', 'daily_generation_cap')->first();

        model(AuditLogModel::class)->record(
            $this->currentUser()->id,
            'updated',
            'ai_setting',
            (int) $row['id'],
            null,
            ['daily_generation_cap' => $cap],
            $this->request->getIPAddress(),
            date('Y-m-d H:i:s')
        );

        return redirect()->to('/admin/settings/ai')->with('success', 'AI settings updated.');
    }
}
