<?php
class Standard_Email extends Zend_Mail {
	protected $_charset;
	protected $_emailTemplateDir= "";
	protected $_emailTemplate="";
	protected $_emailLayout="";
	protected $_emailConfig;
	static $_baseURL="";

	public function __construct($option=null) {
		if($option!=null)
		{
			$this->_charset=$option;
		}
		$appConfig=Zend_Registry::get("AppConfig");

		$this->_emailConfig = $appConfig["Email"];
		$this->_emailTemplateDir= $appConfig["Email"]["emailTemplateDir"];
		$this->_emailLayout=$appConfig["Email"]["emailLayout"];
	}
	
	public function sendEmail($emailTemplate,$emailSubject,array $parseVariable,array $EmailList,$hasAttachemnt=false,$attachementPath="")
	{
		$mailBody=$this->parseEmailTemplate($emailTemplate, $parseVariable);
		$this->setBodyHtml($mailBody);
		$this->setSubject($emailSubject);
		foreach($EmailList as $email=>$name){
			$this->addTo($email,$name);
		}
		if($this->_emailConfig["DemoMode"]){
			$EmailArray = @explode(";",$this->_emailConfig["DemoEmail"]);
			foreach($EmailArray as $DemoEmail){
				$this->addTo($DemoEmail,"Demo Mode");
			}
		}
	
		$this->setFrom($this->_emailConfig["defaultFrom"]["Email"],$this->_emailConfig["defaultFrom"]["Name"]);
	
		if($hasAttachemnt==true && $attachementPath!=""){
			$attachment = new Zend_Mime_Part(file_get_contents($attachementPath));
			//$attachment->type = 'application/pdf';
			//$attachment->disposition = Zend_Mime::DISPOSITION_ATTACHMENT;
			$pathInfo =  pathinfo($attachementPath);
			$attachment->filename =$pathInfo['basename'];
			$this->addAttachment($attachment);
		}
		$transport = $this->getTransport();
		
		return $this->send($transport);
	}
	public function getTransport()
	{
		$transport = new Zend_Mail_Transport_Smtp($this->_emailConfig["smtp"],
				$this->_emailConfig["ZendMail"]);
	
		return $transport ;
	}
	public function getAppURL(){
		return self::$_baseURL;
	}
	public function parseEmailTemplate($emailTemplate,array $parseVariable,$emailLayout="")
	{
		/**check if TemplateFile file exist**/
		$TemplateContent="";
		if($emailLayout==""){
			$emailLayout=$this->_emailLayout;
		}
		$parseVariable["{BASE_URL}"]=$this->getAppURL();
		if(file_exists($this->_emailTemplateDir.$emailLayout) && $emailLayout!=""){
			$TemplateContent = file_get_contents($this->_emailTemplateDir . "/templates/" . $emailLayout);
		}
		
		$emailTemplate = file_get_contents($this->_emailTemplateDir.$emailTemplate);
		if($TemplateContent!=""){
			$TemplateContent=str_replace("{TEMPLATE}", $emailTemplate,$TemplateContent) ;
		}else{
			$TemplateContent=$emailTemplate;
		}
		
		if (is_array($parseVariable) && count($parseVariable)){
			foreach($parseVariable as $key=>$value){
				$TemplateContent=str_replace($key,$value, $TemplateContent) ;
			}
		}
		return $TemplateContent;
	}
}