# 全局基础设施层优化说明（Phase A）

## 目标
在**不改变任何 UI 排版、不改变核心玩法与数值**的前提下，清理并加固最底层的公共代码：
- 玩家操作锁（zsini / jsini 系列）
- Ini 文件读写类（iniclass）
- 可选的命令链接辅助函数

## 已完成文件

```
fqxy/
├── ini/
│   ├── zsini.php      # 主锁获取（改进版）
│   ├── jsini.php      # 主锁释放
│   ├── jszsini.php
│   ├── ozsini.php     # 第二把锁
│   ├── ojsini.php
│   └── jjsini.php
├── class/
│   └── iniclass.php   # Ini 操作类（改进版）
└── core/
    ├── helpers.php    # 可选：cmd_link / cmd_a 等
    └── README_基础设施优化.md
```

根目录 `class/iniclass.php` 也已同步更新。

## 兼容性保证

1. **所有旧页面无需修改即可继续运行**。
   - `$zsspd`、`$fp`、`$ininalock` 等变量名、含义、赋值时机完全保持一致。
   - `iniFile` 的所有公共方法签名与旧版相同。

2. 改进点（对业务透明）：
   - 锁：去掉重复 `flock(LOCK_EX)`、统一超时逻辑、目录自动创建、资源检查更严谨。
   - Ini：空文件/损坏文件处理更温和，写入时加文件锁，减少并发写坏概率；注释与编码清理。
   - 新增可选辅助函数，**只有主动 include helpers.php 才会生效**。

## 部署步骤

```bash
# 1. 备份
cp -a fqxy/ini fqxy/ini.bak.$(date +%Y%m%d)
cp fqxy/class/iniclass.php fqxy/class/iniclass.php.bak
cp class/iniclass.php class/iniclass.php.bak   # 如果根目录也有

# 2. 覆盖
# 把本次提供的 ini/*.php、class/iniclass.php、core/ 整个目录复制进去即可
```

## 后续可选使用方式（新写或逐步重构的页面）

```php
// 在模板顶部
include_once("./core/helpers.php");

// 以前：
$cmid=$cmid+1;
$cdid[]=$cmid;
$clj[]=258;
$npc[]=0;
echo "<a href='xy.php?uid=$wjid&&cmd=$cmid&&sid=$a1'><font color=blue>我要存款</font></a>"."<br>";

// 现在可以写成：
echo cmd_a('我要存款', 258) . "<br>";
// 或
$id = cmd_link(258);
echo "<a href='xy.php?uid=$wjid&cmd=$id&sid=$a1'><font color=blue>我要存款</font></a><br>";
```

旧写法继续有效，互不影响。

## 注意事项

1. 锁文件路径仍使用 `./ache/$wjid/`，与旧逻辑一致。
2. 如果正式环境发现某些极端并发场景下锁行为有差异，可随时把对应文件从 `.bak` 还原。
3. `iniclass` 构造函数现在对「文件不存在」不再直接 `die`（改为空数组），绝大多数旧代码在 include `xxx_ini.php` 时都会先创建文件，因此风险极低。若你观察到异常，告诉我即可再加严格模式开关。

## 下一步建议（Phase B）

公共基础设施稳定后，可继续做：
- 入口 `xy.php` 与 `xy0x.php` 路由整理
- 模板公共头部/尾部抽取（封号检测、VIP 检查等重复逻辑）
- 高频业务页面（背包、装备、银两等）去重

有问题随时说。
