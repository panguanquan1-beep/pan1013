<?php
/**
 * 全局通用辅助函数（可选引入）
 *
 * 目的：减少模板里大量重复的 $cmid++ / $cdid[] / $clj[] / $npc[] 代码
 * 使用方式（新代码推荐）：
 *   include_once("./core/helpers.php");
 *   echo cmd_a('我要存款', 258) . "<br>";
 *
 * 完全向后兼容：不引入本文件、继续写旧四行代码也能正常工作。
 */

if (!function_exists('cmd_link')) {

    /**
     * 注册一条超链接命令，并返回可用于 href 的 cmd 值
     *
     * @param int $pageId  对应的页面/功能 ID（旧代码里的 $clj[] 值）
     * @param int $npcId   NPC 或参数 ID（旧代码里的 $npc[] 值，默认 0）
     * @return int         本次分配的 $cmid
     */
    function cmd_link($pageId, $npcId = 0)
    {
        global $cmid, $cdid, $clj, $npc;

        if (!isset($cmid)) {
            $cmid = 0;
        }
        // 地图等模块会通过引用改写局部 $cmid，函数里的 global 可能落后；
        // 以已注册的 cdid 最大值为准，避免同一 cmd 被后续底部链接覆盖。
        if (isset($cdid) && is_array($cdid) && $cdid) {
            $__maxCmd = max(array_map('intval', $cdid));
            if ($__maxCmd > $cmid) $cmid = $__maxCmd;
        }
        $cmid = $cmid + 1;

        if (!isset($cdid) || !is_array($cdid)) {
            $cdid = array();
        }
        if (!isset($clj) || !is_array($clj)) {
            $clj = array();
        }
        if (!isset($npc) || !is_array($npc)) {
            $npc = array();
        }

        $cdid[] = $cmid;
        $clj[]  = $pageId;
        $npc[]  = $npcId;

        return $cmid;
    }

    /**
     * 生成完整的 <a> 标签（最常见用法）
     *
     * @param string $text   链接文字
     * @param int    $pageId 页面 ID
     * @param int    $npcId  参数
     * @param string $color  颜色，默认 blue
     * @return string
     */
    function cmd_a($text, $pageId, $npcId = 0, $color = 'blue')
    {
        global $wjid, $a1;
        $id = cmd_link($pageId, $npcId);
        // 保持与旧模板完全一致的 URL 写法（&&）
        return "<a href='xy.php?uid={$wjid}&&cmd={$id}&&sid={$a1}'><font color={$color}>{$text}</font></a>";
    }

    /**
     * 标准「返回游戏」链接
     */
    function cmd_back_game($text = '返回游戏')
    {
        return cmd_a($text, 2, 0);
    }
}

if (!function_exists('fmt_silver')) {
    /**
     * 银两统一格式：N兆N亿N万N两（兆=10^12，最大单位，超过后兆的数字继续增长）
     * 从最高非零单位起每段都写（含0），全为0显示“0两”。$suffix=false 时不带“两”（兼容旧 $ylxx）
     */
    function fmt_silver($v, $suffix = true)
    {
        $s = ltrim(preg_replace('/\D/', '', (string)$v), '0');
        if ($s === '') return '0' . ($suffix ? '两' : '');
        $n = strlen($s);
        $yy = (int)substr($s, max(0, $n - 4));
        $w  = $n > 4 ? (int)substr($s, max(0, $n - 8), min(4, $n - 4)) : 0;
        $y  = $n > 8 ? (int)substr($s, max(0, $n - 12), min(4, $n - 8)) : 0;
        $z  = $n > 12 ? ltrim(substr($s, 0, $n - 12), '0') : '';
        // 参考（账号2）：从最高非零单位开始，后面每段都写，0 也写（3万0两、100亿0万0两）
        $out = '';
        if ($z !== '') $out = $z . '兆' . $y . '亿' . $w . '万' . $yy;
        elseif ($y > 0) $out = $y . '亿' . $w . '万' . $yy;
        elseif ($w > 0) $out = $w . '万' . $yy;
        else $out = (string)$yy;
        return $out . ($suffix ? '两' : '');
    }
}


/** 把公告页的进入/返回编号立刻写入 user.ini 并落库，避免点「进入游戏」时还是旧校验表。 */
if (!function_exists('xy_notice_bind_cmds')) {
function xy_notice_bind_cmds()
{
    global $wjid, $enter_cmd, $back_cmd;
    $enter_cmd = (int)$enter_cmd;
    $back_cmd = (int)$back_cmd;
    if ($enter_cmd <= 0 || $back_cmd <= 0 || empty($wjid)) return;
    $u = new iniFile('ache/' . $wjid . '/user.ini');
    $xc = (int)$u->getItem('验证信息', 'xcmid值');
    $dc = (int)$u->getItem('验证信息', 'dcmid值');
    if ($xc <= 0 || $enter_cmd < $xc) $xc = $enter_cmd;
    if ($back_cmd > $dc) $dc = $back_cmd;
    $u->updItem('验证信息', array('xcmid值' => $xc, 'dcmid值' => $dc));
    $u->addItem('超链接值', array((string)$enter_cmd => 766, (string)$back_cmd => 767));
    $u->addItem('超链接npc值', array((string)$enter_cmd => 0, (string)$back_cmd => 0));
    if (class_exists('DbIniStore', false)) {
        DbIniStore::flushAll();
    }
}
}
