<?php
// PoiPHP スモークテスト（CLI 専用）
// 使い方: php tests/smoke.php
// Web サーバーは不要。SQLite のメモリ DB を使う。

error_reporting(E_ALL);

$root = dirname(__DIR__);
require_once $root . '/PoiPHP/core/sanitize.php';
require_once $root . '/PoiPHP/core/validate.php';
require_once $root . '/PoiPHP/core/database.php';
require_once $root . '/PoiPHP/core/model.php';
require_once $root . '/PoiPHP/core/session.php';
require_once $root . '/PoiPHP/core/upload.php';
require_once $root . '/PoiPHP/core/controller.php';
require_once $root . '/PoiPHP/core/view.php';

$pass = 0;
$fail = 0;
$deprecations = [];
set_error_handler(function ($no, $str) use (&$deprecations) {
    if ($no === E_DEPRECATED || $no === E_USER_DEPRECATED) {
        $deprecations[] = $str;
    }
    return false;
});

function ok($cond, $label) {
    global $pass, $fail;
    if ($cond) { $pass++; echo "  ok  - {$label}\n"; }
    else       { $fail++; echo "  NG  - {$label}\n"; }
}
function throws($fn, $label) {
    try { $fn(); ok(false, $label . '（例外が出なかった）'); }
    catch (Throwable $e) { ok(true, $label); }
}

echo "== Sanitize ==\n";
$s = new Sanitize();
ok($s->html('<a>&"') === '&lt;a&gt;&amp;&quot;', 'html escape');
ok($s->h('a<b') === 'a&lt;b', 'h alias');
ok($s->html(null) === '', 'html(null) === ""');
ok($s->html(['<x>']) === ['&lt;x&gt;'], 'html array');
ok($s->url('a b') === 'a%20b', 'url encode');
$_POST['name'] = '<b>poipoi</b>';
ok($s->post('name') === '&lt;b&gt;poipoi&lt;/b&gt;', 'post() はエスケープ済み');
ok($s->postRaw('name') === '<b>poipoi</b>', 'postRaw() は生値');
$_GET['q'] = 'a&b';
ok($s->getRaw('q') === 'a&b', 'getRaw() は生値');
ok(strpos($s->json(['x' => '</script>']), '</script>') === false, 'json は </script> を含まない');

echo "== Validate ==\n";
$v = new Validate();
ok($v->notempty('x') === '', 'notempty 成功');
ok($v->notempty(' ', 'err') === 'err', 'notempty 空白のみは失敗');
ok($v->len('あいう', 3) === '', 'len マルチバイト');
ok($v->number('123') === '', 'number');
ok($v->eisu('abc123') === '', 'eisu');
ok($v->email('a@example.com') === '', 'email');
ok($v->eisu(['x'], 'err') === 'err', '配列入力でも TypeError にならず失敗を返す');
ok($v->email(null, 'err') === 'err', 'null 入力でも落ちない');

echo "== Database (SQLite memory, 遅延接続) ==\n";
$db = new Database('sqlite::memory:');
ok($db->pdo === null, 'コンストラクタでは接続しない（遅延接続）');
$db->query("CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT, email TEXT)");
$id = $db->insert('users', ['name' => 'poi', 'email' => 'poi@example.com']);
ok((int)$id === 1, 'insert で ID が返る');
$row = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$id]);
ok($row['name'] === 'poi', 'fetchOne');
ok($db->fetchValue("SELECT name FROM users WHERE id = ?", [$id]) === 'poi', 'fetchValue');
$db->update('users', ['name' => 'poi2'], 'id = ?', [$id]);
ok($db->fetchValue("SELECT name FROM users WHERE id = ?", [$id]) === 'poi2', 'update');
throws(fn() => $db->insert('users', ['name); DROP TABLE users; --' => 'x']), 'カラム名インジェクションを拒否');
throws(fn() => $db->insert('users); DROP TABLE users; --', ['name' => 'x']), 'テーブル名インジェクションを拒否');
throws(fn() => $db->insert('users', []), '空データの insert を拒否');
ok($db->fetchValue("SELECT COUNT(*) FROM users") == 1, 'テーブルは無事（DROP されていない）');
$db->delete('users', 'id = ?', [$id]);
ok($db->fetchValue("SELECT COUNT(*) FROM users") == 0, 'delete');

echo "== Model ==\n";
$pdo = new PDO('sqlite::memory:', null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);
$pdo->exec("CREATE TABLE items (id INTEGER PRIMARY KEY, title TEXT)");
$m = new Model($pdo, 'items');
$mid = $m->insert(['title' => 'one']);
ok((int)$mid === 1, 'Model insert');
$m->insert(['title' => 'two']);
ok(count($m->findAll()) === 2, 'findAll');
ok($m->find($mid)['title'] === 'one', 'find');
ok(count($m->findBy('title', 'two')) === 1, 'findBy');
throws(fn() => $m->findBy('1=1; DROP TABLE items; --', 'x'), 'findBy のカラム名インジェクションを拒否');
ok($m->update($mid, ['title' => 'ONE']) === true, 'update');
ok($m->updateCount($mid, ['title' => 'ONE2']) === 1, 'updateCount');
ok($m->find($mid)['title'] === 'ONE2', '更新が反映されている');
$m->delete($mid);
ok($m->find($mid) === null, 'delete 後は null');

echo "== Session / CSRF ==\n";
$ses = new Session();
ok($ses->checkToken('') === false, 'トークン未発行時の checkToken("") は false');
$tok = $ses->csrfToken();
ok(is_string($tok) && strlen($tok) === 64, 'csrfToken 発行');
ok($ses->checkToken($tok) === true, '正しいトークンは true');
ok($ses->checkToken('wrong') === false, '違うトークンは false');
$ses->flash('msg', 'hello');
ok($ses->getFlash('msg') === 'hello', 'flash 取得');
ok($ses->getFlash('msg') === null, 'flash は1回で消える');

echo "== Upload（拒否系のみ。move_uploaded_file は CLI では検証不可） ==\n";
$up = new Upload();
$tmpdir = sys_get_temp_dir() . '/poi_upload_test';
foreach (['shell.php', '.htaccess', 'noext', 'evil.svg', 'x.php5'] as $name) {
    $_FILES['f'] = ['name' => $name, 'tmp_name' => __FILE__, 'error' => UPLOAD_ERR_OK, 'size' => 10];
    ok($up->save('f', $tmpdir) === false, "拒否: {$name}");
}
$_FILES['f'] = ['name' => 'big.jpg', 'tmp_name' => __FILE__, 'error' => UPLOAD_ERR_OK, 'size' => 9999];
ok($up->save('f', $tmpdir, 100) === false, 'サイズ上限で拒否');
$_FILES['f'] = ['name' => 'a.txt', 'tmp_name' => __FILE__, 'error' => UPLOAD_ERR_OK, 'size' => 10];
ok($up->save('f', $tmpdir, null, ['jpg', 'png']) === false, '許可リスト外は拒否');

echo "== Controller（動的プロパティ / モデル共有） ==\n";
if (!defined('SCRIPTDIR')) {
    define('SCRIPTDIR', sys_get_temp_dir() . '/');
}
$c = new Controller();
$c->config = ['app' => []];
$c->freeValue = 'abc';
ok($c->freeValue === 'abc', '未宣言プロパティへの代入も警告なしで使える');
$c->db = new Database('sqlite::memory:');
$c->db->query("CREATE TABLE notes (id INTEGER PRIMARY KEY, body TEXT)");
$notes = $c->loadModel('Note');
ok($notes instanceof Model, 'loadModel は汎用 Model を返す');
ok($c->loadModel('Note') === $notes, 'loadModel は同じインスタンスを返す');
ok($c->Note === $notes, '$c->Note でも取り出せる');
throws(fn() => $c->loadModel('../config'), 'loadModel のパストラバーサルを拒否');

echo "== View（レイアウト） ==\n";
$td = sys_get_temp_dir() . '/poi_view_test';
@mkdir($td, 0777, true);
file_put_contents($td . '/body.html', 'BODY:<?= $s->html($msg) ?>');
file_put_contents($td . '/layout.html', 'LAYOUT[<?= $_poi_content ?>]');
file_put_contents($td . '/layout_old.html', 'OLD[<?= $_poi_content_for_layout ?>]');
$view = new View(new Sanitize(), null);
$view->setFile($td . '/body.html', null);
$view->setVars(['msg' => 'hi<']);
$view->layout = $td . '/layout.html';
ob_start(); $view->display(); $out = ob_get_clean();
ok($out === 'LAYOUT[BODY:hi&lt;]', '$_poi_content に本文が入る');
$view2 = new View(new Sanitize(), null);
$view2->setFile($td . '/body.html', null);
$view2->setVars(['msg' => 'x']);
$view2->layout = $td . '/layout_old.html';
ob_start(); $view2->display(); $out2 = ob_get_clean();
ok($out2 === 'OLD[BODY:x]', '旧名 $_poi_content_for_layout も互換で動く');

echo "== その他 ==\n";
ok($deprecations === [], 'Deprecated 警告ゼロ' . ($deprecations ? '（' . implode(' / ', $deprecations) . '）' : ''));

echo "\n結果: PASS {$pass} / FAIL {$fail}\n";
exit($fail === 0 ? 0 : 1);
