<?php
class PushMessage_Model_Mapper_PushMessageCategory extends Standard_ModelMapper {
	protected $_dbTableClass = "PushMessage_Model_DbTable_PushMessageCategory";
	
	public function getNextOrder($parent_id,$customer_id) {
		$select = $this->getDbTable()->select(false)
						->from("module_push_message_category",array("max_order" => "max(`order`)"))
						->group("parent_id")
						->having("parent_id=".$parent_id)
						->where("customer_id=".$customer_id);
		
		$row = $this->getDbTable()->fetchAll($select);
		return isset($row[0]) && isset($row[0]["max_order"])? $row[0]["max_order"] : 0;
	}
}