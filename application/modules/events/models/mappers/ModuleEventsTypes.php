<?php
class Events_Model_Mapper_ModuleEventsTypes extends Standard_ModelMapper {
	protected $_dbTableClass = "Events_Model_DbTable_ModuleEventsTypes";
	
	public function getNextOrder($customer_id) {
		$select = $this->getDbTable()->select(false)
						->from("module_events_types",array("max_order" => "max(`order`)"))
						->where("customer_id=".$customer_id);
		
		$row = $this->getDbTable()->fetchAll($select);
		return isset($row[0]) && isset($row[0]["max_order"])? $row[0]["max_order"] : 0;
	}
}