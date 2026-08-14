<?php
declare(strict_types=1);

namespace BcAuthGuard\Service;

use Cake\Core\Configure;

class BcAuthGuardSettingsService
{
    public function getSettings(): array
    {
        $settings = (array) Configure::read('BcAuthGuard', []);
        $blockedIps = (array) ($settings['blockedIps'] ?? []);
        $settings['blockedIpsText'] = implode("\n", $blockedIps);
        $notifyEmails = (array) ($settings['notifyEmails'] ?? []);
        $settings['notifyEmailsText'] = implode("\n", $notifyEmails);
        return $settings;
    }

    public function update(array $data): array
    {
        $limitWindowMinutes = max(1, (int) ($data['limitWindowMinutes'] ?? 10));
        $limitCount = max(1, (int) ($data['limitCount'] ?? 5));
        $lockMinutes = max(1, (int) ($data['lockMinutes'] ?? 10));
        $enableIpBlock = !empty($data['enableIpBlock']);

        $blockedIps = $this->parseLines((string) ($data['blockedIpsText'] ?? ''));

        $autoPurgeEnabled = !empty($data['autoPurgeEnabled']);
        $logRetentionDays = max(0, (int) ($data['logRetentionDays'] ?? 90));
        $lockoutRetentionDays = max(0, (int) ($data['lockoutRetentionDays'] ?? 30));

        $notifyEnabled = !empty($data['notifyEnabled']);
        $notifyLockoutStarted = !empty($data['notifyLockoutStarted']);
        $notifyBlockedIp = !empty($data['notifyBlockedIp']);
        $notifyEmails = $this->parseEmails((string) ($data['notifyEmailsText'] ?? ''));

        $settings = [
            'limitWindowMinutes' => $limitWindowMinutes,
            'limitCount' => $limitCount,
            'lockMinutes' => $lockMinutes,
            'enableIpBlock' => $enableIpBlock,
            'blockedIps' => $blockedIps,
            'autoPurgeEnabled' => $autoPurgeEnabled,
            'logRetentionDays' => $logRetentionDays,
            'lockoutRetentionDays' => $lockoutRetentionDays,
            'notifyEnabled' => $notifyEnabled,
            'notifyLockoutStarted' => $notifyLockoutStarted,
            'notifyBlockedIp' => $notifyBlockedIp,
            'notifyEmails' => $notifyEmails,
        ];

        $this->writeCustomizeFile($settings);
        Configure::write('BcAuthGuard', array_merge((array) Configure::read('BcAuthGuard', []), $settings));

        $settings['blockedIpsText'] = implode("\n", $blockedIps);
        $settings['notifyEmailsText'] = implode("\n", $notifyEmails);

        return $settings;
    }

    /**
     * 改行区切りのテキストを配列に変換する（空行除去・重複排除）
     */
    private function parseLines(string $text): array
    {
        $text = trim($text);
        if ($text === '') {
            return [];
        }
        $lines = [];
        foreach (preg_split('/\R/u', $text) as $line) {
            $line = trim((string) $line);
            if ($line === '') {
                continue;
            }
            $lines[] = $line;
        }
        return array_values(array_unique($lines));
    }

    /**
     * 改行区切りのメールアドレスを検証しつつ配列に変換する
     */
    private function parseEmails(string $text): array
    {
        $emails = [];
        foreach ($this->parseLines($text) as $line) {
            if (filter_var($line, FILTER_VALIDATE_EMAIL)) {
                $emails[] = $line;
            }
        }
        return array_values(array_unique($emails));
    }

    private function writeCustomizeFile(array $settings): void
    {
        $path = dirname(__DIR__, 2) . '/config/setting_customize.php';
        $payload = ['BcAuthGuard' => $settings];

        $content = "<?php\ndeclare(strict_types=1);\n\nreturn " . var_export($payload, true) . ";\n";
        file_put_contents($path, $content);
    }
}
