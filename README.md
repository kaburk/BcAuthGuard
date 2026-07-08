# BcAuthGuard plugin for baserCMS

BcAuthGuard は、baserCMS 5 の管理画面ログインに対して試行制限（ロックアウト）を行い、
認証失敗イベントの監査ログを記録するためのプラグインです。

このプラグイン単体では動作しません。事前に BcAuthCommon の導入が必要です。

## 目的

- 管理画面ログインのブルートフォース対策を強化する
- ロック中拒否・IP拒否を含む認証イベントの監査性を高める
- ロック情報を管理画面から運用できるようにする

## 機能

- 一定時間内の失敗回数に応じた一時ロック
	- 初期値: 10分間に5回失敗で10分ロック
- ログイン成功時の失敗カウンタ解放
- 単体IP / CIDR による拒否設定
- 認証イベントの監査ログ記録
	- `login_failure`
	- `lockout_started`
	- `lockout_denied`
	- `blocked_ip_denied`
- Guard 固有イベントの Dblog（最近の動き）記録
	- ロック開始
	- ロック中拒否
	- IP拒否
	- ログイン成功による制限解除

## 前提

- BcAuthCommon が有効化されていること
- 対象は baserCMS 5 系の管理画面ログイン（Admin）

## 管理画面

- 認証ガード設定
	- Plugins > 認証ガード設定
	- 保存内容は [config/setting_customize.php](config/setting_customize.php) に反映
- ロック中一覧
	- Plugins > ロック中一覧
	- 検索条件: 状態 / プレフィックス / ログインID / IPアドレス
	- ロック中レコードの手動解除

## 設定の要点

- 設定定義は [config/setting.php](config/setting.php)
- [config/setting_customize.php](config/setting_customize.php) で上書き可能

初期値:

- `limitWindowMinutes`: 10
- `limitCount`: 5
- `lockMinutes`: 10
- `enableIpBlock`: true
- `blockedIps`: []

`blockedIps` の設定例:

- `192.0.2.10`
- `198.51.100.0/24`
- `2001:db8::/32`

## 監査ログ

本プラグインは [BcAuthCommon](../BcAuthCommon/README.md) の
[src/Service/AuthLoginLogService.php](../BcAuthCommon/src/Service/AuthLoginLogService.php) を利用して、
認証イベントを `bc_auth_login_logs` テーブルに保存します。

通常のログイン成功 / ログアウトの監査ログは BcAuthCommon 側で処理します。

## 関連プラグイン

- [../BcAuthCommon/README.md](../BcAuthCommon/README.md)
- [../BcAuthPasskey/README.md](../BcAuthPasskey/README.md)
- [../BcAuthSocial/README.md](../BcAuthSocial/README.md)
- [../BcAuthGuard/README.md](../BcAuthGuard/README.md)

## ライセンス

MIT License.

詳細は [LICENSE.md](LICENSE.md) を参照してください。
