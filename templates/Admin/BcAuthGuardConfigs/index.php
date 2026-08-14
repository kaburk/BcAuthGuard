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
    <tr>
        <th class="col-head bca-form-table__label"><?php echo __d('baser_core', '保持期間ポリシー') ?></th>
        <td class="col-input bca-form-table__input">
            <?php echo $this->BcAdminForm->control('autoPurgeEnabled', [
                'type' => 'checkbox',
                'label' => __d('baser_core', '保持期間を過ぎたログ・ロック情報を自動削除する（コマンド実行時）'),
                'checked' => !empty($settings['autoPurgeEnabled'])
            ]) ?>
        </td>
    </tr>
    <tr>
        <th class="col-head bca-form-table__label"><?php echo $this->BcAdminForm->label('logRetentionDays', __d('baser_core', '認証ログの保持日数')) ?></th>
        <td class="col-input bca-form-table__input">
            <?php echo $this->BcAdminForm->control('logRetentionDays', ['type' => 'number', 'min' => 0, 'value' => $settings['logRetentionDays'] ?? 90]) ?>
            <p class="bca-form__note"><?php echo __d('baser_core', '0を指定すると認証ログは削除しません。') ?></p>
        </td>
    </tr>
    <tr>
        <th class="col-head bca-form-table__label"><?php echo $this->BcAdminForm->label('lockoutRetentionDays', __d('baser_core', '解除済みロック情報の保持日数')) ?></th>
        <td class="col-input bca-form-table__input">
            <?php echo $this->BcAdminForm->control('lockoutRetentionDays', ['type' => 'number', 'min' => 0, 'value' => $settings['lockoutRetentionDays'] ?? 30]) ?>
            <p class="bca-form__note"><?php echo __d('baser_core', '0を指定すると解除済みロック情報は削除しません。ロック中のレコードは削除されません。') ?></p>
        </td>
    </tr>
    <tr>
        <th class="col-head bca-form-table__label"><?php echo $this->BcAdminForm->label('notifyEnabled', __d('baser_core', 'メール通知を有効化')) ?></th>
        <td class="col-input bca-form-table__input">
            <?php echo $this->BcAdminForm->control('notifyEnabled', [
                'type' => 'checkbox',
                'label' => __d('baser_core', '有効'),
                'checked' => !empty($settings['notifyEnabled'])
            ]) ?>
        </td>
    </tr>
    <tr>
        <th class="col-head bca-form-table__label"><?php echo __d('baser_core', '通知対象イベント') ?></th>
        <td class="col-input bca-form-table__input">
            <?php echo $this->BcAdminForm->control('notifyLockoutStarted', [
                'type' => 'checkbox',
                'label' => __d('baser_core', 'ロック開始時に通知する'),
                'checked' => !empty($settings['notifyLockoutStarted'])
            ]) ?>
            <br>
            <?php echo $this->BcAdminForm->control('notifyBlockedIp', [
                'type' => 'checkbox',
                'label' => __d('baser_core', 'IP拒否発生時に通知する'),
                'checked' => !empty($settings['notifyBlockedIp'])
            ]) ?>
        </td>
    </tr>
    <tr>
        <th class="col-head bca-form-table__label"><?php echo $this->BcAdminForm->label('notifyEmailsText', __d('baser_core', '通知先メールアドレス')) ?></th>
        <td class="col-input bca-form-table__input">
            <?php echo $this->BcAdminForm->control('notifyEmailsText', [
                'type' => 'textarea',
                'rows' => 4,
                'value' => $settings['notifyEmailsText'] ?? '',
                'placeholder' => "admin@example.com\nsecurity@example.com"
            ]) ?>
            <p class="bca-form__note"><?php echo __d('baser_core', '1行に1つずつメールアドレスを入力します。不正な形式の行は保存時に除外されます。') ?></p>
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

<div class="bca-panel-box mt-20">
    <h2 class="bca-panel-box__hns"><?php echo __d('baser_core', '保持期間ポリシーの手動実行') ?></h2>
    <p class="bca-form__note">
        <?php echo __d('baser_core', '上記の保持日数に基づき、期限切れの認証ログと解除済みロック情報を今すぐ削除します。') ?>
    </p>
    <?php echo $this->BcAdminForm->postLink(__d('baser_core', '今すぐ削除を実行'), ['action' => 'purge'], [
        'confirm' => __d('baser_core', '保持期間を過ぎた認証ログと解除済みロック情報を削除します。よろしいですか？'),
        'class' => 'bca-btn',
        'data-bca-btn-type' => 'delete'
    ]) ?>
</div>

<?php echo $this->fetch('postLink') ?>
