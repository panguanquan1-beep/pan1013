幻想西游 · 2026-11-14 上线更新包
================================================================
用法：把本压缩包内的 lib/ 和 fqxy/ 两个目录，直接解压覆盖到线上站点根目录
      （宝塔一般是 /www/wwwroot/你的站点目录/，解压时选“覆盖”）。
      覆盖前建议先在宝塔「文件」里把要覆盖的 8 个文件各备份一份（或整站打包）。

包含的文件（8 个，全是 PHP，覆盖后立即生效，不用重启 nginx/php）：
  1. lib/DbIniStore.php         修 Windows 路径归一化（Linux 上行为不变，可安全覆盖）
  2. lib/BailpiaoFurnace.php    新增：白嫖炼丹炉逻辑（给丹、冷却、页面）
  3. lib/ItemBatchUse.php       新增：物品批量使用的一次性结算引擎
  4. lib/ItemDb.php             批量计数（addDailyUse / addGroupUse 支持一次加 N）
  5. lib/PlayerDb.php           新增 addExpRepeated（连用 N 次、只写一次库）
  6. fqxy/npc/npc_dispatch.php  白嫖炼丹炉入口分支（顶部 30001 那几行）
  7. fqxy/template/xy706.php    批量使用选数量页（多一行“本次最多可用 Y 个”）
  8. fqxy/template/xy707.php    批量使用执行页（不再循环 include item_use.php）

不需要改数据库：物品能不能批量看 item_def.batch_use，限用看 max_use / daily_max /
use_effect 里的 grp_max、grp_daily —— 这些线上本来就有。
GM 后台（fqxy/gm_admin/ + xy_gm_admin 表）不包含在本包里，需要的话单独上。

覆盖后自检：
  1) 首页能开、能登录
  2) 背包 → 药品 → 某件物品 → 批量使用：应显示“背包里 X 个，本次最多可用 Y 个”
  3) 十字街头 ☆白嫖炼丹炉☆ 点一下：应给 5 个〖白嫖丹〗并提示冷却 10 秒
