<?php
declare(strict_types=1);

namespace BcAuthGuard\Service;

use BcAuthCommon\Service\AuthLoginLogService;
use Cake\Core\Configure;
use Cake\I18n\FrozenTime;
use Cake\Http\ServerRequest;
use Cake\ORM\Table;
use Cake\ORM\Query\SelectQuery;
use Cake\ORM\TableRegistry;

/**
 * 認証ガードサービス
 */
class BcAuthGuardService
{
    private Table $lockouts;

    public function __construct()
    {
        $this->lockouts = TableRegistry::getTableLocator()->get('BcAuthGuard.BcAuthGuardLockouts');
    }

    public function isBlockedIp(string $ipAddress): bool
    {
        if (!Configure::read('BcAuthGuard.enableIpBlock', true)) {
            return false;
        }
        $blockedIps = array_filter(array_map('trim', (array) Configure::read('BcAuthGuard.blockedIps', [])));

        foreach ($blockedIps as $blockedIp) {
            if ($blockedIp === $ipAddress) {
                return true;
            }
            if (str_contains($blockedIp, '/') && $this->matchCidr($ipAddress, $blockedIp)) {
                return true;
            }
        }

        return false;
    }

    public function isLocked(string $prefix, string $username, string $ipAddress): bool
    {
        $lockout = $this->findLockout($prefix, $username, $ipAddress);
        if (!$lockout || !$lockout->locked_until) {
            return false;
        }
        return new FrozenTime($lockout->locked_until) > FrozenTime::now();
    }

    public function recordFailure(string $prefix, string $username, string $ipAddress, ?ServerRequest $request = null, array $context = []): bool
    {
        $now = FrozenTime::now();
        $windowMinutes = (int) Configure::read('BcAuthGuard.limitWindowMinutes', 10);
        $limitCount = (int) Configure::read('BcAuthGuard.limitCount', 5);
        $lockMinutes = (int) Configure::read('BcAuthGuard.lockMinutes', 10);

        $lockout = $this->findLockout($prefix, $username, $ipAddress);
        if (!$lockout) {
            $lockout = $this->lockouts->newEntity([
                'prefix' => $prefix,
                'username' => $username,
                'ip_address' => $ipAddress,
                'failed_count' => 0,
            ]);
        }

        $windowStart = $lockout->window_started ? new FrozenTime($lockout->window_started) : null;
        if (!$windowStart || $windowStart < $now->subMinutes($windowMinutes)) {
            $lockout->failed_count = 1;
            $lockout->window_started = $now;
        } else {
            $lockout->failed_count = (int) $lockout->failed_count + 1;
        }

        $lockout->last_failed_at = $now;
        $lockedNow = ((int) $lockout->failed_count >= $limitCount);
        if ($lockedNow) {
            $lockout->locked_until = $now->addMinutes($lockMinutes);
        }

        $this->lockouts->saveOrFail($lockout);

        $event = $lockedNow ? 'lockout_started' : 'login_failure';
        $this->createLog($event, $prefix, $username, null, $request, $context);

        return $lockedNow;
    }

    public function clearFailures(string $prefix, string $username, string $ipAddress, string $reason = 'login_success'): bool
    {
        $lockout = $this->findLockout($prefix, $username, $ipAddress);
        if (!$lockout) {
            return false;
        }
        $wasLocked = !empty($lockout->locked_until) && new FrozenTime($lockout->locked_until) > FrozenTime::now();
        $lockout->failed_count = 0;
        $lockout->window_started = null;
        $lockout->last_failed_at = null;
        $lockout->locked_until = null;
        $lockout->released_reason = $reason;
        $this->lockouts->saveOrFail($lockout);
        return $wasLocked;
    }

    public function recordLockoutDenied(string $prefix, string $username, string $ipAddress, ?ServerRequest $request = null, array $context = []): void
    {
        $this->createLog('lockout_denied', $prefix, $username, null, $request, $context);
    }

    public function recordBlockedIpDenied(string $prefix, string $username, string $ipAddress, ?ServerRequest $request = null, array $context = []): void
    {
        $this->createLog('blocked_ip_denied', $prefix, $username, null, $request, $context);
    }

    public function normalizeUsername(?string $username): string
    {
        return mb_strtolower(trim((string) $username));
    }

    private function findLockout(string $prefix, string $username, string $ipAddress)
    {
        return $this->lockouts->find()
            ->where([
                'prefix' => $prefix,
                'username' => $username,
                'ip_address' => $ipAddress,
            ])
            ->first();
    }

    public function getLockoutsQuery(array $conditions = []): SelectQuery
    {
        $query = $this->lockouts->find()
            ->order(['BcAuthGuardLockouts.modified' => 'DESC', 'BcAuthGuardLockouts.id' => 'DESC']);

        if (!empty($conditions['prefix'])) {
            $query->where(['BcAuthGuardLockouts.prefix' => (string) $conditions['prefix']]);
        }
        if (!empty($conditions['username'])) {
            $query->where(['BcAuthGuardLockouts.username LIKE' => '%' . (string) $conditions['username'] . '%']);
        }
        if (!empty($conditions['ip_address'])) {
            $query->where(['BcAuthGuardLockouts.ip_address LIKE' => '%' . (string) $conditions['ip_address'] . '%']);
        }
        if (!empty($conditions['status'])) {
            if ($conditions['status'] === 'locked') {
                $query->where(['BcAuthGuardLockouts.locked_until >' => FrozenTime::now()]);
            } elseif ($conditions['status'] === 'released') {
                $query->where([
                    'OR' => [
                        ['BcAuthGuardLockouts.locked_until IS' => null],
                        ['BcAuthGuardLockouts.locked_until <=' => FrozenTime::now()],
                    ]
                ]);
            }
        }

        return $query;
    }

    public function releaseLockout(int $id, string $reason = 'manual_release'): bool
    {
        $lockout = $this->lockouts->find()->where(['id' => $id])->first();
        if (!$lockout) {
            return false;
        }

        $lockout->failed_count = 0;
        $lockout->window_started = null;
        $lockout->last_failed_at = null;
        $lockout->locked_until = null;
        $lockout->released_reason = $reason;
        return (bool) $this->lockouts->save($lockout);
    }

    private function createLog(
        string $event,
        string $prefix,
        string $username,
        ?int $userId,
        ?ServerRequest $request,
        array $context
    ): void {
        AuthLoginLogService::writeWithContext(
            event: $event,
            userId: $userId,
            prefix: $prefix,
            authSource: (string) ($context['auth_source'] ?? 'password'),
            username: $username,
            request: $request,
            context: $context,
        );
    }

    private function matchCidr(string $ipAddress, string $cidr): bool
    {
        if (!str_contains($cidr, '/')) {
            return false;
        }

        [$network, $prefix] = explode('/', $cidr, 2);
        $network = trim($network);
        $prefix = trim($prefix);

        if ($network === '' || $prefix === '' || !ctype_digit($prefix)) {
            return false;
        }

        $ipBin = @inet_pton($ipAddress);
        $networkBin = @inet_pton($network);
        if ($ipBin === false || $networkBin === false || strlen($ipBin) !== strlen($networkBin)) {
            return false;
        }

        $bits = (int) $prefix;
        $maxBits = strlen($ipBin) * 8;
        if ($bits < 0 || $bits > $maxBits) {
            return false;
        }

        $fullBytes = intdiv($bits, 8);
        $remainingBits = $bits % 8;

        if ($fullBytes > 0 && substr($ipBin, 0, $fullBytes) !== substr($networkBin, 0, $fullBytes)) {
            return false;
        }

        if ($remainingBits === 0) {
            return true;
        }

        $mask = (0xFF << (8 - $remainingBits)) & 0xFF;
        $ipByte = ord($ipBin[$fullBytes]);
        $networkByte = ord($networkBin[$fullBytes]);

        return (($ipByte & $mask) === ($networkByte & $mask));
    }

}
