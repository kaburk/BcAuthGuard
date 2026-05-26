<?php
declare(strict_types=1);
/**
 * baserCMS :  Based Website Development Project <https://basercms.net>
 * Copyright (c) NPO baser foundation <https://baserfoundation.org/>
 *
 * @copyright     Copyright (c) NPO baser foundation
 * @link          https://basercms.net baserCMS Project
 * @since         5.0.0
 * @license       https://basercms.net/license/index.html MIT License
 */

use Cake\Utility\Hash;

$config = [
    'BcApp' => [
        'adminNavigation' => [
            'Plugins' => [
                'menus' => [
                    'BcAuthGuardConfigs' => [
                        'title' => __d('baser_core', '認証ガード設定'),
                        'url' => [
                            'prefix' => 'Admin',
                            'plugin' => 'BcAuthGuard',
                            'controller' => 'BcAuthGuardConfigs',
                            'action' => 'index'
                        ]
                    ],
                    'BcAuthGuardLockouts' => [
                        'title' => __d('baser_core', 'ロック中一覧'),
                        'url' => [
                            'prefix' => 'Admin',
                            'plugin' => 'BcAuthGuard',
                            'controller' => 'BcAuthGuardLockouts',
                            'action' => 'index'
                        ]
                    ],
                ]
            ]
        ]
    ],
    'BcAuthGuard' => [
        // 何分以内の失敗回数をカウントするか
        'limitWindowMinutes' => 10,
        // 何回失敗したらロックするか
        'limitCount' => 5,
        // 何分ロックするか
        'lockMinutes' => 10,
        // IP拒否機能を有効にするか
        'enableIpBlock' => true,
        // 単体IPまたはCIDRで拒否するIPリスト
        'blockedIps' => [],
        // 解除理由コードの表示ラベル
        'releasedReasonLabels' => [
            'manual_release' => __d('baser_core', '手動解除'),
            'login_success' => __d('baser_core', 'ログイン成功'),
        ],
    ],
];

$customizeFile = __DIR__ . DS . 'setting_customize.php';
if (file_exists($customizeFile)) {
    $config = Hash::merge($config, include $customizeFile);
}

return $config;
