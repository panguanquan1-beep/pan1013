<?php
// 统一数据库状态存储：历史 .ini 路径不再访问磁盘
if (!class_exists('DbIniStore', false)) {
    $__dbini_root = __DIR__;
    while ($__dbini_root && !is_file($__dbini_root . '/config/app.php')) {
        $__dbini_parent = dirname($__dbini_root);
        if ($__dbini_parent === $__dbini_root) { $__dbini_root = false; break; }
        $__dbini_root = $__dbini_parent;
    }
    if ($__dbini_root && is_file($__dbini_root . '/lib/DbIniStore.php')) require_once $__dbini_root . '/lib/DbIniStore.php';
    unset($__dbini_root, $__dbini_parent);
}

/**
 * 页面路由（替代 xy01.php ~ xy07.php 的巨型 if-elseif）
 *
 * 行为与旧版完全一致：
 *   根据 $cmdd 直接 include 对应 template/xyXXX.php
 *   找不到时不做任何事（与旧 else{} 空块一致）
 *
 * 使用方式（在 xy.php 里替换原来的 7 个 include）：
 *   include("./core/router.php");
 *   // 或者保留旧 xy0x.php 作为兼容层（见下方 xy0x 兼容文件）
 */

if (!isset($cmdd)) {
    return;
}


$cmdd = (int)$cmdd;

// 旧房屋/官宅/家具页面统一停用。底层新系统由数据库模块接管，旧页面不再读写旧表。
// 157-166/169 已接回管家打造/摆放/升级（新库实现，不再拦截）。
$__houseLegacyPages = array(
    151,152,153,154,155,
    167,168,170,183,102,310,319,320,321,322,342
);
if (in_array($cmdd, $__houseLegacyPages, true)) {
    echo '<font color="red">房屋系统正在重构，旧住宅、官宅及家具功能已全部停用。</font><br/>';
    echo '<font color="black">后续将通过新的数据库系统重新开放。</font><br/>';
    echo "<a href='xy.php?uid=" . (int)$wjid . "&&cmd=" . (int)$cmid . "&&sid=" . htmlspecialchars($a1, ENT_QUOTES, 'UTF-8') . "'><font color='blue'>返回游戏</font></a><br/>";
    return;
}

// 页面 ID → 模板文件映射（可继续往下加）
// 未列出的 ID 会走自动规则：template/xy{ID三位数}.php
$pageMap = array(
    // 这里只放有特殊注释/需要特别说明的，其余走自动路径即可
    // 1 => 'template/xy001.php',  // 首页
    // 2 => 'template/xy002.php',  // 游戏主页
);
// === 如意回馈中心领取拦截 ===
if ($cmdd >= 2101 && $cmdd <= 2122) {
    $tpl = 'template/xy787.php';
} elseif (isset($pageMap[$cmdd])) {
    $tpl = $pageMap[$cmdd];
} else {
    // 自动规则: 三位数补零
    $tpl = sprintf('template/xy%03d.php', $cmdd);
}
if (DbIniStore::isFile($tpl)) {
    include($tpl);
} else {
    // ...
}
?>
