<?php
class ModuleCms_Model_Mapper_ModuleCms extends Standard_ModelMapper{
	protected $_dbTableClass = "ModuleCms_Model_DbTable_ModuleCms";
	
	public function getNextOrder($parent_id) {
		$select = $this->getDbTable()->select(false)
		->from("module_cms",array("max_order" => "max(`order`)"))
		->group("parent_id")
		->having("parent_id=".$parent_id);
	
		$row = $this->getDbTable()->fetchAll($select);
		return isset($row[0]) && isset($row[0]["max_order"])? $row[0]["max_order"] : 0;
	}
}