<?php
class Contact_Model_Mapper_ContactTypes extends Standard_ModelMapper {
	protected $_dbTableClass = "Contact_Model_DbTable_ContactTypes";
	public function getNextOrder($customer_id) {
	    $select = $this->getDbTable()->select(false)
	    ->from("module_contact_types",array("max_order" => "max(`order`)"))
	    ->where("customer_id=".$customer_id);
	
	    $row = $this->getDbTable()->fetchAll($select);
	    return isset($row[0]) && isset($row[0]["max_order"])? $row[0]["max_order"] : 0;
	}
}