<?php
class Contact_RestController extends Standard_Rest_Controller {
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
					$contactCategoryMapper = new Contact_Model_Mapper_ContactCategory();
					$contactCategoryModel = $contactCategoryMapper->fetchAll("customer_id=".$customer_id);
					if($contactCategoryModel) {
					    foreach($contactCategoryModel as $contactcategory) {
					        $contactCategoryDetails = array();
					        $contactCategoryDetailMapper = new Contact_Model_Mapper_ContactCategoryDetail();
					        $contactCategoryDetailModel = $contactCategoryDetailMapper->fetchAll("contact_category_id=".$contactcategory->getContactCategoryId());
					        if($contactCategoryDetailModel) {
					            foreach($contactCategoryDetailModel as $contact_category_detail) {
					                $details = $contact_category_detail->toArray();
					                if(isset($details["icon"]) && $details["icon"] != null) {
					                    if(count(explode('/', $details["icon"])) > 1){
					                        $details["icon"] = "resource/contact/category/".$details["icon"];
					                    }else{
					                        $details["icon"] = "resource/contact/category/preset-icons/".$details["icon"];
					                    }
					                }
					                $contactCategoryDetails[] = $details;
					            }
					        }
					        	
					        $response["data"][] = array("tbl_contact_category"=>$contactcategory->toArray(),"tbl_contact_category_detail"=>$contactCategoryDetails);
					    }
					}else{
					    $response["data"][] = array("tbl_contact_category"=>array(),"tbl_contact_category_detail"=>array());
					}
					
					$contactTypesMapper = new Contact_Model_Mapper_ContactTypes();
					$contactTypesModel = $contactTypesMapper->fetchAll("customer_id=".$customer_id);
					if($contactTypesModel) {
					    foreach($contactTypesModel as $contacttypes) {
					        $contactTypesDetails = array();
					        $contactTypesDetailMapper = new Contact_Model_Mapper_ContactTypesDetail();
					        $contactTypesDetailModel = $contactTypesDetailMapper->fetchAll("contact_types_id=".$contacttypes->getContactTypesId());
					        if($contactTypesDetailModel) {
					            foreach($contactTypesDetailModel as $contact_types_detail) {
					                $details = $contact_types_detail->toArray();
					                if(isset($details["icon"]) && $details["icon"] != null) {
					                    if(count(explode('/', $details["icon"])) > 1){
					                        $details["icon"] = "resource/contact/types/".$details["icon"];
					                    }else{
					                        $details["icon"] = "resource/contact/types/preset-icons/".$details["icon"];
					                    }
					                }
					                $contactTypesDetails[] = $details;
					            }
					        }
					
					        $response["data"][] = array("tbl_contact_types"=>$contacttypes->toArray(),"tbl_contact_types_detail"=>$contactTypesDetails);
					    }
					}else{
					    $response["data"][] = array("tbl_contact_types"=>array(),"tbl_contact_types_detail"=>array());
					}
					
					$contactMapper = new Contact_Model_Mapper_Contact();
					$contactModel = $contactMapper->fetchAll("customer_id=".$customer_id);
					if($contactModel) {
						foreach($contactModel as $contact) {
							$contactDetails = array();
							$contactDetailMapper = new Contact_Model_Mapper_ContactDetail();
							$contactDetailModel = $contactDetailMapper->fetchAll("contact_id=".$contact->getContactId());
							if($contactDetailModel) {
								foreach($contactDetailModel as $contact_detail) {
									$details = $contact_detail->toArray();
									$details["information"] = strip_tags($details["information"]) != ""?$details["information"]:"";
									if(isset($details["logo"])) {
										$details["logo"] = "resource/contact/images/".$details["logo"];
									}
									if(isset($details["icon"]) && $details["icon"] != null) {
									    if(count(explode('/', $details["icon"])) > 1){
									        $details["icon"] = "resource/contact/".$details["icon"];
									    }else{
									        $details["icon"] = "resource/contact/preset-icons/".$details["icon"];
									    }
									}
									$contactDetails[] = $details;
								}
							}
							
							$response["data"][] = array("tbl_contact"=>$contact->toArray(),"tbl_contact_detail"=>$contactDetails);
						}
					}else{
						$response["data"][] = array("tbl_contact"=>array(),"tbl_contact_detail"=>array());
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