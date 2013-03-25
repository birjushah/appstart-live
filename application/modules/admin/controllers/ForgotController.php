<?php
class Admin_ForgotController extends Zend_Controller_Action {
	public function init() {
	}
	public function indexAction() {
		$request = $this->getRequest ();
		$form = new Admin_Form_Login ();
		$form->removeElement("password");
		if ($this->getRequest ()->isPost ()) {
			if ($form->isValid ( $request->getPost () )) {
				$newPassword = $this->_getPassword();
				$mapper = new Admin_Model_Mapper_SystemUser();
				$system_user = $mapper->fetchAll("email='". $request->getParam("email") ."'");
				if(is_array($system_user)) {
					$system_user = $system_user[0];
					$system_user->setPassword(md5($newPassword.$system_user->getEmail()));
					$system_user->save();
					$parseVariable = array();
					$parseVariable["{PASSWORD}"] = $newPassword;
					$parseVariable["{DATETIME}"] = Standard_Functions::getCurrentDateTime(null,"m-d-Y H:i:s");
					$parseVariable["{RESET_URL}"] = $this->view->url(array("module"=>"admin","controller"=>"forgot","action"=>"index"),"default",true);
					$parseVariable["{RESET_URL}"] = $this->getRequest()->getScheme() . '://' . $this->getRequest()->getHttpHost() . $parseVariable["{RESET_URL}"];
					$email = new Standard_Email();
					$email->sendEmail("password_reset_admin.phtml", 
										"Password Reset", $parseVariable, 
										array($request->getParam("email")=>"System User"));
					$this->view->success = true;
				} else {
					$this->view->errorMessage = "Invalid Email";
				}				
			} else {
				$this->view->errorMessage = "Invalid Email";
			}
		}
		
		foreach ($form->getElements() as $element) {
			if($element->getDecorator('Label')) $element->getDecorator('Label')->setTag(null);
		}
		$this->view->form = $form;
	}
	protected function _getPassword()
	{
		$characterList = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
		$i = 0;
		$salt = "";
		do {
			$salt .= $characterList{mt_rand(0,strlen($characterList)-1)};
			$i++;
		} while ($i < 9);
		return $salt;
	}
}