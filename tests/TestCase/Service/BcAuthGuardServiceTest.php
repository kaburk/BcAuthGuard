<?php
declare(strict_types=1);

namespace BcAuthGuard\Test\TestCase\Service;

use BaserCore\TestSuite\BcTestCase;
use BcAuthGuard\Service\BcAuthGuardService;
use Cake\Core\Configure;

/**
 * BcAuthGuardServiceTest
 */
class BcAuthGuardServiceTest extends BcTestCase
{
    private BcAuthGuardService $service;

    public function setUp(): void
    {
        parent::setUp();
        $reflection = new \ReflectionClass(BcAuthGuardService::class);
        $this->service = $reflection->newInstanceWithoutConstructor();
    }

    public function tearDown(): void
    {
        Configure::delete('BcAuthGuard');
        parent::tearDown();
    }

    public function testIsBlockedIpWithExactAndCidr(): void
    {
        Configure::write('BcAuthGuard', [
            'enableIpBlock' => true,
            'blockedIps' => [
                '192.0.2.10',
                '198.51.100.0/24',
                '2001:db8::/32',
            ],
        ]);

        $this->assertTrue($this->service->isBlockedIp('192.0.2.10'));
        $this->assertTrue($this->service->isBlockedIp('198.51.100.20'));
        $this->assertTrue($this->service->isBlockedIp('2001:db8:abcd::1'));
        $this->assertFalse($this->service->isBlockedIp('203.0.113.10'));
    }

    public function testIsBlockedIpReturnsFalseWhenDisabled(): void
    {
        Configure::write('BcAuthGuard', [
            'enableIpBlock' => false,
            'blockedIps' => ['192.0.2.10'],
        ]);

        $this->assertFalse($this->service->isBlockedIp('192.0.2.10'));
    }

    public function testNormalizeUsername(): void
    {
        $this->assertSame('admin@example.com', $this->service->normalizeUsername('  Admin@Example.COM  '));
        $this->assertSame('', $this->service->normalizeUsername(null));
    }

    public function testMatchCidrWithInvalidDefinition(): void
    {
        $this->assertFalse($this->execPrivateMethod($this->service, 'matchCidr', ['192.0.2.10', '192.0.2.0/abc']));
        $this->assertFalse($this->execPrivateMethod($this->service, 'matchCidr', ['192.0.2.10', '192.0.2.0/99']));
        $this->assertFalse($this->execPrivateMethod($this->service, 'matchCidr', ['192.0.2.10', '2001:db8::/32']));
    }
}
