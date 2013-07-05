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
					
					$eventCategoryMapper = new Events_Model_Mapper_ModuleEventsCategory();
					$eventCategoryModel = $eventCategoryMapper->fetchAll("customer_id=".$customer_id);
					if($eventCategoryModel) {
					    foreach($eventCategoryModel as $category) {
					        $eventCategoryDetails = array();
					        $eventCategoryDetailMapper = new Events_Model_Mapper_ModuleEventsCategoryDetail();
					        $eventCategoryDetailModel = $eventCategoryDetailMapper->fetchAll("module_events_category_id=".$category->getModuleEventsCategoryId());
					        if($eventCategoryDetailModel) {
					            foreach($eventCategoryDetailModel as $category_detail) {
					                $details = $category_detail->toArray();
					                if(isset($details["icon"]) && $details["icon"] != null) {
					                    if(count(explode('/', $details["icon"])) > 1){
					                        $details["icon"] = "resource/events/category/".$details["icon"];
					                    }else{
					                        $details["icon"] = "resource/events/category/preset-icons/".$details["icon"];
					                    }
					                }
					                $eventCategoryDetails[] = $details;
					            }
					        }
					
					        $response["data"][] = array("tbl_module_events_category"=>$category->toArray(),"tbl_module_events_category_detail"=>$eventCategoryDetails);
					    }
					}else{
					    $response["data"][] = array("tbl_module_events_category"=>array(),"tbl_module_events_category_detail"=>array());
					}
						
					$eventTypesMapper = new Events_Model_Mapper_ModuleEventsTypes();
					$eventTypesModel = $eventTypesMapper->fetchAll("customer_id=".$customer_id);
					if($eventTypesModel) {
					    foreach($eventTypesModel as $types) {
					        $eventTypesDetails = array();
					        $eventTypesDetailMapper = new Events_Model_Mapper_ModuleEventsTypesDetail();
					        $eventTypesDetailModel = $eventTypesDetailMapper->fetchAll("module_events_types_id=".$types->getModuleEventsTypesId());
					        if($eventTypesDetailModel) {
					            foreach($eventTypesDetailModel as $event_types_detail) {
					                $details = $event_types_detail->toArray();
					                if(isset($details["icon"]) && $details["icon"] != null) {
					                    if(count(explode('/', $details["icon"])) > 1){
					                        $details["icon"] = "resource/events/types/".$details["icon"];
					                    }else{
					                        $details["icon"] = "resource/events/types/preset-icons/".$details["icon"];
					                    }
					                }
					                $eventTypesDetails[] = $details;
					            }
					        }
					
					        $response["data"][] = array("tbl_module_events_types"=>$types->toArray(),"tbl_module_events_types_detail"=>$eventTypesDetails);
					    }
					}else{
					    $response["data"][] = array("tbl_module_events_types"=>array(),"tbl_module_events_types_detail"=>array());
					}
					
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
									$details["information"] = ($details["information"]!="") ? $this->getNativeContent($details["information"]) : "";
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
	public function getNativeContent($content) {
	    $dom = new DOMDocument;
	    libxml_use_internal_errors(true);
	
	    $dom->loadHTML( "<html>".$content ."</html>" );
	    $xpath = new DOMXPath( $dom );
	    libxml_clear_errors();
	
	    $doc = $dom->getElementsByTagName("html")->item(0);
	    $src = $xpath->query(".//@src");
	
	    foreach ( $src as $s ) {
	        $s->nodeValue = str_replace('/appstart/public', '', $s->nodeValue);
	        if(stripos($s->nodeValue, "/resource/")===0) {
	            $s->nodeValue = ".".$s->nodeValue;
	        }
	        $s->nodeValue = 'data:image/' . filetype($s->nodeValue) . ';base64,' . base64_encode(file_get_contents($s->nodeValue));
	    }
	
	    $output = $dom->saveXML( $doc );
	    return $output;
	}
	/* (non-PHPdoc)
	 * @see Zend_Rest_Controller::headAction()
	 */public function headAction() {
		// TODO Auto-generated method stub
		}

}