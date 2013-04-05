<?php
class HomeWallpaper_Model_Mapper_HomeWallpaper extends Standard_ModelMapper {
	protected $_dbTableClass = "HomeWallpaper_Model_DbTable_HomeWallpaper";
	
	public function getNextOrder($customer_id) {
		$select = $this->getDbTable()->select(false)
		->from("module_home_wallpaper",array("max_order" => "max(`order`)"))
		->group("customer_id")
		->having("customer_id=".$customer_id);
	
		$row = $this->getDbTable()->fetchAll($select);
		return isset($row[0]) && isset($row[0]["max_order"])? $row[0]["max_order"] : 0;
	}
}