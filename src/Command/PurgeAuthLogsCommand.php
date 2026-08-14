<?php
declare(strict_types=1);

namespace BcAuthGuard\Command;

use BcAuthGuard\Service\BcAuthGuardService;
use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Configure;

/**
 * PurgeAuthLogsCommand
 *
 * 保持期間ポリシーに基づき、期限切れの認証ログと解除済みロック情報を削除する。
 * cron からの定期実行を想定。
 *
 * bin/cake bc_auth_guard purge
 * bin/cake bc_auth_guard purge --force   # autoPurgeEnabled が無効でも実行する
 */
class PurgeAuthLogsCommand extends Command
{
    protected function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser
            ->setDescription(__d('baser_core', '保持期間を過ぎた認証ログと解除済みロック情報を削除します。'))
            ->addOption('force', [
                'boolean' => true,
                'short' => 'f',
                'help' => __d('baser_core', '自動削除設定が無効でも強制的に実行します。'),
            ]);
        return $parser;
    }

    public function execute(Arguments $args, ConsoleIo $io): int
    {
        $enabled = (bool) Configure::read('BcAuthGuard.autoPurgeEnabled', false);
        if (!$enabled && !$args->getOption('force')) {
            $io->warning(__d('baser_core', '自動削除設定が無効のため処理をスキップしました。強制実行するには --force を指定してください。'));
            return static::CODE_SUCCESS;
        }

        $result = (new BcAuthGuardService())->purgeByRetentionPolicy();
        $io->success(__d('baser_core', '認証ログを {0} 件、解除済みロック情報を {1} 件削除しました。', $result['logs'], $result['lockouts']));

        return static::CODE_SUCCESS;
    }
}
