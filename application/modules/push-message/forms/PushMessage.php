<?php 
class PushMessage_Form_PushMessage extends Standard_Form{
	public static $_module_id;
	public function init(){
		$this->setMethod('POST');
		$notEmptyValidator = new Zend_Validate_NotEmpty ();
		$notEmptyValidator->setMessage ( 'Enter Valid Value For The Field.' );
		$date_time = Standard_Functions::getLocalDateTime ();
		$message_date = DateTime::createFromFormat ( "Y-m-d H:i:s", $date_time );
		$message_date = $message_date->format ( "d/m/Y H:i" ) ;
		// Push Message ID
		$push_message_id = $this->createElement ( "hidden", "push_message_id", array (
				'value' => '',
				'filters' => array (
						'StringTrim'
				)
		) );
		$this->addElement ( $push_message_id);
		
		// Module Document Category ID
		$push_message_category_id = $this->createElement ( "hidden", "push_message_category_id", array (
		        'value' => '',
		        'filters' => array (
		                'StringTrim'
		        )
		) );
		$this->addElement ( $push_message_category_id);
		
		// Push Message Detail ID
		$push_message_detail_id = $this->createElement ( "hidden", "push_message_detail_id", array (
				'value' => '',
				'filters' => array (
						'StringTrim'
				)
		) );
		$this->addElement ( $push_message_detail_id);
		
		// Language ID
		$language_id = $this->createElement ( "hidden", "language_id", array (
				'filters' => array (
						'StringTrim'
				)
		) );
		$this->addElement ( $language_id );
		
		// Push Message Title
		$title = $this->createElement ( "text", "title", array (
				'label' => 'Push Message Title:',
				'size' => '64',
				'required' => true,
				'filters' => array (
						'StringTrim'
				),
				'validators' => array (
						array (
								$notEmptyValidator,
								true
						)
				),
				'errorMessages' => array (
						'Invalid Image Title'
				)
		) );
		$title->setAttrib("required", "required");
		$this->addElement ($title);
		
		//Push Message Description
		$description = $this->createElement("textarea","description",array(
				'label' => 'Push Message Description:',
				'style' => 'width:500px;',
				'required' => true,
		        'maxlength' => 255,
				'filters' => array(
						'StringTrim'
				),
				'validators' => array(
						array(
								$notEmptyValidator,
								true
						)
				),
				'errorMessages' => array(
						'Invalid Message Description'
				)
		));
		$description->setAttrib("required", "required");
		$this->addElement($description);
		$this->addElement('checkbox', 'status', array(
				'label'      => 'Active',
				'value'      => '1'
		));
		

		//message time
		$message = $this->createElement ( "text", "message_date", array (
				'label' => 'Message Date-Time:',
				'size' => '30',
				'filters' => array (
						'StringTrim'
				),
				'value' => $message_date,
				'validators' => array(
						array(
								$notEmptyValidator,
								true
						)
				)
		) );
		$message->setAttrib("required", "required");
		$this->addElement ($message);
		
		// Phone
		$phone = $this->createElement ( "text", "phone", array (
				'label' => 'Phone:',
				'size' => '20',
				'filters' => array (
						'StringTrim'
				)
		) );
		$this->addElement ($phone);
		
		// Email
		$email = $this->createElement ( "text", "email", array (
				'label' => 'Email:',
				'size' => '120',
				'filters' => array (
						'StringTrim'
				)
		) );
		$this->addElement ($email);
		
		// Website
		$website = $this->createElement ( "text", "website", array (
				'label' => 'Website:',
				'size' => '255',
				'filters' => array (
						'StringTrim'
				)
		) );
		$this->addElement ($website);
		
		// Code
		$code = $this->createElement ( "text", "code", array (
				'label' => 'Code:',
				'size' => '120',
				'filters' => array (
						'StringTrim'
				)
		) );
		$this->addElement ($code);
		
		// Notes
		$notes = $this->createElement("textarea","notes",array(
				'label' => 'Notes:',
				'style' => 'width:500px;',
				'maxlength' => 255,
				'filters' => array(
						'StringTrim'
				)
		));
		$this->addElement($notes);
		
		// Module
		$module_id= $this->createElement ( "select", "module_id", array (
				'label' => 'Module:',
				'MultiOptions' => $this->_getLinkModule (),
				'filters' => array (
						'StringTrim'
				)
		) );
		$this->addElement ( $module_id );
		
		//Icon
		$icon = $this->createElement ( 'file', 'icon' );
		$icon->setLabel ( 'Icon:' )->setDestination ( Standard_Functions::getResourcePath () . "push-message/uploaded-icons" )->addValidator ( 'Size', false, 102400 )->addValidator ( 'Extension', false, 'jpg,png,gif' );
		$this->addElement ( $icon );

		// Submit button
		$submit = $this->addElement ( 'submit', 'submit', array (
				'ignore' => true,
				'class' => "button"
		) );
		
		// Submit For ALL button
		$allpyall = $this->addElement ( 'button', 'applyall', array (
		        'ignore' => true,
		        'class' => "button",
		        'label' => 'submit to all'
		) );
		
		// Reset button
		$reset = $this->addElement ( 'reset', 'reset', array (
				'ignore' => true,
				'class' => "button"
		) );
		$this->addElements ( array (
				$submit,
				$reset
		) );
		
	}
	public function _getLinkModule ()
	{
		$modules = array(""=>"Select Module");
		$mapper = new Admin_Model_Mapper_CustomerModule();
		$model = $mapper->fetchAll("status=1 AND customer_id=".Standard_Functions::getCurrentUser()->customer_id);
		$active_lang_id = Standard_Functions::getCurrentUser ()->active_language_id;
		foreach ($model as $customerModule) {
			if(self::$_module_id != $customerModule->getModuleId()) {
				$mapperDetail = new Admin_Model_Mapper_CustomerModuleDetail();
				$modelDetail = $mapperDetail->fetchAll("customer_module_id = ".$customerModule->get("customer_module_id"). " AND language_id=".$active_lang_id);
	
				if(is_array($modelDetail) && is_object($modelDetail[0])) {
					$modules[$customerModule->getModuleId()] = $modelDetail[0]->getScreenName();
				}
			}
		}
		$modules = array_filter($modules,function($element){
			return ($element != "");
		});
		return $modules;
	}
}