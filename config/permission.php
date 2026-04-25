<?php
/**
 * baserCMS :  Based Website Development Project <https://basercms.net>
 * Copyright (c) NPO baser foundation <https://baserfoundation.org/>
 *
 * @copyright     Copyright (c) NPO baser foundation
 * @link          https://basercms.net baserCMS Project
 * @since         5.0.0
 * @license       https://basercms.net/license/index.html MIT License
 */

return [
    'permission' => [
        'BcAuthGuardConfigsAdmin' => [
            'title' => __d('baser_core', '認証ガード設定'),
            'plugin' => 'BcAuthGuard',
            'type' => 'Admin',
            'items' => [
                'Index' => [
                    'title' => __d('baser_core', '設定'),
                    'url' => '/baser/admin/bc-auth-guard/bc_auth_guard_configs/index',
                    'method' => '*',
                    'auth' => false,
                ],
            ]
        ],
        'BcAuthGuardLockoutsAdmin' => [
            'title' => __d('baser_core', 'ロック中一覧'),
            'plugin' => 'BcAuthGuard',
            'type' => 'Admin',
            'items' => [
                'Index' => [
                    'title' => __d('baser_core', '一覧'),
                    'url' => '/baser/admin/bc-auth-guard/bc_auth_guard_lockouts/index',
                    'method' => '*',
                    'auth' => false,
                ],
                'Release' => [
                    'title' => __d('baser_core', '手動解除'),
                    'url' => '/baser/admin/bc-auth-guard/bc_auth_guard_lockouts/release/*',
                    'method' => 'POST',
                    'auth' => false,
                ],
            ]
        ],
    ]
];
