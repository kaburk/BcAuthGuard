<?php
declare(strict_types=1);

namespace BcAuthGuard\Controller\Admin;

use BaserCore\Utility\BcSiteConfig;
use BcAuthGuard\Service\BcAuthGuardService;

class BcAuthGuardLockoutsController extends BcAuthGuardAdminAppController
{
    private BcAuthGuardService $service;

    public function initialize(): void
    {
        parent::initialize();
        $this->service = new BcAuthGuardService();
    }

    public function index()
    {
        $this->setViewConditions('BcAuthGuardLockouts', [
            'default' => [
                'query' => [
                    'limit' => BcSiteConfig::get('admin_list_num'),
                    'sort' => 'modified',
                    'direction' => 'desc',
                ]
            ]
        ]);

        $query = $this->service->getLockoutsQuery([
            'prefix' => (string) $this->getRequest()->getQuery('prefix'),
            'username' => trim((string) $this->getRequest()->getQuery('username')),
            'ip_address' => trim((string) $this->getRequest()->getQuery('ip_address')),
            'status' => (string) $this->getRequest()->getQuery('status'),
        ]);

        $this->set([
            'lockouts' => $this->paginate($query),
            'statusList' => [
                'locked' => __d('baser_core', 'ロック中'),
                'released' => __d('baser_core', '解除済み'),
            ],
            'prefixList' => [
                'Admin' => 'Admin',
            ],
        ]);
    }

    public function release(int $id)
    {
        $this->request->allowMethod(['post', 'delete']);

        try {
            if ($this->service->releaseLockout($id, 'manual_release')) {
                $this->BcMessage->setSuccess(__d('baser_core', 'ロック情報 No.{0} を解除しました。', $id));
            } else {
                $this->BcMessage->setError(__d('baser_core', 'ロック情報の解除に失敗しました。'));
            }
        } catch (\Throwable $e) {
            $this->BcMessage->setError(__d('baser_core', 'ロック情報の解除中にエラーが発生しました。') . $e->getMessage());
        }

        return $this->redirect(['action' => 'index']);
    }
}
