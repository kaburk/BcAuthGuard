<?php
/**
 * @var \BaserCore\View\BcAdminAppView $this
 * @var \Cake\Datasource\Paging\PaginatedResultSet $lockouts
 * @var array $statusList
 * @var array $prefixList
 */
use Cake\Core\Configure;

$this->BcAdmin->setTitle(__d('baser_core', 'ロック中一覧'));
$this->BcAdmin->setSearch('BcAuthGuard.bc_auth_guard_lockouts_index');
$releasedReasonLabels = (array) Configure::read('BcAuthGuard.releasedReasonLabels', []);
?>

<table class="list-table bca-table-listup" id="ListTable">
    <thead class="bca-table-listup__thead">
    <tr>
        <th class="bca-table-listup__thead-th"><?php echo $this->Paginator->sort('id', __d('baser_core', 'No')) ?></th>
        <th class="bca-table-listup__thead-th"><?php echo $this->Paginator->sort('prefix', __d('baser_core', 'プレフィックス')) ?></th>
        <th class="bca-table-listup__thead-th"><?php echo $this->Paginator->sort('username', __d('baser_core', 'ログインID')) ?></th>
        <th class="bca-table-listup__thead-th"><?php echo $this->Paginator->sort('ip_address', __d('baser_core', 'IPアドレス')) ?></th>
        <th class="bca-table-listup__thead-th"><?php echo $this->Paginator->sort('failed_count', __d('baser_core', '失敗回数')) ?></th>
        <th class="bca-table-listup__thead-th"><?php echo $this->Paginator->sort('locked_until', __d('baser_core', 'ロック期限')) ?></th>
        <th class="bca-table-listup__thead-th"><?php echo $this->Paginator->sort('released_reason', __d('baser_core', '解除理由')) ?></th>
        <th class="bca-table-listup__thead-th"><?php echo __d('baser_core', 'アクション') ?></th>
    </tr>
    </thead>
    <tbody class="bca-table-listup__tbody">
    <?php if ($lockouts->count()): ?>
        <?php foreach ($lockouts as $lockout): ?>
            <tr>
                <td class="bca-table-listup__tbody-td"><?php echo $lockout->id ?></td>
                <td class="bca-table-listup__tbody-td"><?php echo h((string) $lockout->prefix) ?></td>
                <td class="bca-table-listup__tbody-td"><?php echo h((string) $lockout->username) ?></td>
                <td class="bca-table-listup__tbody-td"><?php echo h((string) $lockout->ip_address) ?></td>
                <td class="bca-table-listup__tbody-td"><?php echo (int) $lockout->failed_count ?></td>
                <td class="bca-table-listup__tbody-td">
                    <?php echo $lockout->locked_until ? $this->BcTime->format($lockout->locked_until, 'yyyy-MM-dd HH:mm:ss') : '' ?>
                </td>
                <td class="bca-table-listup__tbody-td">
                    <?php
                    $releasedReason = (string) $lockout->released_reason;
                    $releasedReasonLabel = (string) ($releasedReasonLabels[$releasedReason] ?? $releasedReason);
                    echo h($releasedReasonLabel);
                    ?>
                </td>
                <td class="bca-table-listup__tbody-td bca-table-listup__tbody-td--actions">
                    <?php
                    if ($lockout->locked_until && $lockout->locked_until > \Cake\I18n\FrozenTime::now()) {
                        echo $this->BcAdminForm->postLink(__d('baser_core', '解除'), ['action' => 'release', $lockout->id], [
                            'confirm' => __d('baser_core', 'ロック情報 No.{0} を解除してもよろしいですか？', $lockout->id),
                            'title' => __d('baser_core', '解除'),
                            'class' => 'bca-btn',
                        ]);
                    }
                    ?>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr>
            <td colspan="8" class="bca-table-listup__tbody-td">
                <p class="no-data"><?php echo __d('baser_core', 'データが見つかりませんでした。') ?></p>
            </td>
        </tr>
    <?php endif; ?>
    </tbody>
</table>

<div class="bca-data-list__bottom">
    <div class="bca-data-list__sub">
        <?php $this->BcBaser->element('pagination') ?>
        <?php $this->BcBaser->element('list_num') ?>
    </div>
</div>

<?php echo $this->fetch('postLink') ?>
