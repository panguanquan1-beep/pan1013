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
 * 背包类页面公共头部（书卷/材料/装备/商城/丹药/任务/农场/宝箱/其他 等）
 *
 * 旧页面大量重复：
 *   include seach / zsini / zt_ini / yl_ini / bbrl_ini
 *   以及「保存页面id方便分页」的判断
 *
 * 使用方式（可选）：
 *   include("./core/bag_header.php");
 *   // 之后直接写列表逻辑，最后 include jsini 或 page_unlock()
 *
 * 会设置/更新：
 *   - 锁已获取（$zsspd==1 时才继续）
 *   - 背包页面 id 写入 yl.ini（若当前 cmdd 属于背包系列）
 */

// 阻塞锁
$ininalock = $wjid . "_lock.txt";
include("./ini/zsini.php");

if ($zsspd != 1) {
    return; // 抢锁失败，调用方不应继续输出业务
}

// 搜索框（部分背包页有）
if (DbIniStore::isFile("seach.php")) {
    // 部分页面在锁前就 include 了 seach，这里不强制
}

include("./ini/zt_ini.php");
include("./ini/yl_ini.php");
include("./ini/bbrl_ini.php");

// 保存页面 id 方便分页（与旧逻辑一致）
$bagPages = array(27, 28, 29, 30, 31, 32, 33, 34, 35, 82);
if (isset($cmdd) && in_array((int)$cmdd, $bagPages, true)) {
    $iniFile->updItem('背包页面', array('页面id' => $cmdd));
}
?>
