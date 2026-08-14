<?php
/**
 * 認証ガード通知メール（テキスト）
 *
 * @var \Cake\View\View $this
 * @var string $heading
 * @var array $rows label => value
 * @var string $footer
 */
echo $heading . "\n\n";
foreach ($rows as $label => $value) {
    echo $label . ': ' . $value . "\n";
}
if (!empty($footer)) {
    echo "\n" . $footer . "\n";
}
