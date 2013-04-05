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
		
		// Keywords
		$keywords = $this->createElement ( "text", "keywords", array (
				'label' => 'Keywords',
				'size' => '128',
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
						'Invalid Keywords'
				)
		) );
		$keywords->setAttrib("required", "required");
		$this->addElement ( $keywords);
		
		$this->addElement('checkbox', 'status', array(
				'label'      => 'Active',
				'value'      => '1'
		));
		
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
				$submit,
				$reset
		) );
	}
}