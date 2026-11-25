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
 * 全局玩家在线状态：最后一次有效游戏请求起 30 分钟内视为在线。
 * 兼容 PHP 5.6；沿用 user.ini「验证信息」中的年/月/日/时/分/秒，不另建数据库表。
 */
if (!function_exists('xy_player_last_active')) {
    function xy_player_last_active($playerId)
    {
        static $cache = array();
        $playerId = (int)$playerId;
        if ($playerId <= 10000000) return 0;
        if (isset($cache[$playerId])) return $cache[$playerId];

        $file = dirname(__DIR__) . '/ache/' . $playerId . '/user.ini';
        if (!DbIniStore::isFile($file)) { $cache[$playerId] = 0; return 0; }
        $data = @DbIniStore::parseIniFile($file, true, INI_SCANNER_RAW);
        if (!is_array($data) || !isset($data['验证信息']) || !is_array($data['验证信息'])) {
            $cache[$playerId] = 0; return 0;
        }
        $v = $data['验证信息'];
        $y = isset($v['年']) ? (int)$v['年'] : 0;
        $m = isset($v['月']) ? (int)$v['月'] : 0;
        $d = isset($v['日']) ? (int)$v['日'] : 0;
        $h = isset($v['时']) ? (int)$v['时'] : 0;
        $i = isset($v['分']) ? (int)$v['分'] : 0;
        $s = isset($v['秒']) ? (int)$v['秒'] : 0;
        if ($y < 2000 || $m < 1 || $m > 12 || $d < 1 || $d > 31 || $h < 0 || $h > 23 || $i < 0 || $i > 59 || $s < 0 || $s > 59) {
            $cache[$playerId] = 0; return 0;
        }
        $ts = @mktime($h, $i, $s, $m, $d, $y);
        $cache[$playerId] = $ts ? (int)$ts : 0;
        return $cache[$playerId];
    }

    function xy_player_is_online($playerId, $now = null)
    {
        static $cache = array();
        $playerId = (int)$playerId;
        if ($playerId <= 10000000) return false;
        if ($now === null && isset($cache[$playerId])) return $cache[$playerId];
        if ($now === null) $now = time();
        $last = xy_player_last_active($playerId);
        $diff = (int)$now - (int)$last;
        $online = ($last > 0 && $diff >= 0 && $diff < 1800);
        if (func_num_args() < 2) $cache[$playerId] = $online;
        return $online;
    }

    /** 把所有指向玩家资料页(旧93/新742)的链接统一按在线状态处理。 */
    function xy_filter_offline_player_links($html, $cmdIds, $pageIds, $targetIds)
    {
        $targets = array();
        if (is_array($cmdIds) && is_array($pageIds) && is_array($targetIds)) {
            foreach ($cmdIds as $idx => $cmdValue) {
                $page = isset($pageIds[$idx]) ? (int)$pageIds[$idx] : 0;
                $target = isset($targetIds[$idx]) ? (int)$targetIds[$idx] : 0;
                if (($page === 93 || $page === 742) && $target > 10000000 && $cmdValue !== '') {
                    $targets[(string)$cmdValue] = $target;
                }
            }
        }
        if (!$targets || !is_string($html) || $html === '') return $html;

        return preg_replace_callback('~<a\\b([^>]*)>(.*?)</a>~is', function ($m) use ($targets) {
            $attrs = $m[1];
            if (!preg_match('~\\bhref\\s*=\\s*([\\\'\"])(.*?)\\1~is', $attrs, $hm)) return $m[0];
            $href = html_entity_decode($hm[2], ENT_QUOTES, 'UTF-8');
            if (!preg_match('~[?&]+cmd=(\\d+)~i', $href, $cm)) return $m[0];
            $cmd = (string)$cm[1];
            if (!isset($targets[$cmd]) || xy_player_is_online($targets[$cmd])) return $m[0];

            $inner = $m[2];
            $inner = preg_replace_callback('~<font\\b([^>]*)>~i', function ($fm) {
                $a = $fm[1];
                if (preg_match('~\\bcolor\\s*=~i', $a)) {
                    $a = preg_replace('~\\bcolor\\s*=\\s*([\\\'\"]?)[^\\\'\\\"\\s>]+\\1~i', 'color="black"', $a);
                } else {
                    $a .= ' color="black"';
                }
                return '<font' . $a . '>';
            }, $inner);
            // 行内 style 也统一改为黑色，避免 span 等标签覆盖离线黑字。
            $inner = preg_replace_callback("~\\bstyle\\s*=\\s*([\'\"])(.*?)\\1~is", function ($sm) {
                $style = $sm[2];
                if (preg_match('~(^|;)\\s*color\\s*:~i', $style)) {
                    $style = preg_replace('~(^|;)(\\s*color\\s*:\\s*)[^;]*~i', '$1$2black', $style);
                } else {
                    $style = rtrim($style, '; ') . ';color:black';
                }
                return 'style="' . htmlspecialchars($style, ENT_QUOTES, 'UTF-8') . '"';
            }, $inner);
            if (stripos($inner, '<font') === false) $inner = '<font color="black">' . $inner . '</font>';
            return $inner;
        }, $html);
    }
}
