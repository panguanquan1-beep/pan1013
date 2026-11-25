<?php
// 旧日常/主线任务已停用（代码存于 legacy/），统一使用新任务系统（DB：quest_def / player_quest）
include_once(__DIR__ . "/../wp/item_view.php");
if (empty($__quest_moved_shown)) {
    $__quest_moved_shown = 1;
    echo "任务已统一到【任务】系统：" . iv_link('查看可接任务', 750, 'weijie') . "&nbsp;" . iv_link('我的任务', 750, 'renwu') . "<br/>";
}
