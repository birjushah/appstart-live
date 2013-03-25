<?php
class Admin_Form_BusinessType extends Standard_Form {
	public function init() {
		$this->setMethod ( 'POST' );
		$notEmptyValidator = new Zend_Validate_NotEmpty ();
		$notEmptyValidator->setMessage ( 'Enter Enter Valid Value For The Field.' );
		
		// Business Type ID
		$business_type_id = $this->createElement ( "hidden", "business_type_id", array (
				'value' => '',
				'filters' => array (
						'StringTrim' 
				) 
		) );
		
		// Name
		$name = $this->createElement ( "text", "name", array (
				'label' => 'Name:',
				'size' => '35',
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
						'Invalid Business Type Name' 
				) 
		) );
		$name->setAttrib("required", "required");
		
		// Submit button
		$submit = $this->addElement ( 'submit', 'submit', array (
				'ignore' => true,
				'class' => "button" 
		) );
		
		// REset button
		$reset = $this->addElement ( 'reset', 'reset', array (
				'ignore' => true,
				'class' => "button"
		) );
		$this->addElements ( array (
				$business_type_id,
				$name,
				$submit,
				$reset
		) );
	}
}