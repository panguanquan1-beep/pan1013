<?php
/**
 * 页面公共引导（可选）
 *
 * 旧模板典型写法：
 *   $ininalock = $wjid."_lock.txt";
 *   include("./ini/zsini.php");
 *   if ($zsspd == 1) {
 *       // 业务...
 *       include("./ini/jsini.php");
 *   }
 *
 * 新写法（可选）：
 *   include_once("./core/page_bootstrap.php");
 *   if (page_lock()) {
 *       // 业务...
 *       page_unlock();
 *   }
 *
 * 变量与旧版完全兼容，可混用。
 */

if (!function_exists('page_lock')) {

    function page_lock($lockName = null)
    {
        global $wjid, $ininalock, $zsspd, $fp;

        if ($lockName === null) {
            $ininalock = $wjid . "_lock.txt";
        } else {
            $ininalock = $lockName;
        }

        include("./ini/zsini.php");
        return ($zsspd == 1);
    }

    function page_unlock()
    {
        global $zsspd, $fp; // 修复：原先在函数作用域里拿不到锁变量，总是输出“服务器开小差了”
        include("./ini/jsini.php");
    }

    /**
     * 带封号检测的页面入口
     * 返回 true 表示可以继续业务，false 表示已封号
     */
    function page_lock_and_check_ban()
    {
        if (!page_lock()) {
            return false;
        }
        include("./core/check_ban.php");
        if (isset($GLOBALS['fhbl']) && $GLOBALS['fhbl'] == 2) {
            // 封号信息已在 check_ban 中设置
            return false;
        }
        // 兼容旧变量作用域
        global $fhbl, $jyts1, $jyts;
        return ($fhbl != 2);
    }
}
?>
