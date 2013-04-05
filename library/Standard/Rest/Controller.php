<?php
abstract class Standard_Rest_Controller extends Zend_Rest_Controller {
	public function init() {
		$this->_helper->layout->disableLayout ();
		$this->_helper->viewRenderer->setNoRender ( true );
	}
	
	public function indexAction() {
		// TODO Auto-generated method stub
	}
	
	protected function _sendError($message){
		$returnData = array();
		$returnData["status"] = "error";
		$returnData["data"] = $message;
		return $this->_helper->json ( $returnData );
	}
	protected function _sendData($message){
		$returnData = array();
		if(!is_array($message)){
			$returnData[] = $message;
		} else {
			$returnData = $message;
		}
		return $this->_helper->json ( $returnData );
	}
}