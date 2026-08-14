<?php
declare(strict_types=1);

namespace BcAuthGuard\Controller\Admin;

use BcAuthGuard\Service\BcAuthGuardService;
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

    /**
     * 保持期間ポリシーに基づき、期限切れの認証ログと解除済みロック情報を手動で削除する
     */
    public function purge()
    {
        $this->request->allowMethod(['post']);

        try {
            $result = (new BcAuthGuardService())->purgeByRetentionPolicy();
            $this->BcMessage->setSuccess(__d('baser_core', '削除を実行しました。認証ログ: {0} 件、ロック情報: {1} 件。', $result['logs'], $result['lockouts']));
        } catch (\Throwable $e) {
            $this->BcMessage->setError(__d('baser_core', '削除の実行中にエラーが発生しました。') . $e->getMessage());
        }

        return $this->redirect(['action' => 'index']);
    }
}
