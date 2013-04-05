<?php
class Music_Model_Mapper_ModuleMusic extends Standard_ModelMapper {
	protected $_dbTableClass = "Music_Model_DbTable_ModuleMusic";
	
	public function getNextOrder($customer_id) {
		$select = $this->getDbTable()->select(false)
						->from("module_music",array("max_order" => "max(`order`)"))
						->group("customer_id")
						->having("customer_id=".$customer_id);
		
		$row = $this->getDbTable()->fetchAll($select);
		return isset($row[0]) && isset($row[0]["max_order"])? $row[0]["max_order"] : 0;
	}
}