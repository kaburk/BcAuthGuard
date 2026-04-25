<?php
declare(strict_types=1);

namespace BcAuthGuard;

use BaserCore\BcPlugin;
use BcAuthGuard\Event\BcAuthGuardControllerEventListener;
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
}
