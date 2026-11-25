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




echo "<font color=red>【数据管理】</font>"."<br>";

//阻塞代码防止出现脏数据
$ininalock=$wjid."_lock".".txt";
include("./ini/zsini.php");
if($zsspd==1){





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



$path='ache/'.$wjid;




deldir($path);




} else{	
}
//解锁当前使用的ini
include("./ini/jsini.php");
//





deldir($path);
echo "<font color=black>数据刷新完毕</font>"."<br>";







echo "<br>";
echo "<font color=black>---------------------</font>"."<br>";
echo "<a href='gm.php?wjid=$wjiddd&&pass=$password&&gid=1'><font color=blue>【返回GM管理首页】</font></a>"."<br>";

echo "<br>";
echo "<font color=black>---------------------</font>"."<br>";


echo "<a href=http://".$xxjyurl."/admin/index.php?wjid=$wjid&&pass=$password><font color=blue>返回GM管理平台</font></a>"."<br>";


















?>





