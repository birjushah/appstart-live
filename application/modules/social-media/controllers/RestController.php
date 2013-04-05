<?php
class SocialMedia_RestController extends Standard_Rest_Controller {
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
					$socialMediaMapper = new SocialMedia_Model_Mapper_ModuleSocialMedia();
					$socialMediaModel = $socialMediaMapper->fetchAll("customer_id=".$customer_id);
					if($socialMediaModel) {
						foreach($socialMediaModel as $socialMedia) {
							$socialMediaDetails = array();
							$socialMediaDetailMapper = new SocialMedia_Model_Mapper_ModuleSocialMediaDetail();
							$socialMediaDetailModel = $socialMediaDetailMapper->fetchAll("module_social_media_id=".$socialMedia->getModuleSocialMediaId());
							if($socialMediaDetailModel) {
								foreach($socialMediaDetailModel as $socialMedia_detail) {
									$details = $socialMedia_detail->toArray();
									if(isset($details["icon_path"])) {
										$details["icon_path"] = "resource/social-media/".$details["icon_path"];
									}
									$socialMediaDetails[] = $details;
								}
							}
							
							$response["data"][] = array("tbl_module_social_media"=>$socialMedia->toArray(),"tbl_module_social_media_detail"=>$socialMediaDetails);
						}
					}else{
						$response["data"][] = array("tbl_module_social_media"=>array(),"tbl_module_social_media_detail"=>array());
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