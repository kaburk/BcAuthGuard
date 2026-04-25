<?php
declare(strict_types=1);

namespace BcAuthGuard\Model\Table;

use Cake\ORM\Table;

/**
 * BcAuthGuardLockouts table
 */
class BcAuthGuardLockoutsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);
        $this->setTable('bc_auth_guard_lockouts');
        $this->addBehavior('Timestamp');
    }
}
