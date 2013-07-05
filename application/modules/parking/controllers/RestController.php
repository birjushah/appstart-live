<?php
class Parking_RestController extends Standard_Rest_Controller {
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
			} else if($service == "live_data") {
				$this->_liveData();
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
					
					$parkingTypeMapper = new Parking_Model_Mapper_ModuleParkingType();
					$parkingTypeModel = $parkingTypeMapper->fetchAll("customer_id=".$customer_id);
					if($parkingTypeModel) {
						foreach($parkingTypeModel as $parkingType) {
							$parkingTypeDetails = array();
							$parkingTypeDetailMapper = new Parking_Model_Mapper_ModuleParkingTypeDetail();
							$parkingTypeDetailModel = $parkingTypeDetailMapper->fetchAll("module_parking_type_id=".$parkingType->getModuleParkingTypeId());
							if($parkingTypeDetailModel) {
								foreach($parkingTypeDetailModel as $parking_type_detail) {
									$details = $parking_type_detail->toArray();
									if(isset($details["icon"]) && $details["icon"] != null) {
										if(count(explode('/', $details["icon"])) > 1){
											$details["icon"] = "resource/parking/".$details["icon"];
										}else{
											$details["icon"] = "resource/parking/preset-icons/types/".$details["icon"];
										}
									}
									$parkingTypeDetails[] = $details;
								}
							}
								
							$response["data"][] = array("tbl_module_parking_type"=>$parkingType->toArray(),"tbl_module_parking_type_detail"=>$parkingTypeDetails);
						}
					}else{
						$response["data"][] = array("tbl_module_parking_type"=>array(),"tbl_module_parking_type_detail"=>array());
					}
					
					$parkingMapper = new Parking_Model_Mapper_ModuleParking();
					$parkingModel = $parkingMapper->fetchAll("customer_id=".$customer_id);
					if($parkingModel) {
						foreach($parkingModel as $parking) {
							$parkingDetails = array();
							$parkingDetailMapper = new Parking_Model_Mapper_ModuleParkingDetail();
							$parkingDetailModel = $parkingDetailMapper->fetchAll("module_parking_id=".$parking->getModuleParkingId());
							if($parkingDetailModel) {
								foreach($parkingDetailModel as $parking_detail) {
									$details = $parking_detail->toArray();
									if(isset($details["icon"]) && $details["icon"] != null) {
									    if(count(explode('/', $details["icon"])) > 1){
									        $details["icon"] = "resource/parking/".$details["icon"];
									    }else{
									        $details["icon"] = "resource/parking/preset-icons/".$details["icon"];
									    }
									}
									$parkingDetails[] = $details;
								}
							}
							$parkingType = array();
							$parkingTypeMapper = new Parking_Model_Mapper_ModuleParkingTypeMapping();
							$parkingTypeModel = $parkingTypeMapper->fetchAll("module_parking_id=".$parking->getModuleParkingId());
							if($parkingTypeModel) {
								foreach($parkingTypeModel as $parking_type) {
									$parkingType[] = $parking_type->toArray();;
								}
							}
							
							$response["data"][] = array("tbl_module_parking"=>$parking->toArray(),"tbl_module_parking_detail"=>$parkingDetails,"tbl_module_parking_type_mapping"=>$parkingType);
						}
					}else{
						$response["data"][] = array("tbl_module_parking"=>array(),"tbl_module_parking_detail"=>array(),"tbl_module_parking_type_mapping"=>array());
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
	
	protected function _liveData() {
		$customer_id = $this->_request->getParam("customer_id",null);
		if($customer_id===null) {
			$this->_sendError("Invalid request");
		} else {
			try{
				$mapper = new Admin_Model_Mapper_Customer();
				$customer = $mapper->find($customer_id);
				if($customer) {
					$response = array();
					$parkingMapper = new Parking_Model_Mapper_ModuleParking();
					$parkingModel = $parkingMapper->fetchAll("customer_id=".$customer_id);
					if($parkingModel) {
						foreach($parkingModel as $parking) {
							$parkingLiveData = array();
							$parkingLiveDataMapper = new Parking_Model_Mapper_ModuleParkingLiveData();
							$parkingLiveDataModel = $parkingLiveDataMapper->fetchAll("module_parking_id=".$parking->getModuleParkingId());
							if($parkingLiveDataModel) {
								foreach($parkingLiveDataModel as $parking_live_data) {
									$parkingLiveData[] = $parking_live_data->toArray();
								}
							}
								
							$response["data"][] = array("tbl_module_parking_live_data"=>$parkingLiveData);
						}
					}else{
						$response["data"][] = array("tbl_module_parking_live_data"=>array());
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