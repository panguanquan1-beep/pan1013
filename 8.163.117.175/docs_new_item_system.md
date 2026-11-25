# 新物品系统（item_def）
- 表：item_category / item_def / player_item / item_log（hxxy 库，InnoDB utf8mb4）
- 建库顺序：sql_schema/001 → 002 → 003 → 004
- 分类序号（003）：1 装备、2 镶物、3 药品、4 任务、5 其它、6 书籍
  - 装备目前仍在旧装备系统（wp/data/equipment.php + zbb.ini），分类1留给以后的新装备物品
- 物品ID：从1自增，重新编号；legacy_id 仅记录旧ID。现有：1 回城卷、2 腾云符（都在 6 书籍，见004）
- 银两为大数，价格列 DECIMAL(65,0)
- 代码：lib/ItemDb.php（DAL）、fqxy/template/xy026(单页背包,分类导航动态读 item_category)、xy036 详情、xy037 使用、fqxy/wp/item_use.php(效果)、fqxy/item_debug.php(GM调试页)
- 旧调用：wp/ItemLoader.php 的 $wpfl 现在等于新分类号，与旧 ini 分类(1书卷…8宝箱)含义不同

## 装备部位 equip_slot（005）
1兵器 2身穿 3头戴 4脚穿 5佩戴 6手饰 7法宝 8坐骑 9婚戒 10婚链 11徽章 12国家荣耀石 13阵营灵气石 14混元灵魂石
- item_def.slot_id 可空，外键→equip_slot；分类1(装备)必须填，其它分类留空（MySQL5.6无CHECK，由 ItemDb::validateDef 校验，调试页标红）

## 旧装备系统的部位（未改动，供以后对接）
- 定义：wp/data/equipment.php（659件），经 wp/EquipmentLoader.php → $wp30=slot，$wp29=sect(门派限制:1-5门派,6通用,7宠物装备)
- 背包里的装备：ache/<uid>/zb.ini；身上穿戴：ache/<uid>/zbb.ini（[装备分类] 实例→部位，[装备分类id] 部位→实例，另有星级/孔/镶嵌）；宠物穿戴：cwzbb_<宠物>.ini
- 穿装备：template/xy082.php（同部位已穿则替换）
- 旧 slot 编号 → 含义（按装备名推断）→ 建议映射新部位：
  - 3 武器(182件) → 1兵器
  - 4 战甲/盔甲 → 2身穿
  - 5 头盔 → 3头戴
  - 6 战靴 → 4脚穿
  - 7 项链 → 5佩戴
  - 8 手镯 → 6手饰
  - 1 阐气瓶等 → 7法宝
  - 2 坐骑(风火双轮/麒麟…) → 8坐骑
  - 9 婚戒 → 9婚戒；10 婚链 → 10婚链
  - 11 披风 → 无对应（新表没有披风；可并入11徽章或新增部位）
  - 12-21 小恶魔/小天使/黑骑士/猎天使套(魔翼/杖/盾/镜/服/靴/戒/链/袍/勋章，各5件) → 无直接对应
  - 新的 12国家荣耀石/13阵营灵气石/14混元灵魂石 在旧系统中没有部位
- 测试号 zbb.ini 实际穿戴了旧部位 1-11 共11件，即角色当前可穿 11 个部位
