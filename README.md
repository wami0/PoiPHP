# PoiPHP – 最小・明快・必要最小限の PHP フレームワーク
**Version: 1.1.2**

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
 ├ index.php         ← アプリの入口（actionを書く）
 ├ html/             ← テンプレート
 │   ├── header.html     # 共通ヘッダー
 │   ├── footer.html     # 共通フッター
 │   └── top.html        # コンテンツ
 └ PoiPHP/           ← フレームワーク本体
     ├ core/         ← フレームワーク内部クラス
     ├ models/       ← モデル（必要な人だけ作る・任意）
     ├ poiphp.php    ← メインローダー
     └ config.php    ← フレームワーク設定
```

---

## 最小のサンプルコード

まずはこれだけで画面が表示されます。

#### 【index.php】

```php
<?php
require_once __DIR__ . '/PoiPHP/poiphp.php';

// 全ての処理はこの action 関数から始まります
function action(&$c)
{
    // テンプレートへ値を渡す
    $c->set("message", "Hello PoiPHP!");
    
    // 表示するテンプレートを指定
    $c->setTemplateFile("html/index.html");
}
```

#### 【html/index.html】

```html
<h1><?= $s->html($message) ?></h1>
```

---

## テンプレートとレイアウト

PoiPHP は `layout.html` を使った、シンプルで分かりやすいテンプレート構造を採用しています。

- **共通デザイン（layout.html）** にヘッダーやフッターを書く。
- **各ページのテンプレート** には「中身」だけを書く。
- 実行時、**`<?= $_poi_content ?>`** の位置に中身が自動で合体します。



---

## セキュリティ（サニタイズ）

テンプレートでは、安全な出力のために **`$s`** オブジェクトが自動で使えます。

- **`$s->h($str)`** : HTMLエスケープ（`htmlspecialchars` の短縮形）
- **`$s->url($str)`** : URLエンコード
- **`$s->json($data)`** : JavaScriptに渡すための安全なJSON出力

---

## インストール

PoiPHP は依存ライブラリがありません。

1. **PoiPHP をダウンロード** します。
2. **自分のサーバー**（Apache/Nginx等）に配置します。
3. **`PoiPHP/config.php`** で DB 設定をして完了！

```text
/your-app/
 ├ index.php
 ├ html/
 │   └ index.html
 └ PoiPHP/
     ├ config.php
     ├ poiphp.php
     ├ models/（任意）
     └ core/
```

---

## ライセンス

**License: MIT License**（商用・個人問わず自由に使えます）

---

## 公式サイト

[https://poiphp.m2-lab.net/](https://poiphp.m2-lab.net/)

