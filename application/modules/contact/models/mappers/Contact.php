<?php
class Contact_Model_Mapper_Contact extends Standard_ModelMapper {
	protected $_dbTableClass = "Contact_Model_DbTable_Contact";
	
	public function getNextOrder($customer_id) {
		$select = $this->getDbTable()->select(false)
						->from("module_contact",array("max_order" => "max(`order`)"))
						->group("customer_id")
						->having("customer_id=".$customer_id);
		
		$row = $this->getDbTable()->fetchAll($select);
		return isset($row[0]) && isset($row[0]["max_order"])? $row[0]["max_order"] : 0;
	}
}