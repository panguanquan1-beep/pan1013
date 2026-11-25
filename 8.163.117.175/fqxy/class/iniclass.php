<?php
/**
 * 历史 iniFile API 的数据库适配器。
 * 保留方法签名以兼容旧页面；所有内容只读写 hxxy.game_state_store，不访问 .ini 文件。
 */
require_once __DIR__ . '/../../lib/DbIniStore.php';
require_once __DIR__ . '/../../lib/PlayerStore.php';

if (!class_exists('iniFile', false)) {
class iniFile
{
    public $iniFilePath;
    public $iniFileHandle;
    public $store;

    public function __construct($iniFilePath)
    {
        $this->iniFilePath = $iniFilePath;
        $this->store = PlayerStore::match($iniFilePath);
        $this->iniFileHandle = $this->store
            ? PlayerStore::load($this->store[0], $this->store[1], $iniFilePath)
            : DbIniStore::load($iniFilePath);
        if (!is_array($this->iniFileHandle)) $this->iniFileHandle = array();
    }

    public function addCategory($category_name, array $item = array())
    {
        if (!isset($this->iniFileHandle[$category_name]) || !is_array($this->iniFileHandle[$category_name])) $this->iniFileHandle[$category_name] = array();
        foreach ($item as $key => $value) $this->iniFileHandle[$category_name][$key] = $value;
        return $this->save();
    }

    public function addItem($category_name, array $item)
    {
        return $this->updItem($category_name, $item);
    }

    public function getAll() { return $this->iniFileHandle; }

    public function getCategory($category_name)
    {
        return isset($this->iniFileHandle[$category_name]) && is_array($this->iniFileHandle[$category_name]) ? $this->iniFileHandle[$category_name] : array();
    }

    public function getItem($category_name, $item_name)
    {
        $items = $this->getCategory($category_name);
        if (is_array($item_name)) {
            $out = array();
            foreach ($item_name as $name) $out[$name] = isset($items[$name]) ? $items[$name] : null;
            return $out;
        }
        return isset($items[$item_name]) ? $items[$item_name] : null;
    }

    public function updItem($category_name, array $item)
    {
        if (!isset($this->iniFileHandle[$category_name]) || !is_array($this->iniFileHandle[$category_name])) $this->iniFileHandle[$category_name] = array();
        foreach ($item as $key => $value) $this->iniFileHandle[$category_name][$key] = $value;
        return $this->save();
    }

    public function delCategory($category_name)
    {
        unset($this->iniFileHandle[$category_name]);
        return $this->save();
    }

    public function delItem($category_name, $item_name)
    {
        if (isset($this->iniFileHandle[$category_name])) unset($this->iniFileHandle[$category_name][$item_name]);
        return $this->save();
    }

    public function save()
    {
        if ($this->store) return PlayerStore::save($this->store[0], $this->store[1], $this->iniFilePath, $this->iniFileHandle);
        return DbIniStore::save($this->iniFilePath, $this->iniFileHandle);
    }

    public function getInt($category_name, $item_name, $default = 0)
    {
        $v = $this->getItem($category_name, $item_name);
        return ($v === null || $v === '') ? (int)$default : (int)$v;
    }

    public function getString($category_name, $item_name, $default = '')
    {
        $v = $this->getItem($category_name, $item_name);
        return $v === null ? (string)$default : (string)$v;
    }
}
}
