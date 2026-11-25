# Phase B：入口路由 + 公共头部抽取

## 目标
在**不改变 UI 与核心玩法**的前提下：
1. 消除 xy01.php～xy07.php 里数千行重复的 if-elseif
2. 抽出封号检测等公共逻辑，减少模板重复
3. 为后续模板重构提供干净的入口

## 新增/修改文件

```
fqxy/
├── xy.php                    # 已改为直接走 core/router.php（原文件备份为 xy.php.orig）
├── xy01.php ~ xy07.php       # 薄兼容层（只 include router），旧逻辑可随时还原
├── core/
│   ├── router.php            # 统一页面路由（根据 $cmdd 自动 include template）
│   ├── check_ban.php         # 封号检测（从 xy002 等抽取）
│   ├── page_bootstrap.php    # 可选：page_lock() / page_unlock() / page_lock_and_check_ban()
│   ├── helpers.php           # Phase A 已有
│   └── README_PhaseB_路由与公共头.md
```

## 行为保证

- 所有 `$cmdd` → `template/xyXXX.php` 的映射关系与重构前完全一致
- 找不到模板时仍静默忽略（与旧空 else 一致）
- 封号检测变量（`$fhbl`、`$jyts1`、`$jyts`）与旧代码一致
- xy.php 的安全校验、超时、特征码、防刷等逻辑一字未改

## 部署步骤

```bash
# 1. 备份
cp xy.php xy.php.bak
cp xy01.php xy01.php.bak   # 同理备份 xy02~07
cp -a core core.bak.$(date +%Y%m%d)  # 如果已有 Phase A

# 2. 覆盖
# 解压本包中的 xy.php、xy0x.php、core/ 即可

# 3. 简单验证
# - 登录后进入游戏主页
# - 上下左右移动
# - 打开背包 / 状态 / 钱庄
# - 被封号账号是否仍正确提示
```

## 可选升级用法（新写或逐步改的模板）

### 1. 使用封号检测
```php
include("./core/check_ban.php");
if ($fhbl != 2) {
    // 正常业务
} else {
    echo $jyts1 . "<br>" . $jyts;
}
```

### 2. 使用页面锁辅助
```php
include_once("./core/page_bootstrap.php");
if (page_lock()) {
    // 业务...
    page_unlock();
}
```

### 3. 锁 + 封号一步完成
```php
include_once("./core/page_bootstrap.php");
if (page_lock_and_check_ban()) {
    // 业务...
    page_unlock();
} else {
    // 已封号或抢锁失败
}
```

旧模板继续用原来的 zsini / 手写封号代码也完全没问题。

## 回滚

- 把 `xy.php.orig` 改回 `xy.php`
- 把备份的 xy01~xy07 还原
- 即可完全回到重构前状态

## 下一步建议（Phase C）

- 高频模板去重（背包、装备、银两、状态等）
- 把更多模板改为使用 `cmd_a()` / `page_lock()` 等新辅助
- 视情况把「最后页面白名单」等硬编码长列表也数据化

有问题随时反馈。
