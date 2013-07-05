<?php
class Website1_RestController extends Standard_Rest_Controller {
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
					$websiteMapper = new Website1_Model_Mapper_ModuleWebsite1();
					$websiteModel = $websiteMapper->fetchAll("customer_id=".$customer_id);
					if($websiteModel) {
						foreach($websiteModel as $website) {
							$websiteDetail = array();
							$websiteDetailMapper = new Website1_Model_Mapper_ModuleWebsiteDetail1();
							$websiteDetailModel = $websiteDetailMapper->fetchAll("module_website_1_id=".$website->getModuleWebsite1Id());
							if($websiteDetailModel) {
								$details = array();
								foreach($websiteDetailModel as $website_detail) {
									$websiteDetail = $website_detail->toArray();
									if(isset($websiteDetail["website_logo"]) && $websiteDetail["website_logo"] != null){
									    if(count(explode("/", $websiteDetail["website_logo"])) > 1){
                                            $websiteDetail["website_logo"] = "resource/website-1/".$websiteDetail["website_logo"];
									    }else{
									        $websiteDetail["website_logo"] = "resource/website-1/preset-icons/".$websiteDetail["website_logo"];
									    }
									}
									$details[] = $websiteDetail; 
								}
							}
							
							$response["data"][] = array("tbl_module_website_1"=>$website->toArray(),"tbl_module_website_detail_1"=>$details);
						}
					}else{
						$response["data"][] = array("tbl_module_website_1"=>array(),"tbl_module_website_detail_1"=>array());
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