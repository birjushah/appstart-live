<?php
class Document_RestController extends Standard_Rest_Controller {
	/*
	 * (non-PHPdoc) @see Zend_Rest_Controller::getAction()
	 */
	public function getAction() {
		$service = $this->_request->getParam("service",null);
		if($service==null) {
			$this->_sendError("No service called");
		} else {
			if($service == "sync") {
				$this->_sync();
			} else {
				$this->_sendError("Invalid service");
			}
		}
	}
	
	/*
	 * (non-PHPdoc) @see Zend_Rest_Controller::postAction()
	 */
	public function postAction() {

	}
	
	/*
	 * (non-PHPdoc) @see Zend_Rest_Controller::putAction()
	 */
	public function putAction() {

	}
	
	/*
	 * (non-PHPdoc) @see Zend_Rest_Controller::deleteAction()
	 */
	public function deleteAction() {

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
					// Fetch Response Data Here
					
					$categoryMapper = new Document_Model_Mapper_ModuleDocumentCategory();
					$categoryModel = $categoryMapper->fetchAll("customer_id=".$customer_id);
					if($categoryModel) {
						foreach($categoryModel as $category) {
							$categoryDetails = array();
							$categoryDetailMapper = new Document_Model_Mapper_ModuleDocumentCategoryDetail();
							$categoryDetailModel = $categoryDetailMapper->fetchAll("module_document_category_id=".$category->getModuleDocumentCategoryId());
							if($categoryDetailModel) {
								foreach($categoryDetailModel as $category_detail) {
									$details = $category_detail->toArray();
									$categoryDetails[] = $details;
								}
							}
								
							$response["data"][] = array("tbl_module_document_category"=>$category->toArray(),"tbl_module_document_category_detail"=>$categoryDetails);
						}
					}else{
						$response["data"][] = array("tbl_module_document_category"=>array(),"tbl_module_document_category_detail"=>array());	
					}
					
					$documentMapper = new Document_Model_Mapper_ModuleDocument();
					$documentModel = $documentMapper->fetchAll("customer_id=".$customer_id);
					if($documentModel) {
						foreach($documentModel as $document) {
							$documentDetails = array();
							$documentDetailMapper = new Document_Model_Mapper_ModuleDocumentDetail();
							$documentDetailModel = $documentDetailMapper->fetchAll("module_document_id=".$document->getModuleDocumentId());
							if($documentDetailModel) {
								foreach($documentDetailModel as $document_detail) {
									$details = $document_detail->toArray();
									if(isset($details["document_path"])) {
										$details["document_path"] = "resource/document/uploads/".$details["document_path"];
									}
									$documentDetails[] = $details;
								}
							}
							
							$response["data"][] = array("tbl_module_document"=>$document->toArray(),"tbl_module_document_detail"=>$documentDetails);
						}
					}else{
						$response["data"][] = array("tbl_module_document"=>array(),"tbl_module_document_detail"=>array());
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
	/* (non-PHPdoc)
	 * @see Zend_Rest_Controller::headAction()
	 */public function headAction() {
		// TODO Auto-generated method stub
		}

}