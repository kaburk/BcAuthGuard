<?php
declare(strict_types=1);

namespace BcAuthGuard\Service;

use BaserCore\Utility\BcSiteConfig;
use BcAuthGuard\Mailer\BcAuthGuardMailer;
use Cake\Core\Configure;
use Cake\I18n\DateTime;
use Cake\Log\Log;
use Cake\Mailer\MailerAwareTrait;

/**
 * BcAuthGuardNotificationService
 *
 * ロック開始・IP拒否発生時の通知メールを送信する。
 * 認証フローを止めないよう、送信失敗は握り潰してログに記録する。
 */
class BcAuthGuardNotificationService
{
    use MailerAwareTrait;

    /**
     * ロック開始を通知する
     */
    public function notifyLockoutStarted(string $username, string $ipAddress): void
    {
        if (!$this->isEnabled('notifyLockoutStarted')) {
            return;
        }
        $this->send(
            __d('baser_core', '[認証ガード] ログインロックが開始されました'),
            __d('baser_core', 'ログイン失敗が規定回数に達したため、ログインを一定時間制限しました。'),
            $username,
            $ipAddress,
        );
    }

    /**
     * IP拒否発生を通知する
     */
    public function notifyBlockedIp(string $username, string $ipAddress): void
    {
        if (!$this->isEnabled('notifyBlockedIp')) {
            return;
        }
        $this->send(
            __d('baser_core', '[認証ガード] 拒否IPからのログイン試行を検知しました'),
            __d('baser_core', '拒否リストに登録されたIPアドレスからログインが試行されました。'),
            $username,
            $ipAddress,
        );
    }

    /**
     * 指定イベントの通知が有効かどうか
     */
    private function isEnabled(string $eventKey): bool
    {
        if (!(bool) Configure::read('BcAuthGuard.notifyEnabled', false)) {
            return false;
        }
        if (!(bool) Configure::read('BcAuthGuard.' . $eventKey, false)) {
            return false;
        }
        return !empty($this->recipients());
    }

    /**
     * 通知先メールアドレスを取得する
     */
    private function recipients(): array
    {
        $emails = (array) Configure::read('BcAuthGuard.notifyEmails', []);
        $emails = array_filter(array_map('trim', $emails), static fn($email) => filter_var($email, FILTER_VALIDATE_EMAIL));
        return array_values(array_unique($emails));
    }

    /**
     * 通知メールを送信する
     */
    private function send(string $subject, string $heading, string $username, string $ipAddress): void
    {
        try {
            $vars = [
                'heading' => $heading,
                'rows' => [
                    __d('baser_core', 'サイト名') => (string) BcSiteConfig::get('name'),
                    __d('baser_core', 'ログインID') => $username !== '' ? $username : '-',
                    __d('baser_core', 'IPアドレス') => $ipAddress !== '' ? $ipAddress : '-',
                    __d('baser_core', '発生日時') => DateTime::now()->i18nFormat('yyyy-MM-dd HH:mm:ss'),
                ],
                'footer' => __d('baser_core', '本メールは認証ガードの自動通知です。'),
            ];
            $this->getMailer(BcAuthGuardMailer::class)
                ->send('sendNotification', [$this->recipients(), $subject, $vars]);
        } catch (\Throwable $e) {
            Log::error('[BcAuthGuard] 通知メールの送信に失敗しました: ' . $e->getMessage());
        }
    }
}
