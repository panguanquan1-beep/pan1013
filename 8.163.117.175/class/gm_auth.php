<?php
/**
 * GM 鉴权（服务器端校验，兼容 PHP 5.6 ~ 8.x）
 * 通过方式（任一）：
 *  1. 已有 GM 会话 $_SESSION['gm_uid']
 *  2. URL 中 wjid + pass 与 xxjyuser.gmuser 表中记录一致（admin/login.php 登录后生成的链接）
 *  3. gmkey 等于 config/app.php 中的 gm_secret
 * 用法：require_once __DIR__.'/../class/gm_auth.php'; gm_require();
 */
if (!function_exists('gm_require')) {
function gm_config() {
    static $c = null;
    if ($c === null) { $c = include __DIR__ . '/../config/app.php'; }
    return $c;
}
function gm_hash_equals($a, $b) {
    if (function_exists('hash_equals')) return hash_equals((string)$a, (string)$b);
    return (string)$a === (string)$b;
}
function gm_check() {
    if (session_id() === '') @session_start();
    if (!empty($_SESSION['gm_uid'])) return (int)$_SESSION['gm_uid'];
    $c = gm_config();
    if (!empty($c['gm_secret']) && isset($_REQUEST['gmkey']) && gm_hash_equals($c['gm_secret'], $_REQUEST['gmkey'])) {
        $_SESSION['gm_uid'] = 10000001; return 10000001;
    }
    $wjid = isset($_GET['wjid']) ? (int)$_GET['wjid'] : 0;
    $pass = isset($_GET['pass']) ? (string)$_GET['pass'] : '';
    if ($wjid <= 10000000 || $pass === '') return 0;
    $d = $c['db'];
    $m = @new mysqli($d['host'], $d['user'], $d['pass'], $d['user_db'], $d['port']);
    if ($m->connect_errno) return 0;
    $uid = $wjid - 10000000;
    $st = $m->prepare('SELECT password FROM gmuser WHERE uid=?');
    $st->bind_param('i', $uid); $st->execute(); $st->bind_result($hash);
    $ok = $st->fetch() && $hash !== '' && gm_hash_equals($hash, $pass);
    $st->close(); $m->close();
    if ($ok) { $_SESSION['gm_uid'] = $wjid; return $wjid; }
    return 0;
}
function gm_require() {
    $gm = gm_check();
    if (!$gm) {
        header('HTTP/1.1 403 Forbidden');
        echo "<font color=red>GM 验证失败，请重新登录</font><br><a href='/admin/login.php'>返回GM登录</a>";
        exit;
    }
    return $gm;
}
}
