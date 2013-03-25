<?php
class ModuleCms_RestController extends Standard_Rest_Controller {
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
		$device_type = $this->_request->getParam("device_type",null);
		if($customer_id===null || $device_type == null) {
			$this->_sendError("Invalid request");
		} else {
			try{
				$mapper = new Admin_Model_Mapper_Customer();
				$customer = $mapper->find($customer_id);
				if($customer) {
					$response = array();
					$moduleCmsMapper = new ModuleCms_Model_Mapper_ModuleCms();
					$moduleCmsModel = $mapper->fetchAll("customer_id=".$customer_id);
					if($moduleCmsModel) {
						foreach($moduleCmsModel as $models) {
							$details = array();
							$moduleCmsDetailMapper = new ModuleCms_Model_Mapper_ModuleCmsDetail();
							$moduleCmsDetailModel = $moduleCmsDetailMapper->fetchAll("module_cms_id=".$models->getModuleCmsId());
							if($moduleCmsDetailModel) {
								foreach($moduleCmsDetailModel as $detail_model) {
									$details = $detail_model->toArray();
									if(isset($details["thumb"])) {
										$details["thumb"] = "resource/module-cms/thumb/".$details["thumb"];
									}
									$moduleCmsDetailModel[] = $details;
								}
							}
							
							$response["data"][] = array("tbl_module_cms"=>$models->toArray(),"tbl_module_cms_detail"=>$moduleCmsDetailModel);
						}
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
}