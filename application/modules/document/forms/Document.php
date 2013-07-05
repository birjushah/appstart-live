<?php
class Document_Form_Document extends Standard_Form {
	public function init() {
		$this->setMethod ( 'POST' );
		
		$notEmptyValidator = new Zend_Validate_NotEmpty ();
		$notEmptyValidator->setMessage ( 'Enter Valid Value For The Field.' );
		
		// Module Document ID
		$module_document_id = $this->createElement ( "hidden", "module_document_id", array (
				'value' => '',
				'filters' => array (
						'StringTrim' 
				) 
		) );
		$this->addElement ( $module_document_id);
		
		// Module Document Category ID
		$module_document_id = $this->createElement ( "hidden", "module_document_category_id", array (
				'value' => '',
				'filters' => array (
						'StringTrim'
				)
		) );
		$this->addElement ( $module_document_id);
		
		// Module Document Detail ID
		$module_document_detail_id = $this->createElement ( "hidden", "module_document_detail_id", array (
				'value' => '',
				'filters' => array (
						'StringTrim'
				)
		) );
		$this->addElement ( $module_document_detail_id);
		
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
				'label' => 'Title',
				'size' => '60',
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
		
		//Icon
		$icon = $this->createElement ( 'file', 'icon' );
		$icon->setLabel ( 'Icon:' )->setDestination ( Standard_Functions::getResourcePath () . "document/uploaded-icons" )->addValidator ( 'Size', false, 102400 )->addValidator ( 'Extension', false, 'jpg,png,gif' );
		$this->addElement ( $icon );
		
		$this->addElement ( 'checkbox', 'status', array (
				'label' => 'Active',
				'value' => '1' 
		) );
		
		// Description
		$description = $this->createElement ( "textarea", "description", array (
				'label' => 'Description',
				'size' => '255',
				'filters' => array (
						'StringTrim' 
				) 
		) );
		$this->addElement ( $description );
		
		// Document Path
		
		$document = $this->createElement('file','document');
		$document->setLabel('Document')
			 ->setDestination(Standard_Functions::getResourcePath(). "document/uploads")
			 ->addValidator('Size', false, 102400);
		$this->addElement($document);
		
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
		$allpyall = $this->addElement ( 'button', 'applyalldoc', array (
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