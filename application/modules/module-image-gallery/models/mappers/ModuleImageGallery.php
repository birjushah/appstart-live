<?php
class ModuleImageGallery_Model_Mapper_ModuleImageGallery extends Standard_ModelMapper{
	protected $_dbTableClass = "ModuleImageGallery_Model_DbTable_ModuleImageGallery";
	public function getNextOrder($category_id) {
		$select = $this->getDbTable()->select(false)
		->from("module_image_gallery",array("max_order" => "max(`order`)"))
		->group("module_image_gallery_category_id")
		->having("module_image_gallery_category_id=".$category_id);
		$row = $this->getDbTable()->fetchAll($select);
		return isset($row[0]) && isset($row[0]["max_order"])? $row[0]["max_order"] : 0;
	}
}