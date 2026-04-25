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
        return $settings;
    }

    public function update(array $data): array
    {
        $limitWindowMinutes = max(1, (int) ($data['limitWindowMinutes'] ?? 10));
        $limitCount = max(1, (int) ($data['limitCount'] ?? 5));
        $lockMinutes = max(1, (int) ($data['lockMinutes'] ?? 10));
        $enableIpBlock = !empty($data['enableIpBlock']);

        $blockedIpsText = trim((string) ($data['blockedIpsText'] ?? ''));
        $blockedIps = [];
        if ($blockedIpsText !== '') {
            foreach (preg_split('/\R/u', $blockedIpsText) as $line) {
                $line = trim((string) $line);
                if ($line === '') {
                    continue;
                }
                $blockedIps[] = $line;
            }
            $blockedIps = array_values(array_unique($blockedIps));
        }

        $settings = [
            'limitWindowMinutes' => $limitWindowMinutes,
            'limitCount' => $limitCount,
            'lockMinutes' => $lockMinutes,
            'enableIpBlock' => $enableIpBlock,
            'blockedIps' => $blockedIps,
            'blockedIpsText' => implode("\n", $blockedIps),
        ];

        $this->writeCustomizeFile($settings);
        Configure::write('BcAuthGuard', [
            'limitWindowMinutes' => $settings['limitWindowMinutes'],
            'limitCount' => $settings['limitCount'],
            'lockMinutes' => $settings['lockMinutes'],
            'enableIpBlock' => $settings['enableIpBlock'],
            'blockedIps' => $settings['blockedIps'],
        ]);

        return $settings;
    }

    private function writeCustomizeFile(array $settings): void
    {
        $path = dirname(__DIR__, 2) . '/config/setting_customize.php';
        $payload = [
            'BcAuthGuard' => [
                'limitWindowMinutes' => $settings['limitWindowMinutes'],
                'limitCount' => $settings['limitCount'],
                'lockMinutes' => $settings['lockMinutes'],
                'enableIpBlock' => $settings['enableIpBlock'],
                'blockedIps' => $settings['blockedIps'],
            ]
        ];

        $content = "<?php\ndeclare(strict_types=1);\n\nreturn " . var_export($payload, true) . ";\n";
        file_put_contents($path, $content);
    }
}
