<?php 
class PushMessage_Form_PushMessage extends Standard_Form{
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
}