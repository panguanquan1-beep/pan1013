<?php
require_once __DIR__ . '/../../lib/PlayerSqlCompat.php';

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






//获取全区所有人员id所有


include("./sql/mysql.php");//调用数据库连接 

$q2="all_zt";
PlayerSqlCompat::query("set names utf8");
$str="select wjid from $q2";
$result=PlayerSqlCompat::query($str) or die('SQL语句有误');
//把有值的数据存入一个数组
$xxcc=0;

 while(!!$row=mysql_fetch_array($result)){
$sxwjid[]=$row[wjid];
$xxcc=$xxcc+1;

}
$sxi=-1;


//清空
$q2="all_phb";
$strsql="truncate table $q2";
$result=PlayerSqlCompat::query($strsql);

$maxidd=0;
for($d=0;$d<$xxcc;$d++){
$sxi=$sxi+1;
$wjid=$sxwjid[$sxi];
//阻塞代码防止出现脏数据
$ininalock=$wjid."_lock".".txt";
include("./ini/zsini.php");
if($zsspd==1){



//调用zt.ini是否存在
$wjxx="";
include("./ini/zt_ini.php");
include("wj/ztt.php");
include("./ini/zt_ini.php");
$wjxx=($iniFile->getCategory('玩家信息'));

if($wjxx[玩家名字]!=""){
$maxidd=$maxidd+1;
$q2="all_phb";
PlayerSqlCompat::query("set names utf8");
$sql = "insert into $q2 (id,wjid,wjmz,vip,phb1,phb2,phb3,phb4,phb5,phb6,phb7,phb8)  values('$maxidd','$wjid','$wjxx[玩家名字]','$wjxx[vip等级]','$wjxx1[血]','$wjxx1[max攻击]','$wjxx1[max魔攻]','$wjxx1[max防御]','$wjxx[等级]','0','0','0')";
if (!PlayerSqlCompat::query($sql,$conn)){
die('Error: ' . mysql_error());
}
} else{	
}





} else{	
}
//解锁当前使用的ini
include("./ini/jsini.php");
//解锁当前使用的ini








}


function deldir($dir) {
  // 旧目录中的 .ini 状态已迁入数据库；刷新时删除对应 DB 记录，不再依赖 opendir/readdir。
  DbIniStore::deleteByPrefix($dir);
  // 非 .ini 的临时文件/锁文件仍按原文件系统方式清理。
  if (is_dir($dir)) {
    $dh = opendir($dir);
    if ($dh) {
      while (($file = readdir($dh)) !== false) {
        if ($file === '.' || $file === '..') continue;
        $fullpath = $dir . '/' . $file;
        if (is_dir($fullpath)) deldir($fullpath);
        elseif (!preg_match('/\.ini$/i', $file)) @unlink($fullpath);
      }
      closedir($dh);
    }
  }
  return true;
}


$path='acher/phb';
deldir($path);




echo "<font color=red>排行榜数据已刷新</font>"."<br>";





echo "<font color=black>---------------------</font>"."<br>";
echo "<a href='gm.php?wjid=$wjiddd&&pass=$password&&gid=1'><font color=blue>【返回GM管理首页】</font></a>"."<br>";

echo "<br>";
echo "<font color=black>---------------------</font>"."<br>";


echo "<a href=http://".$xxjyurl."/admin/index.php?wjid=$wjid&&pass=$password><font color=blue>返回GM管理平台</font></a>"."<br>";
?>





