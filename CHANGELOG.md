# CHANGELOG

## 1.2.0 (2026-11-05)

確認で見つかった不具合の修正と、セキュリティまわりのテコ入れ。基本の使い方は従来通り。

### 直した不具合（重要）

- **2ページ目が作れなかった問題を修正**
  Router が常時 `index.php` を読みに行く設計で、エントリ側の `action()` と別ページの `action()` が同名衝突し、どちらの経路でも `Cannot redeclare action()` の Fatal error になっていた。
  フロントコントローラ `app.php` を追加し、`app.php?url=about` で別ページを実行できるようにした。`index.php` の直接実行（従来の単一ページ構成）はそのまま動く。
- **レイアウトが機能しなかった問題を修正（2件）**
  - config の `'layout'` が初期化の二重化で捨てられ、無効になっていた。
  - サンプルの `html/layout.html` が未定義変数 `$_poi_content_for_layout` を参照していて、本文が表示されなかった（View 側は互換のため両方の変数を渡すようにし、サンプルは正式名 `$_poi_content` に統一）。
- **DB を使わないページでも DB 接続エラーで落ちる問題を修正**
  起動時に無条件で接続していたため、同梱サンプルのままではトップページすら表示できなかった。Database を遅延接続（初回利用時に接続）に変更。
- **初期化（boot）が二重に行われていた問題を修正**
  コントローラが2つ作られ、`action()` の中では `$c->session` / `$c->csrf` / `$c->upload` / `$c->s` / `$c->v` が使えなかった。1つに統一し、Sanitize / Validate も dispatch 前にセットされる。
- **PHP 8.2+ の動的プロパティ非推奨警告を修正**
  `Controller::$config` / `$db` を正式に宣言。モデルの保持やアプリ側の自由なプロパティ追加も `__get/__set` で受け、警告を出さない。
- **404 / 例外時の扱いを追加**
  従来は未捕捉例外で HTTP 200 のままスタックトレース（パス含む）を表示していた。404 は 404、例外は 500 を返し、詳細は `debug` の時だけ表示、それ以外はログに残す。View で例外が起きた際の出力バッファの残りも解消。

### セキュリティ

- **テーブル名・カラム名の識別子インジェクション対策**
  `Database::insert/update/delete` と `Model::insert/update/findBy` 等で、識別子を検証（英数字と `_` のみ、`.` 区切り可）＋クォートするようにした。`$_POST` のキーなどをそのまま渡しても SQL を組み立てられない。
- **PDO のプリペアドを本物に**（`ATTR_EMULATE_PREPARES => false` を既定化）。
- **エラー表示を config の debug に連動**。従来は `debug=false` でも `display_errors=1` 固定で詳細が出ていた。
- **セッション Cookie に HttpOnly / SameSite=Lax（HTTPS 時 Secure）を付与**。`Session::regenerate()`（ログイン時の ID 再発行）と `Session::destroy()` を追加。
- **CSRF チェックの空トークンすり抜けを修正**（保存トークンが空の時、`checkToken('')` が true になっていた）。
- **アップロードの防御を強化**：ディレクトリ作成権限を 0777 → 0755、拒否拡張子を拡大（phar / スクリプト系 / .htaccess 形式 / svg 等）、ドットファイルと拡張子なしを拒否、`is_uploaded_file` 確認、任意のサイズ上限と許可リスト（`save($field, $dir, $maxBytes, $allowedExts)`）を追加。
- `Model` / `Database` の insert・update に空データのガードを追加。
- `Validate` が配列・null などの入力で TypeError にならないよう型ガードを追加。
- `Controller::loadModel()` に名前の検証を追加し、PDO 接続を `$c->db` と共有＆モデルをキャッシュするようにした（毎回新規接続していた）。

### その他

- `session.php` の文字化けコメント（Shift_JIS 混入でファイル自体が UTF-8 として不正）を修正し、ファイル全体を UTF-8 に統一。
- `Sanitize::postRaw()` / `getRaw()` を追加（エスケープなしの入力取得。DB 保存用はこちらを使い、表示時にエスケープする流れを README に明記）。
- `Sanitize::html(null)` が空文字を返すよう統一。
- `Model::update()` と `updateCount()` の重複実装を共通化。
- バージョン表記を 1.2.0 に統一（従来はコード内・README・定数で 1.1.3 / 1.1.2 / 1.1.1 / 1.0.0 とバラバラだった）。
- `tests/smoke.php`（CLI スモークテスト）を追加。

### 互換性メモ

- `new Database($dsn, ...)` は即時接続しなくなった。`->pdo` を直接触るコードは、最初に何かメソッドを呼ぶか `->pdo()` / `->connect()` を使ってほしい。
- Upload の既定拒否リストに `svg` が入った。SVG を許可したい場合は許可リスト引数で明示してほしい。
- `Model::update()` は成功時に true を返す（従来は PDOStatement::execute() の戻り値。そのままの意味）。
- レイアウト変数は `$_poi_content` が正式名。旧名 `$_poi_content_for_layout` も当面は両方渡される。
