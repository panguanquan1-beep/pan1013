<?php
// 全局配置（禁止 Web 访问：见 .htaccess / docs/nginx_baota.conf）
return array(
    'db' => array('host' => '127.0.0.1', 'user' => 'root', 'pass' => 'pann1013', 'port' => 3306,
                  'game_db' => 'hxxy', 'user_db' => 'xxjyuser', 'charset' => 'utf8mb4'),
    // GM 备用密钥：GM 页面可用 ?gmkey=此值 临时登录（请部署时修改，留空则禁用）
    'gm_secret' => '7c982521a58e0870d352abfebe3ed873',
);
