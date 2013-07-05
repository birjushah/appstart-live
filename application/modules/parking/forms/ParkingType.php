<?php
class Parking_Form_ParkingType extends Standard_Form {
	public function init() {
		$this->setMethod ( 'POST' );
		
		$notEmptyValidator = new Zend_Validate_NotEmpty ();
		$notEmptyValidator->setMessage ( 'Enter Valid Value For The Field.' );
		
		// Module Parking Type ID
		$module_parking_type_id = $this->createElement ( "hidden", "module_parking_type_id", array (
				'value' => '',
				'filters' => array (
						'StringTrim' 
				) 
		) );
		$this->addElement ( $module_parking_type_id );
		
		// Module Parking Type Deatil ID
		$module_parking_type_detail_id = $this->createElement ( "hidden", "module_parking_type_detail_id", array (
				'value' => '',
				'filters' => array (
						'StringTrim'
				)
		) );
		$this->addElement ( $module_parking_type_detail_id);
		
		// Language ID
		$language_id = $this->createElement ( "hidden", "language_id", array (
				'value' => '',
				'filters' => array (
						'StringTrim'
				)
		) );
		$this->addElement ( $language_id );
		
		// Title
		$title = $this->createElement ( "text", "title", array (
				'label' => 'Title:',
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
						'Invalid Title' 
				) 
		) );
		$title->setAttrib("required", "required");
		$this->addElement ( $title );
		
		// image
		$icon = $this->createElement('file','icon');
		$icon->setLabel('Icon:')
			 ->setDestination(Standard_Functions::getResourcePath(). "parking/uploaded-icons")
			 ->addValidator('Size', false, 102400)
			 ->addValidator('Extension', false, 'jpg,png,gif');
		$this->addElement($icon);
		
		// Status
		$this->addElement('checkbox', 'status', array(
				'label'      => 'Active',
				'value'      => '1'
		));
		
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
		
		// REset button
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