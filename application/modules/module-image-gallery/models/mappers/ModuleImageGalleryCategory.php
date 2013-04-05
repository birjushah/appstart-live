<?php
class ModuleImageGallery_Model_Mapper_ModuleImageGalleryCategory extends Standard_ModelMapper{
	protected $_dbTableClass = "ModuleImageGallery_Model_DbTable_ModuleImageGalleryCategory";
	public function getNextOrder($customer_id) {
		$select = $this->getDbTable()->select(false)
		->from("module_image_gallery_category",array("max_order" => "max(`order`)"))
		->group("customer_id")
		->having("customer_id=".$customer_id);
	
		$row = $this->getDbTable()->fetchAll($select);
		return isset($row[0]) && isset($row[0]["max_order"])? $row[0]["max_order"] : 0;
	}
}