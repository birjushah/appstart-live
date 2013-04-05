<?php
class ModuleImageGallery1_Model_Mapper_ModuleImageGalleryCategory1 extends Standard_ModelMapper{
	protected $_dbTableClass = "ModuleImageGallery1_Model_DbTable_ModuleImageGalleryCategory1";
	public function getNextOrder($customer_id) {
		$select = $this->getDbTable()->select(false)
		->from("module_image_gallery_category_1",array("max_order" => "max(`order`)"))
		->group("customer_id")
		->having("customer_id=".$customer_id);
	
		$row = $this->getDbTable()->fetchAll($select);
		return isset($row[0]) && isset($row[0]["max_order"])? $row[0]["max_order"] : 0;
	}
}