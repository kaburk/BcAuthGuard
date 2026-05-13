# BcAuthGuard plugin for baserCMS

BcAuthGuard は、baserCMS 5 の管理画面ログインに対して試行制限と監査ログ記録を提供するプラグインです。

主に次の責務を担います。

- ログイン失敗回数に応じた一時ロック
- ロック中拒否・IP拒否を含む監査ログ記録
- ロック情報の管理画面運用（検索・手動解除）

## 対象と目的

- 対象: baserCMS 5 系の管理画面ログイン（Admin）
- 目的: ブルートフォース対策の強化と、認証失敗イベントの監査性向上

## できること

- 10分間に5回失敗でロック（初期設定値）
- ログイン成功時に失敗カウンタを解放
- 単体IP / CIDR による拒否設定
- 次の認証イベントを監査ログに記録
	- login_failure
	- lockout_started
	- lockout_denied
	- blocked_ip_denied
- Guard 固有イベントを最近の動き（Dblog）へ記録
	- ロック開始
	- ロック中拒否
	- IP拒否
	- ログイン成功による制限解除

## 管理画面

- 認証ガード設定
	- Plugins > 認証ガード設定
	- 保存内容は [config/setting_customize.php](config/setting_customize.php) に反映
- ロック中一覧
	- Plugins > ロック中一覧
	- 検索条件: 状態 / プレフィックス / ログインID / IPアドレス
	- ロック中レコードの手動解除

## 設定

設定定義は [config/setting.php](config/setting.php) にあります。初期値は次の通りです。

- limitWindowMinutes: 10
- limitCount: 5
- lockMinutes: 10
- enableIpBlock: true
- blockedIps: []

[config/setting_customize.php](config/setting_customize.php) を配置すると設定を上書きできます。

blockedIps の設定例:

- 192.0.2.10
- 198.51.100.0/24
- 2001:db8::/32

## 監査ログについて

本プラグインは [BcAuthCommon](../BcAuthCommon/README.md) の [src/Service/AuthLoginLogService.php](../BcAuthCommon/src/Service/AuthLoginLogService.php) を利用して、認証イベントを bc_auth_login_logs テーブルに保存します。

通常のログイン成功 / ログアウトの監査ログは BcAuthCommon 側で処理します。

## よく参照する実装ファイル

- [src/Event/BcAuthGuardControllerEventListener.php](src/Event/BcAuthGuardControllerEventListener.php)
- [src/Service/BcAuthGuardService.php](src/Service/BcAuthGuardService.php)
- [src/Service/BcAuthGuardSettingsService.php](src/Service/BcAuthGuardSettingsService.php)
- [src/Controller/Admin/BcAuthGuardConfigsController.php](src/Controller/Admin/BcAuthGuardConfigsController.php)
- [src/Controller/Admin/BcAuthGuardLockoutsController.php](src/Controller/Admin/BcAuthGuardLockoutsController.php)
- [config/Migrations/20260425000000_Initial.php](config/Migrations/20260425000000_Initial.php)

## テスト

主要ロジックの単体テストは次に実装済みです。

- [tests/TestCase/Service/BcAuthGuardServiceTest.php](tests/TestCase/Service/BcAuthGuardServiceTest.php)

今後の拡張候補:

- イベントリスナー（ログインフロー）の統合テスト追加
- 管理画面コントローラのリクエストテスト追加

## 関連ドキュメント

- [../BcAuthCommon/docs/auth-plugin-spec-summary.md](../BcAuthCommon/docs/auth-plugin-spec-summary.md)
- [../BcAuthCommon/docs/auth-common-architecture.md](../BcAuthCommon/docs/auth-common-architecture.md)

## ライセンス

MIT License.

詳細は [LICENSE.md](LICENSE.md) を参照してください。
