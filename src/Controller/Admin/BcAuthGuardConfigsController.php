<?php
declare(strict_types=1);

namespace BcAuthGuard\Controller\Admin;

use BcAuthGuard\Service\BcAuthGuardSettingsService;

class BcAuthGuardConfigsController extends BcAuthGuardAdminAppController
{
    public function index()
    {
        $service = new BcAuthGuardSettingsService();
        $settings = $service->getSettings();

        if ($this->getRequest()->is(['post', 'put'])) {
            try {
                $settings = $service->update((array) $this->getRequest()->getData());
                $this->BcMessage->setSuccess(__d('baser_core', '認証ガード設定を保存しました。'));
                return $this->redirect(['action' => 'index']);
            } catch (\Throwable $e) {
                $settings = array_merge($settings, (array) $this->getRequest()->getData());
                $this->BcMessage->setError(__d('baser_core', '設定の保存中にエラーが発生しました。') . $e->getMessage());
            }
        }

        $this->set('settings', $settings);
    }
}
