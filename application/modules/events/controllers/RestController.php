<?php
class Events_RestController extends Standard_Rest_Controller {
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
					$eventMapper = new Events_Model_Mapper_ModuleEvents();
					$eventModel = $eventMapper->fetchAll("customer_id=".$customer_id);
					if($eventModel) {
						foreach($eventModel as $event) {
							$eventDetails = array();
							$location = array();
							$eventDetailMapper = new Events_Model_Mapper_ModuleEventsDetail();
							$eventDetailModel = $eventDetailMapper->fetchAll("module_events_id=".$event->getModuleEventsId());
							if($eventDetailModel) {
								$eventLocationMapper = new Events_Model_Mapper_ModuleEventsLocation();
								foreach($eventDetailModel as $event_detail) {
									$details = $event_detail->toArray();
									$locationDetails = $eventLocationMapper->fetchAll("module_events_detail_id =".$details['module_events_detail_id']);
									if($locationDetails){
										foreach ($locationDetails as $locationDetail) {
											$locationEntry = $locationDetail->toArray();
											$location[] = $locationEntry;
										}
									}
									if(isset($details["image"])) {
										$details["image"] = "resource/events/images/".$details["image"];
									}
									if(isset($details["icon"]) && $details["icon"] != null) {
									    if(count(explode('/', $details["icon"])) > 1){
									        $details["icon"] = "resource/events/".$details["icon"];
									    }else{
									        $details["icon"] = "resource/events/preset-icons/".$details["icon"];
									    }
									}
									$eventDetails[] = $details;
								}
							}
							
							$response["data"][] = array("tbl_module_events"=>$event->toArray(),"tbl_module_events_detail"=>$eventDetails,"tbl_module_events_location"=>$location);
						}
					}else{
						$response["data"][] = array("tbl_module_events"=>array(),"tbl_module_events_detail"=>array(),"tbl_module_events_location"=>array());
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