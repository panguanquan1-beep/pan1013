# 按参考爬取重建地图（022）

- 生成：`python3 tools/maprebuild/build.py`（读 reference/world_map.json + 当前库 TSV）→ `sql_schema/022_map_rebuild_data.sql`；结构变更 `022_map_rebuild_schema.sql`。
- 渲染路径：xy.php → map/mapid.php → map_loader.php → MapStore(DB) → MapRenderer。`map/xsc.php` 等旧文件只在该区不在 DB 时回退，不再使用。
- 东海渔村：玩家出生点 dtx=0(xsc) 0,0=村长家。参考渔村优先映射到 0 区（别名：渔村广场→广场，码头（去长安）→码头），未匹配的渔村场景追加在 0 区 dty≥34。
- 映射：同名唯一+邻居一致 → 经已映射邻居出口传播 → 其余新建区 dtx≥300（按连通块）。重建坐标记录在表 `map_rebuilt`。
- 统计：{"npc_existing": 20, "npc_skipped_in_quest_file": 17, "npc_behaviour_reused": 7, "npc_new": 320, "monster_existing": 73, "monster_new": 19, "exit_dead": 71, "exit_kept_old_target": 1, "ref_scenes": 516, "mapped_to_existing": 274, "new_scenes": 302, "new_zones": 40}
- 名字重复无法确定、已新建（疑似合并，未猜测）：25 个：官道(危)、厢房、小路(危)、练功房(危)、后院(危)、南海之滨(危)、海岸(危)、内堂(危)、山门(危)、山洞(危)、厢房(危)、街道、偏厅、雅室、甬道(危)、甬道、饮酒厅(危)、甬道(危)、山洞(危)、村口、山门、练武场(危)、练武场(危)、演武厅(危)、码头(危)
- 爬虫标记 suspect_merges：7 条（见 result.json）。
- 新怪物 id≥90000 为占位属性（复制约 10 级怪，talk=（占位属性））；对话含 [攻击X] 的实体按怪处理。
- 新 NPC：npc_info id≥30000，对话为参考 tdialog 文本；同名 NPC 保留原行为。参考页 NPC 链接无颜色。
- 旧场景中未出现在参考里的（如 0 区 茅草屋）保留数据但不再连通。
