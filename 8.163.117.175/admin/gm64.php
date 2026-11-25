<?php
/**
 * 房屋/家具 GM 管理页面
 * 必须通过 fqxy/gm.php 且通过 gm_auth 校验后访问。
 * PHP 5.6 兼容。
 */
if (!defined('XY_GM_ROUTER_AUTH') || XY_GM_ROUTER_AUTH !== true) {
    header('HTTP/1.1 403 Forbidden');
    exit('请从GM管理平台进入。');
}

require_once __DIR__ . '/../../lib/HouseDb.php';

function housegm_h($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
function housegm_redirect_url()
{
    $url = 'gm.php?gid=64';
    if (isset($_GET['wjid']) && isset($_GET['pass'])) {
        $url = 'gm.php?wjid=' . rawurlencode((string)$_GET['wjid']) . '&pass=' . rawurlencode((string)$_GET['pass']) . '&gid=64';
    }
    return $url;
}
function housegm_exec($db, $sql, $types, $params)
{
    $st = $db->prepare($sql);
    if (!$st) return false;
    if ($types !== '') {
        $bind = array($types);
        foreach ($params as $k => $v) $bind[] = &$params[$k];
        call_user_func_array(array($st, 'bind_param'), $bind);
    }
    $ok = $st->execute();
    if (!$ok) error_log('House GM SQL error: ' . $st->error);
    $st->close();
    return $ok;
}
function housegm_one($db, $sql, $types, $params)
{
    $st = $db->prepare($sql);
    if (!$st) return null;
    if ($types !== '') {
        $bind = array($types);
        foreach ($params as $k => $v) $bind[] = &$params[$k];
        call_user_func_array(array($st, 'bind_param'), $bind);
    }
    if (!$st->execute()) { $st->close(); return null; }
    $res = $st->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $st->close();
    return $row ? $row : null;
}
function housegm_rows($db, $sql)
{
    $out = array();
    $res = $db->query($sql);
    if ($res) {
        while ($row = $res->fetch_assoc()) $out[] = $row;
        $res->free();
    }
    return $out;
}
function housegm_token()
{
    if (!isset($_SESSION['house_gm_token']) || $_SESSION['house_gm_token'] === '') {
        if (function_exists('openssl_random_pseudo_bytes')) {
            $_SESSION['house_gm_token'] = bin2hex(openssl_random_pseudo_bytes(16));
        } else {
            $_SESSION['house_gm_token'] = md5(uniqid(mt_rand(), true));
        }
    }
    return $_SESSION['house_gm_token'];
}
function housegm_post_token_ok()
{
    if (!isset($_POST['house_gm_token']) || !isset($_SESSION['house_gm_token'])) return false;
    if (function_exists('hash_equals')) return hash_equals($_SESSION['house_gm_token'], (string)$_POST['house_gm_token']);
    return $_SESSION['house_gm_token'] === (string)$_POST['house_gm_token'];
}
function housegm_num($value, $default, $min, $max)
{
    if (!is_numeric($value)) return $default;
    $n = 0 + $value;
    if ($n < $min) $n = $min;
    if ($n > $max) $n = $max;
    return $n;
}
function housegm_code($value)
{
    return substr(preg_replace('/[^a-zA-Z0-9_-]/', '', trim((string)$value)), 0, 50);
}
function housegm_text($value, $max)
{
    $value = trim((string)$value);
    if (function_exists('mb_substr')) return mb_substr($value, 0, $max, 'UTF-8');
    return substr($value, 0, $max);
}

$houseGmDb = ItemDb::conn();
$houseGmDb->set_charset('utf8mb4');
if (!HouseDb::installSchema()) {
    echo '<p style="color:red">房屋系统数据表创建失败。请检查 config/app.php 数据库连接及 PHP 错误日志。</p>';
    return;
}
// 已经由旧版清理脚本建过表的，也补齐家具价格字段。
$col = $houseGmDb->query("SHOW COLUMNS FROM xy_furniture_def LIKE 'base_price'");
if ($col && $col->num_rows == 0) $houseGmDb->query("ALTER TABLE xy_furniture_def ADD base_price DECIMAL(18,2) NOT NULL DEFAULT 0 AFTER max_level");
if ($col) $col->free();
$col = $houseGmDb->query("SHOW COLUMNS FROM xy_furniture_def LIKE 'currency'");
if ($col && $col->num_rows == 0) $houseGmDb->query("ALTER TABLE xy_furniture_def ADD currency VARCHAR(20) NOT NULL DEFAULT 'silver' AFTER base_price");
if ($col) $col->free();

// 统一旧分类：历史遗留的仙官宅、魔官宅等类型全部并入“官宅”。
// 房屋名称由 GM 自行填写，本页面不会自动追加仙/魔等后缀。
$houseGmDb->query("UPDATE xy_house_def SET house_type='official'
    WHERE house_type LIKE '%官宅%'
       OR house_type LIKE '%仙%'
       OR house_type LIKE '%魔%'
       OR LOWER(house_type) IN ('xian','mo','immortal','demon','xian_official','mo_official','immortal_official','demon_official','xian_house','mo_house')");

$houseGmMessage = '';
$houseGmError = '';
// 房屋分类仅允许以下三种，官宅统一使用 official。
$houseGmTypes = array('residence' => '普通住宅', 'mansion' => '豪宅', 'official' => '官宅');
$houseGmAttrLabels = array(
    'hp' => '生命', 'mp' => '法力', 'atk' => '攻击', 'def' => '防御', 'matk' => '魔攻',
    'ind_atk' => '独立攻击', 'ind_def' => '独立防御',
    'atk_ice' => '冰攻击', 'atk_fire' => '火攻击', 'atk_thunder' => '雷攻击',
    'def_ice' => '冰防御', 'def_fire' => '火防御', 'def_thunder' => '雷防御',
    'speed' => '速度', 'crit_rate' => '暴击率', 'crit_dmg' => '暴击伤害',
    'dodge_rate' => '闪避率', 'hit_rate' => '命中率', 'crit_resist' => '抗暴率', 'lifesteal' => '吸血'
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!housegm_post_token_ok()) {
        $houseGmError = '页面验证已失效，请刷新后重新操作。';
    } else {
        $action = isset($_POST['house_action']) ? $_POST['house_action'] : '';
        if ($action === 'save_house') {
            $id = (int)(isset($_POST['house_id']) ? $_POST['house_id'] : 0);
            $code = housegm_code(isset($_POST['code']) ? $_POST['code'] : '');
            $name = housegm_text(isset($_POST['name']) ? $_POST['name'] : '', 100);
            $type = isset($_POST['house_type']) && isset($houseGmTypes[$_POST['house_type']]) ? $_POST['house_type'] : 'residence';
            $level = (int)housegm_num(isset($_POST['level_req']) ? $_POST['level_req'] : 1, 1, 1, 9999);
            $rooms = (int)housegm_num(isset($_POST['max_rooms']) ? $_POST['max_rooms'] : 1, 1, 1, 999);
            $capacity = (int)housegm_num(isset($_POST['max_furniture']) ? $_POST['max_furniture'] : 0, 0, 0, 99999);
            $price = (float)housegm_num(isset($_POST['base_price']) ? $_POST['base_price'] : 0, 0, 0, 999999999999);
            $currency = housegm_code(isset($_POST['currency']) ? $_POST['currency'] : 'silver');
            $enabled = !empty($_POST['enabled']) ? 1 : 0;
            $sort = (int)housegm_num(isset($_POST['sort']) ? $_POST['sort'] : 0, 0, -999999, 999999);
            if ($code === '' || $name === '') {
                $houseGmError = '住宅编号和住宅名称不能为空；编号只能使用英文字母、数字、下划线或横线。';
            } elseif ($currency === '') {
                $houseGmError = '货币类型不能为空。';
            } else {
                if ($id > 0) {
                    $ok = housegm_exec($houseGmDb,
                        'UPDATE xy_house_def SET code=?,name=?,house_type=?,level_req=?,max_rooms=?,max_furniture=?,base_price=?,currency=?,enabled=?,sort=? WHERE id=?',
                        'sssiiidsiii', array($code, $name, $type, $level, $rooms, $capacity, $price, $currency, $enabled, $sort, $id));
                } else {
                    $ok = housegm_exec($houseGmDb,
                        'INSERT INTO xy_house_def (code,name,house_type,level_req,max_rooms,max_furniture,base_price,currency,enabled,sort) VALUES (?,?,?,?,?,?,?,?,?,?)',
                        'sssiiidsii', array($code, $name, $type, $level, $rooms, $capacity, $price, $currency, $enabled, $sort));
                }
                if ($ok) $houseGmMessage = $id > 0 ? '住宅资料已更新。' : '住宅已添加。';
                else $houseGmError = '住宅保存失败。请检查编号是否与已有住宅重复，并查看 PHP 错误日志。';
            }
        } elseif ($action === 'save_furniture') {
            $id = (int)(isset($_POST['furniture_id']) ? $_POST['furniture_id'] : 0);
            $code = housegm_code(isset($_POST['f_code']) ? $_POST['f_code'] : '');
            $name = housegm_text(isset($_POST['f_name']) ? $_POST['f_name'] : '', 100);
            $category = housegm_code(isset($_POST['category']) ? $_POST['category'] : 'decoration');
            $maxLevel = (int)housegm_num(isset($_POST['max_level']) ? $_POST['max_level'] : 1, 1, 1, 999);
            $price = (float)housegm_num(isset($_POST['f_price']) ? $_POST['f_price'] : 0, 0, 0, 999999999999);
            $currency = housegm_code(isset($_POST['f_currency']) ? $_POST['f_currency'] : 'silver');
            $enabled = !empty($_POST['f_enabled']) ? 1 : 0;
            $sort = (int)housegm_num(isset($_POST['f_sort']) ? $_POST['f_sort'] : 0, 0, -999999, 999999);
            if ($code === '' || $name === '') {
                $houseGmError = '家具编号和家具名称不能为空。';
            } elseif ($category === '' || $currency === '') {
                $houseGmError = '家具类别和货币类型不能为空。';
            } else {
                if ($id > 0) {
                    $ok = housegm_exec($houseGmDb,
                        'UPDATE xy_furniture_def SET code=?,name=?,category=?,max_level=?,base_price=?,currency=?,enabled=?,sort=? WHERE id=?',
                        'sssidsiii', array($code, $name, $category, $maxLevel, $price, $currency, $enabled, $sort, $id));
                } else {
                    $ok = housegm_exec($houseGmDb,
                        'INSERT INTO xy_furniture_def (code,name,category,max_level,base_price,currency,enabled,sort) VALUES (?,?,?,?,?,?,?,?)',
                        'sssidsii', array($code, $name, $category, $maxLevel, $price, $currency, $enabled, $sort));
                }
                if ($ok) $houseGmMessage = $id > 0 ? '家具资料已更新。' : '家具已添加。';
                else $houseGmError = '家具保存失败。请检查家具编号是否重复，并查看 PHP 错误日志。';
            }
        } elseif ($action === 'save_attrs') {
            $fid = (int)(isset($_POST['attr_furniture_id']) ? $_POST['attr_furniture_id'] : 0);
            $level = (int)housegm_num(isset($_POST['attr_level']) ? $_POST['attr_level'] : 1, 1, 1, 999);
            $f = housegm_one($houseGmDb, 'SELECT id,max_level FROM xy_furniture_def WHERE id=?', 'i', array($fid));
            if (!$f || $level > (int)$f['max_level']) {
                $houseGmError = '家具不存在，或属性等级超过该家具的最高等级。';
            } else {
                $houseGmDb->begin_transaction();
                $ok = housegm_exec($houseGmDb, 'DELETE FROM xy_furniture_attr WHERE furniture_id=? AND level=?', 'ii', array($fid, $level));
                if ($ok) {
                    foreach ($houseGmAttrLabels as $key => $label) {
                        $raw = isset($_POST['attrs'][$key]) ? trim((string)$_POST['attrs'][$key]) : '0';
                        if ($raw === '') $raw = '0';
                        if (!is_numeric($raw)) { $ok = false; break; }
                        $value = (float)housegm_num($raw, 0, -999999999, 999999999);
                        if ($value != 0) {
                            if (!housegm_exec($houseGmDb,
                                'INSERT INTO xy_furniture_attr (furniture_id,level,attr_key,attr_value) VALUES (?,?,?,?)',
                                'iisd', array($fid, $level, $key, $value))) { $ok = false; break; }
                        }
                    }
                }
                if ($ok) { $houseGmDb->commit(); $houseGmMessage = '家具第 ' . $level . ' 级属性已保存。'; }
                else { $houseGmDb->rollback(); $houseGmError = '家具属性保存失败，已回滚本次修改。'; }
            }
        } elseif ($action === 'toggle_house') {
            $id = (int)(isset($_POST['item_id']) ? $_POST['item_id'] : 0);
            $enabled = (int)(isset($_POST['enabled']) ? $_POST['enabled'] : 0) ? 1 : 0;
            $ok = housegm_exec($houseGmDb, 'UPDATE xy_house_def SET enabled=? WHERE id=?', 'ii', array($enabled, $id));
            if ($ok) $houseGmMessage = $enabled ? '住宅已启用。' : '住宅已停用。'; else $houseGmError = '住宅状态修改失败。';
        } elseif ($action === 'toggle_furniture') {
            $id = (int)(isset($_POST['item_id']) ? $_POST['item_id'] : 0);
            $enabled = (int)(isset($_POST['enabled']) ? $_POST['enabled'] : 0) ? 1 : 0;
            $ok = housegm_exec($houseGmDb, 'UPDATE xy_furniture_def SET enabled=? WHERE id=?', 'ii', array($enabled, $id));
            if ($ok) $houseGmMessage = $enabled ? '家具已启用。' : '家具已停用。'; else $houseGmError = '家具状态修改失败。';
        } elseif ($action === 'delete_house') {
            $id = (int)(isset($_POST['item_id']) ? $_POST['item_id'] : 0);
            $used = housegm_one($houseGmDb, 'SELECT COUNT(*) AS n FROM xy_player_house WHERE house_id=?', 'i', array($id));
            if ($used && (int)$used['n'] > 0) $houseGmError = '该住宅已有玩家持有，不能删除。请先停用；不会自动删除玩家数据。';
            else {
                $ok = housegm_exec($houseGmDb, 'DELETE FROM xy_house_def WHERE id=?', 'i', array($id));
                if ($ok) $houseGmMessage = '住宅定义已删除。'; else $houseGmError = '住宅删除失败。';
            }
        } elseif ($action === 'delete_furniture') {
            $id = (int)(isset($_POST['item_id']) ? $_POST['item_id'] : 0);
            $a = housegm_one($houseGmDb, 'SELECT COUNT(*) AS n FROM xy_player_furniture WHERE furniture_id=?', 'i', array($id));
            $b = housegm_one($houseGmDb, 'SELECT COUNT(*) AS n FROM xy_house_furniture WHERE furniture_id=?', 'i', array($id));
            if (($a && (int)$a['n'] > 0) || ($b && (int)$b['n'] > 0)) $houseGmError = '该家具已有玩家库存或摆放记录，不能删除。请先停用；不会自动删除玩家数据。';
            else {
                $houseGmDb->begin_transaction();
                $ok = housegm_exec($houseGmDb, 'DELETE FROM xy_furniture_attr WHERE furniture_id=?', 'i', array($id));
                if ($ok) $ok = housegm_exec($houseGmDb, 'DELETE FROM xy_furniture_def WHERE id=?', 'i', array($id));
                if ($ok) { $houseGmDb->commit(); $houseGmMessage = '家具定义及其属性已删除。'; }
                else { $houseGmDb->rollback(); $houseGmError = '家具删除失败，已回滚。'; }
            }
        } else {
            $houseGmError = '未知的管理操作。';
        }
        // 每次提交后更换令牌，防止浏览器刷新重复提交。
        unset($_SESSION['house_gm_token']);
    }
}

$editHouse = null;
if (isset($_GET['edit_house'])) $editHouse = housegm_one($houseGmDb, 'SELECT * FROM xy_house_def WHERE id=?', 'i', array((int)$_GET['edit_house']));
$editFurniture = null;
if (isset($_GET['edit_furniture'])) $editFurniture = housegm_one($houseGmDb, 'SELECT * FROM xy_furniture_def WHERE id=?', 'i', array((int)$_GET['edit_furniture']));
$attrLevel = (int)housegm_num(isset($_GET['attr_level']) ? $_GET['attr_level'] : 1, 1, 1, 999);
$attrValues = array();
if ($editFurniture) {
    $attrLevel = (int)housegm_num($attrLevel, 1, 1, (int)$editFurniture['max_level']);
    $attrRows = housegm_rows($houseGmDb, 'SELECT attr_key,attr_value FROM xy_furniture_attr WHERE furniture_id=' . (int)$editFurniture['id'] . ' AND level=' . $attrLevel);
    foreach ($attrRows as $ar) $attrValues[$ar['attr_key']] = $ar['attr_value'];
}
$houses = housegm_rows($houseGmDb, 'SELECT * FROM xy_house_def ORDER BY sort,id');
$furnitures = housegm_rows($houseGmDb, 'SELECT * FROM xy_furniture_def ORDER BY sort,id');
$houseGmSelf = housegm_redirect_url();
$houseGmToken = housegm_token();

// 管理界面
 echo '<style>
.house-gm{font:15px Arial,"Microsoft YaHei",sans-serif;color:#222;max-width:980px;margin:8px auto;line-height:1.55}
.house-gm h3{font-size:19px;margin:16px 0 8px;color:#8b1d1d}
.house-gm h4{font-size:16px;margin:12px 0 6px}
.house-gm .panel{border:1px solid #d8d8d8;border-radius:8px;padding:12px;margin:12px 0;background:#fff}
.house-gm .grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px}
.house-gm label{display:block;font-size:13px;color:#555;margin-bottom:3px}
.house-gm input,.house-gm select{box-sizing:border-box;width:100%;min-height:38px;padding:7px;border:1px solid #bbb;border-radius:5px;background:#fff;font-size:15px}
.house-gm button,.house-gm .linkbtn{display:inline-block;border:0;border-radius:5px;padding:8px 12px;background:#1769aa;color:#fff;text-decoration:none;font-size:14px;margin:3px 2px;cursor:pointer}
.house-gm .danger{background:#a52a2a}.house-gm .muted{color:#666;font-size:13px}.house-gm .ok{color:#087b38}.house-gm .err{color:#b00020}
.house-gm table{width:100%;border-collapse:collapse;font-size:13px;display:block;overflow-x:auto;white-space:nowrap}
.house-gm th,.house-gm td{border-bottom:1px solid #ddd;padding:7px;text-align:left}
.house-gm th{background:#f2f4f6}
.house-gm .attrs{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:7px}
.house-gm .actions{display:flex;flex-wrap:wrap;gap:3px;align-items:center}
@media(max-width:560px){.house-gm .grid,.house-gm .attrs{grid-template-columns:1fr}.house-gm .panel{padding:9px}}
</style>';
echo '<div class="house-gm">';
echo '<h3>房屋与家具管理</h3>';
echo '<p class="muted">此处管理住宅（普通住宅、豪宅、统一官宅）和家具定义。这里只登记模板，不会自动给玩家发放住宅或家具。新系统数据表已自动检查并补齐。</p>';
if ($houseGmMessage !== '') echo '<div class="panel ok">' . housegm_h($houseGmMessage) . '</div>';
if ($houseGmError !== '') echo '<div class="panel err">' . housegm_h($houseGmError) . '</div>';
echo '<div class="panel"><h4>数据概况</h4><p>住宅定义：' . count($houses) . ' 项；家具定义：' . count($furnitures) . ' 项。</p><p class="muted">启用后才允许后续游戏购买/使用。清理后的初始状态应为 0 项。</p><a class="linkbtn" href="' . housegm_h($houseGmSelf) . '">刷新管理页</a> <a class="linkbtn" href="gm.php?gid=1">返回GM首页</a></div>';

// 住宅编辑/新增表单
$h = $editHouse ? $editHouse : array('id'=>0,'code'=>'','name'=>'','house_type'=>'residence','level_req'=>1,'max_rooms'=>1,'max_furniture'=>0,'base_price'=>'0','currency'=>'silver','enabled'=>0,'sort'=>0);
echo '<div class="panel" id="house"><h3>' . ($editHouse ? '修改住宅定义' : '添加住宅') . '</h3>';
echo '<form method="post" action="' . housegm_h($houseGmSelf) . '"><input type="hidden" name="house_gm_token" value="' . housegm_h($houseGmToken) . '"><input type="hidden" name="house_action" value="save_house"><input type="hidden" name="house_id" value="' . (int)$h['id'] . '">';
echo '<div class="grid">';
echo '<div><label>住宅编号（英文/数字，不能重复）</label><input name="code" maxlength="50" value="' . housegm_h($h['code']) . '" placeholder="例如 purple_cloud_house" required></div>';
echo '<div><label>住宅名称</label><input name="name" maxlength="100" value="' . housegm_h($h['name']) . '" placeholder="例如紫云官宅" required></div>';
echo '<div><label>房屋分类</label><select name="house_type">';
foreach ($houseGmTypes as $k=>$v) echo '<option value="' . housegm_h($k) . '"' . ($h['house_type']===$k?' selected':'') . '>' . housegm_h($v) . '</option>';
echo '</select></div>';
echo '<div><label>购买最低等级</label><input type="number" name="level_req" min="1" value="' . (int)$h['level_req'] . '"></div>';
echo '<div><label>房间数量</label><input type="number" name="max_rooms" min="1" value="' . (int)$h['max_rooms'] . '"></div>';
echo '<div><label>家具容量</label><input type="number" name="max_furniture" min="0" value="' . (int)$h['max_furniture'] . '"></div>';
echo '<div><label>购买价格</label><input type="number" name="base_price" min="0" step="0.01" value="' . housegm_h($h['base_price']) . '"></div>';
echo '<div><label>货币代码（例如 silver）</label><input name="currency" maxlength="20" value="' . housegm_h($h['currency']) . '"></div>';
echo '<div><label>排序（越小越靠前）</label><input type="number" name="sort" value="' . (int)$h['sort'] . '"></div>';
echo '<div><label>状态</label><select name="enabled"><option value="0"' . ((int)$h['enabled']===0?' selected':'') . '>停用（建议新建时先停用）</option><option value="1"' . ((int)$h['enabled']===1?' selected':'') . '>启用</option></select></div>';
echo '</div><p><button type="submit">' . ($editHouse?'保存住宅修改':'添加住宅') . '</button>';
if ($editHouse) echo ' <a class="linkbtn" href="' . housegm_h($houseGmSelf) . '#house">取消编辑</a>';
echo '</p></form></div>';

// 住宅列表
echo '<div class="panel"><h3>住宅定义列表</h3>';
if (!$houses) echo '<p class="muted">目前没有住宅或官宅定义。</p>';
else {
    echo '<table><tr><th>ID</th><th>名称</th><th>房屋分类</th><th>等级</th><th>容量</th><th>价格</th><th>状态</th><th>操作</th></tr>';
    foreach ($houses as $row) {
        $typeText = isset($houseGmTypes[$row['house_type']]) ? $houseGmTypes[$row['house_type']] : $row['house_type'];
        echo '<tr><td>' . (int)$row['id'] . '</td><td>' . housegm_h($row['name']) . '<br><span class="muted">' . housegm_h($row['code']) . '</span></td><td>' . housegm_h($typeText) . '</td><td>' . (int)$row['level_req'] . '</td><td>' . (int)$row['max_furniture'] . '</td><td>' . housegm_h($row['base_price']) . ' ' . housegm_h($row['currency']) . '</td><td>' . ((int)$row['enabled']?'启用':'停用') . '</td><td><div class="actions">';
        echo '<a class="linkbtn" href="' . housegm_h($houseGmSelf) . '&edit_house=' . (int)$row['id'] . '#house">编辑</a>';
        echo '<form method="post" action="' . housegm_h($houseGmSelf) . '"><input type="hidden" name="house_gm_token" value="' . housegm_h($houseGmToken) . '"><input type="hidden" name="house_action" value="toggle_house"><input type="hidden" name="item_id" value="' . (int)$row['id'] . '"><input type="hidden" name="enabled" value="' . ((int)$row['enabled']?0:1) . '"><button type="submit">' . ((int)$row['enabled']?'停用':'启用') . '</button></form>';
        echo '<form method="post" action="' . housegm_h($houseGmSelf) . '" onsubmit="return confirm(\'确定删除这条住宅定义？已有玩家持有时系统会阻止删除。\')"><input type="hidden" name="house_gm_token" value="' . housegm_h($houseGmToken) . '"><input type="hidden" name="house_action" value="delete_house"><input type="hidden" name="item_id" value="' . (int)$row['id'] . '"><button class="danger" type="submit">删除</button></form>';
        echo '</div></td></tr>';
    }
    echo '</table>';
}
echo '</div>';

// 家具编辑/新增表单
$f = $editFurniture ? $editFurniture : array('id'=>0,'code'=>'','name'=>'','category'=>'decoration','max_level'=>1,'base_price'=>'0','currency'=>'silver','enabled'=>0,'sort'=>0);
echo '<div class="panel" id="furniture"><h3>' . ($editFurniture?'修改家具定义':'添加家具') . '</h3>';
echo '<form method="post" action="' . housegm_h($houseGmSelf) . '"><input type="hidden" name="house_gm_token" value="' . housegm_h($houseGmToken) . '"><input type="hidden" name="house_action" value="save_furniture"><input type="hidden" name="furniture_id" value="' . (int)$f['id'] . '">';
echo '<div class="grid">';
echo '<div><label>家具编号（英文/数字，不能重复）</label><input name="f_code" maxlength="50" value="' . housegm_h($f['code']) . '" placeholder="例如 rosewood_bed" required></div>';
echo '<div><label>家具名称</label><input name="f_name" maxlength="100" value="' . housegm_h($f['name']) . '" placeholder="例如紫檀木床" required></div>';
echo '<div><label>家具类别（自定义英文代码）</label><input name="category" maxlength="40" value="' . housegm_h($f['category']) . '" placeholder="bed / table / decoration"></div>';
echo '<div><label>最高等级</label><input type="number" name="max_level" min="1" value="' . (int)$f['max_level'] . '"></div>';
echo '<div><label>购买价格</label><input type="number" name="f_price" min="0" step="0.01" value="' . housegm_h($f['base_price']) . '"></div>';
echo '<div><label>货币代码</label><input name="f_currency" maxlength="20" value="' . housegm_h($f['currency']) . '"></div>';
echo '<div><label>排序</label><input type="number" name="f_sort" value="' . (int)$f['sort'] . '"></div>';
echo '<div><label>状态</label><select name="f_enabled"><option value="0"' . ((int)$f['enabled']===0?' selected':'') . '>停用（建议新建时先停用）</option><option value="1"' . ((int)$f['enabled']===1?' selected':'') . '>启用</option></select></div>';
echo '</div><p><button type="submit">' . ($editFurniture?'保存家具修改':'添加家具') . '</button>';
if ($editFurniture) echo ' <a class="linkbtn" href="' . housegm_h($houseGmSelf) . '#furniture">取消编辑</a>';
echo '</p></form></div>';

// 家具列表
echo '<div class="panel"><h3>家具定义列表</h3>';
if (!$furnitures) echo '<p class="muted">目前没有家具定义。</p>';
else {
    echo '<table><tr><th>ID</th><th>名称</th><th>类别</th><th>最高等级</th><th>价格</th><th>状态</th><th>操作</th></tr>';
    foreach ($furnitures as $row) {
        echo '<tr><td>' . (int)$row['id'] . '</td><td>' . housegm_h($row['name']) . '<br><span class="muted">' . housegm_h($row['code']) . '</span></td><td>' . housegm_h($row['category']) . '</td><td>' . (int)$row['max_level'] . '</td><td>' . housegm_h($row['base_price']) . ' ' . housegm_h($row['currency']) . '</td><td>' . ((int)$row['enabled']?'启用':'停用') . '</td><td><div class="actions">';
        echo '<a class="linkbtn" href="' . housegm_h($houseGmSelf) . '&edit_furniture=' . (int)$row['id'] . '#furniture">编辑/属性</a>';
        echo '<form method="post" action="' . housegm_h($houseGmSelf) . '"><input type="hidden" name="house_gm_token" value="' . housegm_h($houseGmToken) . '"><input type="hidden" name="house_action" value="toggle_furniture"><input type="hidden" name="item_id" value="' . (int)$row['id'] . '"><input type="hidden" name="enabled" value="' . ((int)$row['enabled']?0:1) . '"><button type="submit">' . ((int)$row['enabled']?'停用':'启用') . '</button></form>';
        echo '<form method="post" action="' . housegm_h($houseGmSelf) . '" onsubmit="return confirm(\'确定删除该家具及全部等级属性？已有库存或摆放记录时系统会阻止删除。\')"><input type="hidden" name="house_gm_token" value="' . housegm_h($houseGmToken) . '"><input type="hidden" name="house_action" value="delete_furniture"><input type="hidden" name="item_id" value="' . (int)$row['id'] . '"><button class="danger" type="submit">删除</button></form>';
        echo '</div></td></tr>';
    }
    echo '</table>';
}
echo '</div>';

// 家具分等级属性
echo '<div class="panel" id="attributes"><h3>家具属性加成设置</h3>';
if (!$editFurniture) {
    echo '<p class="muted">先在上方家具列表选择“编辑/属性”，再设置该家具各等级属性。</p>';
} else {
    echo '<p>当前家具：<strong>' . housegm_h($editFurniture['name']) . '</strong>；最高等级：' . (int)$editFurniture['max_level'] . '。</p>';
    echo '<form method="get" action="gm.php"><input type="hidden" name="gid" value="64">';
    if (isset($_GET['wjid'])) echo '<input type="hidden" name="wjid" value="' . housegm_h($_GET['wjid']) . '">';
    if (isset($_GET['pass'])) echo '<input type="hidden" name="pass" value="' . housegm_h($_GET['pass']) . '">';
    echo '<input type="hidden" name="edit_furniture" value="' . (int)$editFurniture['id'] . '"><label>选择要编辑的家具等级</label><select name="attr_level">';
    for ($lv=1; $lv<=(int)$editFurniture['max_level']; $lv++) echo '<option value="' . $lv . '"' . ($lv===$attrLevel?' selected':'') . '>第 ' . $lv . ' 级</option>';
    echo '</select><button type="submit">读取该等级属性</button></form>';
    echo '<form method="post" action="' . housegm_h($houseGmSelf) . '"><input type="hidden" name="house_gm_token" value="' . housegm_h($houseGmToken) . '"><input type="hidden" name="house_action" value="save_attrs"><input type="hidden" name="attr_furniture_id" value="' . (int)$editFurniture['id'] . '"><input type="hidden" name="attr_level" value="' . (int)$attrLevel . '"><p>填写数值；留空或填 0 表示该属性不加成。每一级独立保存。</p><div class="attrs">';
    foreach ($houseGmAttrLabels as $key=>$label) {
        $value = isset($attrValues[$key]) ? $attrValues[$key] : '0';
        echo '<div><label>' . housegm_h($label) . '（' . housegm_h($key) . '）</label><input type="number" step="0.01" name="attrs[' . housegm_h($key) . ']" value="' . housegm_h($value) . '"></div>';
    }
    echo '</div><p><button type="submit">保存第 ' . (int)$attrLevel . ' 级属性</button></p></form>';
}
echo '</div>';
echo '<p class="muted">安全说明：删除住宅/家具定义前会检查玩家持有、库存和摆放记录；存在引用时只允许停用，不会连带删除玩家资料。房屋与家具的玩家购买、持有、摆放功能仍需后续接入游戏流程。</p>';
echo '</div>';
?>
