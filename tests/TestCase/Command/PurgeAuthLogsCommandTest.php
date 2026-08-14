<?php
declare(strict_types=1);

namespace BcAuthGuard\Test\TestCase\Command;

use BaserCore\TestSuite\BcTestCase;
use BcAuthGuard\Command\PurgeAuthLogsCommand;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\TestSuite\StubConsoleOutput;
use Cake\Core\Configure;

/**
 * PurgeAuthLogsCommandTest
 */
class PurgeAuthLogsCommandTest extends BcTestCase
{
    public function tearDown(): void
    {
        Configure::delete('BcAuthGuard');
        parent::tearDown();
    }

    /**
     * 自動削除設定が無効で --force 未指定の場合はスキップされること
     *
     * DBに触れる前に短絡するため、フィクスチャなしで検証できる。
     */
    public function testExecuteSkippedWhenDisabled(): void
    {
        Configure::write('BcAuthGuard.autoPurgeEnabled', false);

        $stdout = new StubConsoleOutput();
        $stderr = new StubConsoleOutput();
        $io = new ConsoleIo($stdout, $stderr);

        $command = new PurgeAuthLogsCommand();
        $args = new Arguments([], ['force' => false], []);

        $result = $command->execute($args, $io);

        $this->assertSame(Command::CODE_SUCCESS, $result);
        $this->assertStringContainsString('スキップ', implode("\n", $stderr->messages()));
    }
}
