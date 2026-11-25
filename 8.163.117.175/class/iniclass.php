<?php
/** 根目录旧入口的数据库版 iniFile 兼容适配器。 */
require_once __DIR__ . '/../lib/DbIniStore.php';
require_once __DIR__ . '/../lib/PlayerStore.php';

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
            $this->iniFileHandle = $this->store ? PlayerStore::load($this->store[0], $this->store[1], $iniFilePath) : DbIniStore::load($iniFilePath);
            if (!is_array($this->iniFileHandle)) $this->iniFileHandle = array();
        }
        public function addCategory($name, array $item = array()) { if (!isset($this->iniFileHandle[$name]) || !is_array($this->iniFileHandle[$name])) $this->iniFileHandle[$name] = array(); foreach ($item as $k=>$v) $this->iniFileHandle[$name][$k]=$v; return $this->save(); }
        public function addItem($name, array $item) { return $this->updItem($name, $item); }
        public function getAll() { return $this->iniFileHandle; }
        public function getCategory($name) { return isset($this->iniFileHandle[$name]) && is_array($this->iniFileHandle[$name]) ? $this->iniFileHandle[$name] : array(); }
        public function getItem($name, $key) { $a=$this->getCategory($name); if (is_array($key)) { $o=array(); foreach($key as $k) $o[$k]=isset($a[$k])?$a[$k]:null; return $o; } return isset($a[$key])?$a[$key]:null; }
        public function updItem($name, array $item) { if (!isset($this->iniFileHandle[$name]) || !is_array($this->iniFileHandle[$name])) $this->iniFileHandle[$name]=array(); foreach($item as $k=>$v) $this->iniFileHandle[$name][$k]=$v; return $this->save(); }
        public function delCategory($name) { unset($this->iniFileHandle[$name]); return $this->save(); }
        public function delItem($name, $key) { if(isset($this->iniFileHandle[$name])) unset($this->iniFileHandle[$name][$key]); return $this->save(); }
        public function save() { return $this->store ? PlayerStore::save($this->store[0],$this->store[1],$this->iniFilePath,$this->iniFileHandle) : DbIniStore::save($this->iniFilePath,$this->iniFileHandle); }
        public function getInt($name,$key,$default=0) { $v=$this->getItem($name,$key); return ($v===null || $v==='')?(int)$default:(int)$v; }
        public function getString($name,$key,$default='') { $v=$this->getItem($name,$key); return $v===null?(string)$default:(string)$v; }
    }
}
