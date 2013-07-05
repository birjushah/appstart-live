<?php
class Parking_Model_Mapper_ModuleParkingSource extends Standard_ModelMapper {
	protected $_dbTableClass = "Parking_Model_DbTable_ModuleParkingSource";
	
	public function getNextOrder() {
		$select = $this->getDbTable()->select(false)
						->from("module_parking_source",array("max_order" => "max(`order`)"));
	
		$row = $this->getDbTable()->fetchAll($select);
		return isset($row[0]) && isset($row[0]["max_order"])? $row[0]["max_order"] : 0;
	}
}