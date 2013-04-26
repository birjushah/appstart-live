<?php
class PushMessage_RestController extends Standard_Rest_Controller {
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
					$pushmessageMapper = new PushMessage_Model_Mapper_PushMessage();
					$pushmessageModel = $pushmessageMapper->fetchAll("customer_id=".$customer_id);
					if($pushmessageModel) {
						foreach($pushmessageModel as $pushmessage) {
							$pushmessageDetail = array();
							$pushmessageDetailMapper = new PushMessage_Model_Mapper_PushMessageDetail();
							$pushmessageDetailModel = $pushmessageDetailMapper->fetchAll("push_message_id=".$pushmessage->getPushMessageId());
							if($pushmessageDetailModel) {
								foreach($pushmessageDetailModel as $pushmessage_detail) {
									$details = $pushmessage_detail->toArray();
									if(isset($details["icon"]) && $details["icon"] != null){
									    if(count(explode("/", $details["icon"])) > 1){
                                            $details["icon"] = "resource/push-message/".$details["icon"];
									    }else{
									        $details["icon"] = "resource/push-message/preset-icons/".$details["icon"];
									    }
									}
									$pushmessageDetail[] = $details;
								}
							}
							
							$response["data"][] = array("tbl_push_message"=>$pushmessage->toArray(),"tbl_push_message_detail"=>$pushmessageDetail);
						}
					}else{
						$response["data"][] = array("tbl_push_message"=>array(),"tbl_push_message_detail"=>array());
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