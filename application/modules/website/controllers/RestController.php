<?php
class Website_RestController extends Standard_Rest_Controller {
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
			} else {
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
					$websiteMapper = new Website_Model_Mapper_ModuleWebsite();
					$websiteModel = $websiteMapper->fetchAll("customer_id=".$customer_id);
					if($websiteModel) {
						foreach($websiteModel as $website) {
							$websiteDetail = array();
							$websiteDetailMapper = new Website_Model_Mapper_ModuleWebsiteDetail();
							$websiteDetailModel = $websiteDetailMapper->fetchAll("module_website_id=".$website->getModuleWebsiteId());
							if($websiteDetailModel) {
								foreach($websiteDetailModel as $website_detail) {
									$websiteDetail = $website_detail->toArray();
									if($websiteDetail["website_logo"] != null){
										$websiteDetail["website_logo"] = "resource/website/logos/".$websiteDetail["website_logo"];
									}
									$details[] = $websiteDetail; 
								}
							}
							$response["data"][] = array("tbl_module_website"=>$website->toArray(),"tbl_module_website_detail"=>$details);
						}
					}else{
						$response["data"][] = array("tbl_module_website"=>array(),"tbl_module_website_detail"=>array());
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