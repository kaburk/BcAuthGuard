<?php
declare(strict_types=1);

namespace BcAuthGuard\Controller\Admin;

use BaserCore\Utility\BcSiteConfig;
use BaserCore\View\Helper\BcCsvHelper;
use BcAuthGuard\Service\BcAuthGuardService;
use Cake\Core\Configure;
use Cake\I18n\FrozenTime;
use Cake\View\View;

class BcAuthGuardLockoutsController extends BcAuthGuardAdminAppController
{
    /**
     * CSV出力時の最大件数
     */
    private const CSV_MAX_ROWS = 50000;

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

        $this->set([
            'lockouts' => $this->paginate($this->createIndexQuery()),
            'statusList' => [
                'locked' => __d('baser_core', 'ロック中'),
                'released' => __d('baser_core', '解除済み'),
            ],
            'prefixList' => [
                'Admin' => 'Admin',
            ],
        ]);
    }

    /**
     * 検索条件を引き継いでロック中一覧をCSV出力する
     */
    public function download()
    {
        $releasedReasonLabels = (array) Configure::read('BcAuthGuard.releasedReasonLabels', []);
        $now = FrozenTime::now();
        $rows = $this->createIndexQuery()->limit(self::CSV_MAX_ROWS)->all();

        $datas = [];
        foreach ($rows as $lockout) {
            $lockedUntil = $lockout->locked_until ? new FrozenTime($lockout->locked_until) : null;
            $status = ($lockedUntil && $lockedUntil > $now)
                ? __d('baser_core', 'ロック中')
                : __d('baser_core', '解除済み');
            $releasedReason = (string) $lockout->released_reason;
            $datas[] = [
                'BcAuthGuardLockout' => [
                    __d('baser_core', 'No') => (string) $lockout->id,
                    __d('baser_core', '状態') => $status,
                    __d('baser_core', 'プレフィックス') => (string) $lockout->prefix,
                    __d('baser_core', 'ログインID') => (string) $lockout->username,
                    __d('baser_core', 'IPアドレス') => (string) $lockout->ip_address,
                    __d('baser_core', '失敗回数') => (string) (int) $lockout->failed_count,
                    __d('baser_core', 'ロック期限') => $lockedUntil ? $lockedUntil->i18nFormat('yyyy-MM-dd HH:mm:ss') : '',
                    __d('baser_core', '解除理由') => (string) ($releasedReasonLabels[$releasedReason] ?? $releasedReason),
                    __d('baser_core', '更新日時') => $lockout->modified ? $lockout->modified->i18nFormat('yyyy-MM-dd HH:mm:ss') : '',
                ],
            ];
        }

        $this->autoRender = false;
        $bcCsv = new BcCsvHelper(new View());
        $bcCsv->addModelDatas('BcAuthGuardLockout', $datas);
        $bcCsv->download('auth_guard_lockouts_' . date('YmdHis'));
    }

    /**
     * 一覧・CSV共通の検索クエリを組み立てる
     */
    private function createIndexQuery()
    {
        return $this->service->getLockoutsQuery([
            'prefix' => (string) $this->getRequest()->getQuery('prefix'),
            'username' => trim((string) $this->getRequest()->getQuery('username')),
            'ip_address' => trim((string) $this->getRequest()->getQuery('ip_address')),
            'status' => (string) $this->getRequest()->getQuery('status'),
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
