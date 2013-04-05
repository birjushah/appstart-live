<?php
class ModuleImageGallery1_Model_Mapper_ModuleImageGallery1 extends Standard_ModelMapper{
	protected $_dbTableClass = "ModuleImageGallery1_Model_DbTable_ModuleImageGallery1";
	public function getNextOrder($category_id) {
		$select = $this->getDbTable()->select(false)
		->from("module_image_gallery_1",array("max_order" => "max(`order`)"))
		->group("module_image_gallery_category_1_id")
		->having("module_image_gallery_category_1_id=".$category_id);
		$row = $this->getDbTable()->fetchAll($select);
		return isset($row[0]) && isset($row[0]["max_order"])? $row[0]["max_order"] : 0;
	}
}	