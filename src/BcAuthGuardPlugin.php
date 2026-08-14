<?php
declare(strict_types=1);

namespace BcAuthGuard;

use BaserCore\BcPlugin;
use BcAuthGuard\Command\PurgeAuthLogsCommand;
use BcAuthGuard\Event\BcAuthGuardControllerEventListener;
use Cake\Console\CommandCollection;
use Cake\Core\PluginApplicationInterface;
use Cake\Event\EventManager;

/**
 * plugin for BcAuthGuard
 */
class BcAuthGuardPlugin extends BcPlugin
{
    public function bootstrap(PluginApplicationInterface $app): void
    {
        parent::bootstrap($app);
        EventManager::instance()->on(new BcAuthGuardControllerEventListener());
    }

    public function console(CommandCollection $commands): CommandCollection
    {
        $commands = parent::console($commands);
        $commands->add('bc_auth_guard purge', PurgeAuthLogsCommand::class);
        return $commands;
    }
}
