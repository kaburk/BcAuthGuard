<?php
declare(strict_types=1);

use BaserCore\Database\Migration\BcMigration;

class Initial extends BcMigration
{
    public function up(): void
    {
        $this->table('bc_auth_guard_lockouts')
            ->addColumn('prefix', 'string', [
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('username', 'string', [
                'limit' => 255,
                'null' => true,
            ])
            ->addColumn('ip_address', 'string', [
                'limit' => 45,
                'null' => true,
            ])
            ->addColumn('failed_count', 'integer', [
                'default' => 0,
            ])
            ->addColumn('window_started', 'datetime', [
                'null' => true,
            ])
            ->addColumn('last_failed_at', 'datetime', [
                'null' => true,
            ])
            ->addColumn('locked_until', 'datetime', [
                'null' => true,
            ])
            ->addColumn('released_reason', 'string', [
                'limit' => 50,
                'null' => true,
            ])
            ->addColumn('created', 'datetime', [
                'null' => true,
            ])
            ->addColumn('modified', 'datetime', [
                'null' => true,
            ])
            ->addIndex(['prefix', 'username', 'ip_address'], ['unique' => true])
            ->addIndex(['locked_until'])
            ->create();
    }

    public function down(): void
    {
        $this->table('bc_auth_guard_lockouts')->drop()->save();
    }
}
