<?php
class ModuleImageGallery_RestController extends Standard_Rest_Controller {
	/*
	 * (non-PHPdoc) @see Zend_Rest_Controller::getAction()
	 */
	public function getAction() {
		// TODO Auto-generated method stub
		$service = $this->_request->getParam("service",null);
		if($service==null) {
			$this->_sendError("No service called");
		} else {
			if($service == "sync") {
				$this->_sync();
			} else if ($service == "download") { 
				$this->_download();
			}else {
				$this->_sendError("Invalid service");
			}
		}
	}
	
	/*
	 * (non-PHPdoc) @see Zend_Rest_Controller::postAction()
	 */
	public function postAction() {
		// TODO Auto-generated method stub
	}
	
	/*
	 * (non-PHPdoc) @see Zend_Rest_Controller::putAction()
	 */
	public function putAction() {
		// TODO Auto-generated method stub
	}
	
	/*
	 * (non-PHPdoc) @see Zend_Rest_Controller::deleteAction()
	 */
	public function deleteAction() {
		// TODO Auto-generated method stub
	}
	
	protected function _sync() {
		$customer_id = $this->_request->getParam("customer_id",null);
		if($customer_id===null) {
			$this->_sendError("Invalid request");
		} else {
			try{
				$mapper = new Admin_Model_Mapper_Customer();
				$customer = $mapper->find($customer_id);
				if($customer) {
					$response = array();
					$imageCategoryMapper = new ModuleImageGallery_Model_Mapper_ModuleImageGalleryCategory();
					$categoryModel = $imageCategoryMapper->fetchAll("customer_id=".$customer_id);
					if($categoryModel) {
						foreach($categoryModel as $category) {
							$categoryDetails = array();
							$categoryDetailMapper = new ModuleImageGallery_Model_Mapper_ModuleImageGalleryCategoryDetail();
							$categoryDetailModel = $categoryDetailMapper->fetchAll("module_image_gallery_category_id=".$category->getModuleImageGalleryCategoryId());
							if($categoryDetailModel) {
								foreach($categoryDetailModel as $category_detail) {
									$details = $category_detail->toArray();
    								if(isset($details["icon"]) && $details["icon"] != null) {
    					                if(count(explode('/', $details["icon"])) > 1){
    					                    $details["icon"] = "resource/module-image-gallery/".$details["icon"];
    					                }else{
    					                    $details["icon"] = "resource/module-image-gallery/preset-icons/".$details["icon"];
    					                }
    					            }
									$categoryDetails[] = $details;
								}
							}
							
							$response["data"][] = array("tbl_module_image_gallery_category"=>$category->toArray(),"tbl_module_image_gallery_category_detail"=>$categoryDetails);
						}
					}else{
						$response["data"][] = array("tbl_module_image_gallery_category"=>array(),"tbl_module_image_gallery_category_detail"=>array());
					}
					$imageMapper = new ModuleImageGallery_Model_Mapper_ModuleImageGallery();
					$imageModel = $imageMapper->fetchAll("customer_id=".$customer_id);
					if($imageModel) {
						foreach($imageModel as $image) {
							$imageDetails = array();
							$imageDetailMapper = new ModuleImageGallery_Model_Mapper_ModuleImageGalleryDetail();
							$imageDetailModel = $imageDetailMapper->fetchAll("module_image_gallery_id=".$image->getModuleImageGalleryId());
							if($imageDetailModel) {
								foreach($imageDetailModel as $image_detail) {
									$details = $image_detail->toArray();
									if(isset($details["image_path"])) {
										$details["image_path"] = "resource/module-image-gallery/thumb/".$customer_id."/".$details["image_path"];
									}
									$imageDetails[] = $details;
								}
							}
							
							$response["data"][] = array("tbl_module_image_gallery"=>$image->toArray(),"tbl_module_image_gallery_detail"=>$imageDetails);
						}
					}else{
						$response["data"][] = array("tbl_module_image_gallery"=>array(),"tbl_module_image_gallery_detail"=>array());
					}
					$data["status"] = "success";
					$data["data"] = $response;
					$this->_sendData($data);
				} else {
					$this->_sendError("Invalid customer ID");
				}
			} catch (Exception $ex) {
				$this->_sendError($ex->getMessage());
			}
		}
	}
	protected function _download() {
		$customer_id = $this->_request->getParam("customer_id",null);
		if($customer_id===null) {
			$this->_sendError("Invalid request");
		} else {
			try{
				$file_path = "resource/module-image-gallery/thumb/".$customer_id."/";
				$zip = new ZipArchive();
				$zip->open($file_path . "images.zip",ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE);
				
				$imageMapper = new ModuleImageGallery_Model_Mapper_ModuleImageGallery();
				$imageModel = $imageMapper->fetchAll("customer_id=".$customer_id);
				if($imageModel) {
					foreach($imageModel as $image) {
						$imageDetails = array();
						$imageDetailMapper = new ModuleImageGallery_Model_Mapper_ModuleImageGalleryDetail();
						$imageDetailModel = $imageDetailMapper->fetchAll("module_image_gallery_id=".$image->getModuleImageGalleryId());
						if($imageDetailModel) {
							foreach($imageDetailModel as $image_detail) {
								$details = $image_detail->toArray();
								if(isset($details["image_path"])) {
									$zip->addFile($file_path .$details["image_path"],$details["image_path"]);
								}
							}
						}
					}
				}
				if(($zip->close()===true)) {
					header('Content-Type: application/zip');
					header('Content-disposition: attachment; filename=images.zip');
					header('Content-Length: ' . filesize($file_path."images.zip"));
					readfile($file_path."images.zip");
					unlink($file_path."images.zip");
					die;
				}
			} catch (Exception $ex) {
				$this->_sendError($ex->getMessage());
			}
		}
	}
	/* (non-PHPdoc)
	 * @see Zend_Rest_Controller::headAction()
	 */public function headAction() {
		// TODO Auto-generated method stub
		}

}