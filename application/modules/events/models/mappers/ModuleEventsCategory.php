<?php
class Events_Model_Mapper_ModuleEventsCategory extends Standard_ModelMapper {
	protected $_dbTableClass = "Events_Model_DbTable_ModuleEventsCategory";
	
	public function getNextOrder($parent_id,$customer_id) {
		$select = $this->getDbTable()->select(false)
						->from("module_events_category",array("max_order" => "max(`order`)"))
						->group("parent_id")
						->having("parent_id=".$parent_id)
						->where("customer_id=".$customer_id);
		
		$row = $this->getDbTable()->fetchAll($select);
		return isset($row[0]) && isset($row[0]["max_order"])? $row[0]["max_order"] : 0;
	}
}