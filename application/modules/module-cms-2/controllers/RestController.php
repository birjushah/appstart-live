<?php
class ModuleCms2_RestController extends Standard_Rest_Controller {
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
					$moduleCmsMapper = new ModuleCms2_Model_Mapper_ModuleCms2();
					$moduleCmsModel = $moduleCmsMapper->fetchAll("customer_id=".$customer_id);
					if($moduleCmsModel) {
						foreach($moduleCmsModel as $models) {
							$cmsDetails = array();
							$moduleCmsDetailMapper = new ModuleCms2_Model_Mapper_ModuleCmsDetail2();
							$moduleCmsDetailModel = $moduleCmsDetailMapper->fetchAll("module_cms_2_id=".$models->getModuleCms2Id());
							if($moduleCmsDetailModel) {
								foreach($moduleCmsDetailModel as $detail_model) {
									$details = $detail_model->toArray();
									if(isset($details["thumb"])) {
										$details["thumb"] = "resource/module-cms-2/thumb/".$details["thumb"];
									}
									$cmsDetails[] = $details;
								}
							}
							
							$response["data"][] = array("tbl_module_cms_2"=>$models->toArray(),"tbl_module_cms_detail_2"=>$cmsDetails);
						}
					}else{
						$response["data"][] = array("tbl_module_cms_2"=>array(),"tbl_module_cms_detail_2"=>array());
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