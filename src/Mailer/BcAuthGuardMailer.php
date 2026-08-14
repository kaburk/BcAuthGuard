<?php
declare(strict_types=1);

namespace BcAuthGuard\Mailer;

use BaserCore\Mailer\BcMailer;

/**
 * BcAuthGuardMailer
 *
 * 認証ガードの通知メールを送信する。
 * 差出人・トランスポート設定は BcMailer の初期化に従う。
 */
class BcAuthGuardMailer extends BcMailer
{
    /**
     * @var string
     */
    protected $plugin = 'BcAuthGuard';

    /**
     * 認証ガード通知メールを送信する
     *
     * テンプレートはプラグイン同梱の text テンプレートを明示指定し、
     * テーマに依存せず解決できるようにする。
     *
     * @param array $recipients 宛先メールアドレス
     * @param string $subject 件名
     * @param array $vars テンプレート変数（heading / rows / footer）
     */
    public function sendNotification(array $recipients, string $subject, array $vars): void
    {
        $this->setTo($recipients)
            ->setSubject($subject)
            ->setEmailFormat('text');
        $this->viewBuilder()
            ->disableAutoLayout()
            ->setTemplate('BcAuthGuard.notification')
            ->setVars($vars);
    }
}
