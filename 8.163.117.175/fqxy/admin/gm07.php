<?php
// GM 发放物品 → 新物品系统 player_item（背包）
require_once(__DIR__."/../../lib/ItemDb.php");
$wpid=(int)$wpid; $wpsl=(int)$wpsl; $wjid=(int)$wjid;
$__def=ItemDb::def($wpid);
if($__def && $wpsl>=1 && $wpsl<=5000000 && $wjid>=10000001 && $wjid<=20000000){
    $__after=ItemDb::give($wjid,$wpid,$wpsl,'gm_give','gm:'.$wjiddd);
    if($__after!==false){
        echo "<font color=red>【发放成功通知】</font><br>";
        echo "已向玩家（".$wjid."）发放".htmlspecialchars($__def['name'])."x".$wpsl."，当前背包数量".$__after."<br>";
    }else{
        echo "<font color=red>发放失败</font><br>";
    }
}else{
    echo "<font color=red>参数有误或物品不存在</font><br>";
}
echo "<a href='gm.php?wjid=$wjiddd&&pass=$password&&gid=1'><font color=blue>【返回GM管理首页】</font></a><br>";
?>
