# PoiPHP – 最小・明快・必要最小限の PHP フレームワーク

**Version: 1.2.0**

PoiPHPは、PHP8環境で動作する、学習コストを最小限に抑えた超軽量・高速なマイクロフレームワークです。

- **設定ファイルは 1 つだけ**
- **コントローラは 1 ファイルで完結**（`index.php` に `action` を書く）
- **layout.html によるシンプルなテンプレート構造**
- **Model は「1 テーブル = 1 クラス」**
- **依存ライブラリなし**
- **小さく、読みやすく、迷わない設計**

小規模〜中規模の Web アプリケーションを素早く・気持ちよく構築するためのフレームワークです。

---

## ディレクトリ構造

```text
/your-app/
├ index.php ← トップページの入口（actionを書く）
├ app.php ← 複数ページ用エントリ（フロントコントローラ）
├ about.php ← 追加ページの例（actionだけを書く）
├ html/ ← テンプレート
│ ├── layout.html # 共通レイアウト
│ └── index.html # コンテンツ
├ models/ ← モデル（必要な人だけ作る・任意）
└ PoiPHP/ ← フレームワーク本体
├ core/ ← フレームワーク内部クラス
├ poiphp.php ← メインローダー
└ config.php ← フレームワーク設定
```

---

## 最小のサンプルコード

まずはこれだけで画面が表示されます。DB を使わないページでは、DB 設定があっても接続は行われません（遅延接続）。

####

```php
<?php
require_once __DIR__. '/PoiPHP/poiphp.php';

// 全ての処理はこの action 関数から始まります
function action(&$c)
{
// テンプレートへ値を渡す
$c->set("title", "PoiPHPサンプル");
$c->set("message", "Hello PoiPHP!");

// 表示するテンプレートを指定
$c->setTemplateFile("html/index.html");
}
```

####

```html
<h1><?= $s->html($title)?></h1>
<p><?= $s->html($message)?></p>
```

---

## 複数ページの作り方

ページを増やす時は、フロントコントローラ `app.php` を入口にします。

1. アプリルートに `about.php` を置き、`action()` だけを書きます（`require` 行は不要です）。

```php
<?php
// about.php ── require 行は書かないこと
function action(&$c)
{
$c->set("title", "About");
$c->set("message", "このページについて");
$c->setTemplateFile("html/about.html");
}
```

2. `app.php?url=about` でアクセスします。

> 注意：`index.php?url=about` の形は使えません。`index.php` 自身が `action()` を持っているため、別ページの `action()` と同名衝突してしまいます。その場合はエラーメッセージで `app.php` 経由が案内されます。

Apache で URL をきれいにしたい場合は、アプリルートに `.htaccess` を置きます。

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME}!-f
RewriteCond %{REQUEST_FILENAME}!-d
RewriteRule ^(.*)$ app.php?url=$1 [QSA,L]
```

---

## テンプレートとレイアウト

PoiPHP は `layout.html` を使った、シンプルで分かりやすいテンプレート構造を採用しています。

- **共通デザイン（layout.html）** にヘッダーやフッターを書く。
- **各ページのテンプレート** には「中身」だけを書く。
- 実行時、**`<?= $_poi_content?>`** の位置に中身が自動で合体します。

レイアウトは `PoiPHP/config.php` の `'layout' => 'html/layout.html'` で全体に指定するか、ページごとに `$c->setLayoutFile("html/layout.html")` で指定します。

---

## セキュリティ（サニタイズ）

テンプレートでは、安全な出力のために **`$s`** オブジェクトが自動で使えます。

- **`$s->h($str)`** / **`$s->html($str)`**: HTMLエスケープ（`htmlspecialchars` の短縮形）
- **`$s->url($str)`**: URLエンコード
- **`$s->json($data)`**: JavaScriptに渡すための安全なJSON出力

入力の受け取りについて：

- `$s->post($key)` / `$s->get($key)` は **表示用** にエスケープ済みの値を返します。
- DB へ保存する値や数値として扱う値は **`$s->postRaw($key)` / `$s->getRaw($key)`** で受け取り、表示する時に `$s->html()` でエスケープしてください（保存時にエスケープすると二重エスケープやデータの化けの原因になります）。

CSRF 対策は、フォームにトークンを埋めて送信時に検証します。

```php
// テンプレート側
<input type="hidden" name="_csrf" value="<?= $s->html($c->csrf)?>">

// action() 側（POST を処理する前）
if (!$c->session->checkToken($s->postRaw('_csrf'))) {
http_response_code(403);
exit('Invalid CSRF token');
}
```

ファイルアップロード（`$c->upload->save()`）は、公開フォームでは許可リスト方式を推奨します。

```php
$path = $c->upload->save('photo', __DIR__. '/uploads', 2 * 1024 * 1024, ['jpg', 'png', 'gif']);
```

---

## インストール

PoiPHP は依存ライブラリがありません。

1. **PoiPHP をダウンロード** します。
2. **自分のサーバー**（Apache/Nginx等）に配置します。
3. **`PoiPHP/config.php`** で DB 設定をして完了！（本番では `'debug' => false` にしてください）

```text
/your-app/
├ index.php
├ app.php
├ html/
│ └ index.html
└ PoiPHP/
├ config.php
├ poiphp.php
├ models/（フレームワーク側・任意）
└ core/
```

---

## ライセンス

**License: MIT License**（商用・個人問わず自由に使えます）

---

## 公式サイト

[poiphp.m2-lab.net](https://poiphp.m2-lab.net/)
