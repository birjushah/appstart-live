<?php
class Parking_Model_Mapper_ModuleParking extends Standard_ModelMapper {
	protected $_dbTableClass = "Parking_Model_DbTable_ModuleParking";
	
	public function getNextOrder($customer_id) {
		$select = $this->getDbTable()->select(false)
						->from("module_parking",array("max_order" => "max(`order`)"))
						->group("customer_id")
						->having("customer_id=".$customer_id);
		
		$row = $this->getDbTable()->fetchAll($select);
		return isset($row[0]) && isset($row[0]["max_order"])? $row[0]["max_order"] : 0;
	}
}