<?php
class Events_Form_EventsCategory extends Standard_Form {
	public function init() {
		$this->setMethod ( 'POST' );
		
		$notEmptyValidator = new Zend_Validate_NotEmpty ();
		$notEmptyValidator->setMessage ( 'Enter Valid Value For The Field.' );
		
		// Module Events ID
		$module_events_category_id = $this->createElement ( "hidden", "module_events_category_id", array (
				'value' => '',
				'filters' => array (
						'StringTrim' 
				) 
		) );
		$this->addElement ( $module_events_category_id);
		
		// Module Events Detail ID
		$module_events_category_detail_id = $this->createElement ( "hidden", "module_events_category_detail_id", array (
				'value' => '',
				'filters' => array (
						'StringTrim'
				)
		) );
		$this->addElement ( $module_events_category_detail_id );
		
		// Language ID
		$language_id = $this->createElement ( "hidden", "language_id", array (
				'value' => '',
				'filters' => array (
						'StringTrim'
				)
		) );
		$this->addElement ( $language_id );
		
		//Parent ID
		$parent_id = $this->createElement ( "hidden", "parent_id", array (
				'filters' => array (
						'StringTrim'
				)
		) );
		$this->addElement ( $parent_id );
		
		// Title
		$title = $this->createElement ( "text", "title", array (
				'label' => 'Title',
				'size' => '50',
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
		$this->addElement ( $title);
		
		$this->addElement('checkbox', 'status', array(
				'label'      => 'Active',
				'value'      => '1'
		));
		
		// Color
		$color = $this->createElement ( "text", "color", array (
				'label' => 'Color:',
				'size' => '6',
				'maxlength'=> "6",
				'filters' => array (
						'StringTrim'
				)
		) );
		$this->addElement ( $color );
		
		// Submit button
		$submit = $this->addElement ( 'submit', 'submit', array (
				'ignore' => true,
				'class' => "button" 
		) );
		
		// Submit For ALL button
		$allpyall = $this->addElement ( 'button', 'applyallcat', array (
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