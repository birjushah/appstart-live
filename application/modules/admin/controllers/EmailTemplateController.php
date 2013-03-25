<?php
class Admin_EmailTemplateController extends Zend_Controller_Action {
	
	public function indexAction()
	{
		// action body
		
	}
	
	public function loadTemplateAction() {
		$this->_helper->layout ()->disableLayout ();
		$this->_helper->viewRenderer->setNoRender ();
		
		$appConfig=Zend_Registry::get("AppConfig");
		$template_dir = $appConfig["Email"]["emailTemplateDir"];
		$template_name = $this->_request->getParam("template_name","");
		$response = array();
		if($template_name!="" && file_exists($template_dir."/templates/".$template_name.".phtml")) {
			$content = file_get_contents($template_dir."/templates/".$template_name.".phtml");
			$template_vars = $this->_getTemplateVars($template_name);
			
			$response["error"] = false;
			$response["message"] = array("content"=>$content, "template_vars"=>$template_vars);
			
			$jsonResponse = Zend_Json::encode($response);
			$this->_response->appendBody($jsonResponse);
		} else {
			$response["error"] = true;
			$response["message"] = "Template not found/exsist";
				
			$jsonResponse = Zend_Json::encode($response);
			$this->_response->appendBody($jsonResponse);
		}
	}
	
	private function _getTemplateVars($template_name) {
		$template_vars = "";
		if($template_name == "password_reset_admin") {
			$template_vars .= "<div class='template-var'><a href='#'>{DATETIME}</a></div>";
			$template_vars .= "<div class='template-var'><a href='#'>{PASSWORD}</a></div>";
			$template_vars .= "<div class='template-var'><a href='#'>{RESET_URL}</a></div>";
		} else if($template_name == "welcome_customer") {
			$template_vars .= "<div class='template-var'><a href='#'>{USERNAME}</a></div>";
		}  else if($template_name == "welcome_user") {
			$template_vars .= "<div class='template-var'><a href='#'>{EMAIL}</a></div>";
		}
		return $template_vars;
	}
	
	public function saveAction() {
		$this->_helper->layout ()->disableLayout ();
		$this->_helper->viewRenderer->setNoRender ();
		
		$appConfig=Zend_Registry::get("AppConfig");
		$template_dir = $appConfig["Email"]["emailTemplateDir"];
		$template_name = $this->_request->getParam("template_name","");
		$content = $this->_request->getParam("content","");
		try {
			$response = array();
			if($template_name!="" && file_exists($template_dir."/templates/".$template_name.".phtml")) {
				file_put_contents($template_dir."/templates/".$template_name.".phtml", $content);
				
				$response["error"] = false;
				$response["message"] = "Template Saved Successfully";
				
				$jsonResponse = Zend_Json::encode($response);
				$this->_response->appendBody($jsonResponse);
			} else {
				$response["error"] = true;
				$response["message"] = "Template not found/exsist";
				
				$jsonResponse = Zend_Json::encode($response);
				$this->_response->appendBody($jsonResponse);
			}
		} catch(Exception $ex) {
			$response["error"] = true;
			$response["message"] = "Error Template";
			
			$jsonResponse = Zend_Json::encode($response);
			$this->_response->appendBody($jsonResponse);
		}
	}
}