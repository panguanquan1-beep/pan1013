<?php
/**
 * 封号检测（从多个模板中抽取的公共逻辑）
 *
 * 使用方式（保持旧变量兼容）：
 *   include("./core/check_ban.php");
 *   if ($fhbl != 2) {
 *       // 正常业务
 *   } else {
 *       // 已封号，可输出 $jyts1 / $jyts
 *   }
 *
 * 输出/设置的变量与旧代码完全一致：
 *   $fhbl, $jyts1, $jyts, $jybl 等
 */

$fhbl  = 0;
$jybl  = 0;
$jyts1 = "[否]";
$jyts  = "";

include_once("./ini/alluser_ini.php");

$jy01 = $iniFile->getItem('封号信息', '永久');
$jy02 = $iniFile->getItem('封号信息', '封号时间');
$jy03 = $iniFile->getItem('封号信息', '解除变量');

if ($jy01 != 2) {
    if ($jy03 >= 1) {
        $startdate = $jy02;
        $enddate   = date('Y-m-d H:i:s');
        $minute    = floor((strtotime($enddate) - strtotime($startdate)));

        if ($minute <= $jy03) {
            $jybl    = 2;
            $minute1 = $jy03 - $minute;

            $minute = floor($minute1 / 60);
            $second = $minute1 % 60;

            if ($minute > 0) {
                $jyts1   = "由于该账号违反游戏规定已做封停处理，具体原因请咨询管理员";
                $minute10 = floor($minute / 60);
                if ($minute10 > 0) {
                    $minute9 = floor($minute10 / 24);
                    if ($minute9 > 0) {
                        $second = $minute10 % 24;
                        $jyts   = $minute9 . "天" . $second . "小时";
                        $fhbl   = 2;
                    } else {
                        $second = $minute % 60;
                        $jyts   = $minute10 . "时" . $second . "分";
                        $fhbl   = 2;
                    }
                } else {
                    $jyts = $minute . "分" . $second . "秒";
                    $fhbl = 2;
                }
            } else {
                $jyts1 = "由于该账号违反游戏规定已做封停处理，具体原因请咨询管理员";
                $jyts  = $second . "秒";
                $fhbl  = 2;
            }
        } else {
            $jyts1 = "[否]";
        }
    } else {
        $jyts1 = "[否]";
    }
} else {
    $jyts1 = "由于该账号违反游戏规定已做封停处理，具体原因请咨询管理员";
    $jyts  = "【永久封停】";
    $fhbl  = 2;
}
?>
