<?php
class Document_Model_Mapper_ModuleDocument extends Standard_ModelMapper {
	protected $_dbTableClass = "Document_Model_DbTable_ModuleDocument";
	
	public function getNextOrder($customer_id) {
		$select = $this->getDbTable()->select(false)
						->from("module_document",array("max_order" => "max(`order`)"))
						->group("customer_id")
						->having("customer_id=".$customer_id);
		
		$row = $this->getDbTable()->fetchAll($select);
		return isset($row[0]) && isset($row[0]["max_order"])? $row[0]["max_order"] : 0;
	}
}