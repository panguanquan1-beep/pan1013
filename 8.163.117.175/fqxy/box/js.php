<?php
/**
 * 钻石顶单次抽奖
 * 只负责：抽 1 件 + 入包
 * 显示由外层统一处理
 */
$manzu1 = "";
$kcrl   = "";
$wpdz1  = array();
$wpdz2  = array();
$wpdz3  = array();
$wpdz4  = array();
$wpdz5  = array();

// 权重表（总和 10000）
$pool = array(
    array(3500, '【强化精魄】',               2, 1107, 20),
    array(2000, '〖金龙宝箱〗',               4, 1108, 10),
    array(1000, '〖幸运宝石〗',               2, 1109, 10),
    array(1000, '〖三味真火〗',               2, 1110, 1),
    array(1500, '〖上古诛仙遗迹〗',           4, 1111, 2),
    array( 300, '【强化精魄】',               2, 1107, 70),
    array( 300, '〖财气防具宝石召唤箱〗',     4, 1112, 8),
    array( 200, '〖一亿银两包〗',             4, 1113, 6789),
    array( 250, '〖圣灵石〗',                 4, 1114, 1),
    array(  70, '〖八级魔化五爪金龙召唤箱〗', 8, 1115, 1),
    array(  50, '〖五级财气防具宝石召唤箱〗', 4, 1116, 1),
    array(  30, '〖九级魔化五爪金龙召唤箱〗', 8, 1117, 1),
    array(  20, '〖十级魔化五爪金龙召唤箱〗', 8, 1118, 1),
    array(  10, '〖帝王权杖〗',               4, 1119, 1),
    array(  10, '〖灵气固元心法〗',           4, 1120, 1),
);

$totalWeight = 0;
foreach ($pool as $row) $totalWeight += $row[0];
$rand = mt_rand(1, $totalWeight);
$cursor = 0;
$hit = $pool[0];
foreach ($pool as $row) {
    $cursor += $row[0];
    if ($rand <= $cursor) { $hit = $row; break; }
}

// 写入奖励（供 rwget 入包）
$wpdz1[] = $hit[1];
$wpdz2[] = $hit[2];
$wpdz3[] = $hit[3];
$wpdz4[] = $hit[4];
$wpdz5[] = 1;

// 入包（不依赖它的默认显示）
ob_start();
include("./rwmap/rwget.php");
ob_end_clean();

// 把本次结果交给外层
$js_last_name = $hit[1];
$js_last_num  = intval($hit[4]);
?>