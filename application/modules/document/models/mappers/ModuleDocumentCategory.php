<?php
class Document_Model_Mapper_ModuleDocumentCategory extends Standard_ModelMapper {
	protected $_dbTableClass = "Document_Model_DbTable_ModuleDocumentCategory";
	
	public function getNextOrder($parent_id) {
		$select = $this->getDbTable()->select(false)
						->from("module_document_category",array("max_order" => "max(`order`)"))
						->group("parent_id")
						->having("parent_id=".$parent_id);
		
		$row = $this->getDbTable()->fetchAll($select);
		return isset($row[0]) && isset($row[0]["max_order"])? $row[0]["max_order"] : 0;
	}
}