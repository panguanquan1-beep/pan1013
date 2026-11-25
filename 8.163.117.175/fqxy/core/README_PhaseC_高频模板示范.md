# Phase C：高频模板去重示范

## 目标
在**不改变 UI 排版与核心玩法**的前提下，示范如何用 Phase A/B 提供的辅助函数重构典型页面，并抽取可复用的公共头部。

## 本阶段改动

### 1. 钱庄三件套（完整示范）
| 文件 | 说明 |
|------|------|
| `template/xy257.php` | 钱庄主页（用 `cmd_a` + `page_lock`） |
| `template/xy258.php` | 存款页（逻辑整理，文案/上限不变） |
| `template/xy259.php` | 取款页（同上） |

原文件已备份为 `*.php.bak`。

### 2. 状态页
| 文件 | 说明 |
|------|------|
| `template/xy011.php` | 状态页链接全部改为 `cmd_a`，结构更清晰 |

### 3. 新增公共组件
| 文件 | 说明 |
|------|------|
| `core/bag_header.php` | 背包类页面公共头部（锁 + zt/yl/bbrl + 分页 id 保存） |
| `core/README_PhaseC_高频模板示范.md` | 本说明 |

## 对比示例（钱庄主页）

**旧写法：**
```php
$cmid=$cmid+1;
$cdid[]=$cmid;
$clj[]=258;
$npc[]=0;
echo "<a href='xy.php?uid=$wjid&&cmd=$cmid&&sid=$a1'><font color=blue>我要存款</font></a>"."<br>";
```

**新写法：**
```php
echo cmd_a("我要存款", 258) . "<br>";
```

生成的 HTML 与旧版一致（含 `&&` 写法）。

## 部署

```bash
# 备份（建议）
cp template/xy257.php template/xy257.php.bak2
# ... 同理 258/259/011

# 覆盖本包中的 template/ 与 core/ 文件即可
```

重点测试：
1. 钱庄存/取款流程与提示文案
2. 状态页各链接是否跳到正确页面
3. 银两上下限、错误提示是否与以前相同

## 如何继续推广

背包系列（xy027～xy035 等）可改为：

```php
include("./core/bag_header.php");
if ($zsspd == 1) {
    // 原有列表逻辑...
    include("./ini/jsini.php");
}
```

或进一步使用 `page_lock()` / `cmd_a()`。

旧页面无需立刻全部改完，可按「改一个测一个」的节奏推进。

## 回滚

对应 `.bak` 文件还原即可。
