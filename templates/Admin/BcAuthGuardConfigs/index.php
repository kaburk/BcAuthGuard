<?php
/**
 * @var \BaserCore\View\BcAdminAppView $this
 * @var array $settings
 */
$this->BcAdmin->setTitle(__d('baser_core', '認証ガード設定'));
?>

<?php echo $this->BcAdminForm->create(null, ['url' => ['action' => 'index']]) ?>

<table class="form-table bca-form-table" id="FormTable">
    <tr>
        <th class="col-head bca-form-table__label"><?php echo $this->BcAdminForm->label('limitWindowMinutes', __d('baser_core', '失敗回数の集計時間(分)')) ?></th>
        <td class="col-input bca-form-table__input">
            <?php echo $this->BcAdminForm->control('limitWindowMinutes', ['type' => 'number', 'min' => 1, 'value' => $settings['limitWindowMinutes'] ?? 10]) ?>
        </td>
    </tr>
    <tr>
        <th class="col-head bca-form-table__label"><?php echo $this->BcAdminForm->label('limitCount', __d('baser_core', 'ロック開始までの失敗回数')) ?></th>
        <td class="col-input bca-form-table__input">
            <?php echo $this->BcAdminForm->control('limitCount', ['type' => 'number', 'min' => 1, 'value' => $settings['limitCount'] ?? 5]) ?>
        </td>
    </tr>
    <tr>
        <th class="col-head bca-form-table__label"><?php echo $this->BcAdminForm->label('lockMinutes', __d('baser_core', 'ロック時間(分)')) ?></th>
        <td class="col-input bca-form-table__input">
            <?php echo $this->BcAdminForm->control('lockMinutes', ['type' => 'number', 'min' => 1, 'value' => $settings['lockMinutes'] ?? 10]) ?>
        </td>
    </tr>
    <tr>
        <th class="col-head bca-form-table__label"><?php echo $this->BcAdminForm->label('enableIpBlock', __d('baser_core', 'IP拒否を有効化')) ?></th>
        <td class="col-input bca-form-table__input">
            <?php echo $this->BcAdminForm->control('enableIpBlock', [
                'type' => 'checkbox',
                'label' => __d('baser_core', '有効'),
                'checked' => !empty($settings['enableIpBlock'])
            ]) ?>
        </td>
    </tr>
    <tr>
        <th class="col-head bca-form-table__label"><?php echo $this->BcAdminForm->label('blockedIpsText', __d('baser_core', '拒否IPリスト')) ?></th>
        <td class="col-input bca-form-table__input">
            <?php echo $this->BcAdminForm->control('blockedIpsText', [
                'type' => 'textarea',
                'rows' => 8,
                'value' => $settings['blockedIpsText'] ?? '',
                'placeholder' => "192.0.2.10\n198.51.100.0/24\n2001:db8::/32"
            ]) ?>
            <p class="bca-form__note"><?php echo __d('baser_core', '1行に1つずつIPアドレスまたはCIDRを入力します。') ?></p>
        </td>
    </tr>
</table>

<div class="bca-actions">
    <div class="bca-actions__main">
        <?php echo $this->BcAdminForm->button(__d('baser_core', '保存'), [
            'type' => 'submit',
            'class' => 'bca-btn bca-actions__item',
            'data-bca-btn-type' => 'save',
            'data-bca-btn-size' => 'lg',
            'data-bca-btn-width' => 'lg'
        ]) ?>
    </div>
</div>

<?php echo $this->BcAdminForm->end() ?>
