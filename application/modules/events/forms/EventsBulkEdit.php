<?php
class Events_Form_EventsBulkEdit extends Standard_Form {
	public function init() {
		$this->setMethod ( 'POST' );
		
		$notEmptyValidator = new Zend_Validate_NotEmpty ();
		$notEmptyValidator->setMessage ( 'Enter Valid Value For The Field.' );
		
		// Module Events ID
		// Language ID
		$language_id = $this->createElement ( "multiselect", "language_id", array (
				'label'		 => 'Language:',
		 		'MultiOptions' => $this->_getLanguages(),
		 		'validators'	=>	array(
		 				'NotEmpty'
		 		),
		 		'Required'	=>	true
		) );
		$this->addElement ( $language_id );
		
		// Start Date/Time
		$startDate = $this->createElement ( "text", "start_date_time", array (
				'label' => 'Start Date/Time:',
				'size' => '30',
				'filters' => array (
						'StringTrim'
				)
		) );
		$this->addElement ( $startDate);
		
		// End Date/Time
		$endDate = $this->createElement ( "text", "end_date_time", array (
				'label' => 'End Date/Time:',
				'size' => '30',
				'filters' => array (
						'StringTrim'
				)
		) );
		$this->addElement ( $endDate);
		
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
				$submit
		) );
	}
	private function _getLanguages() {
		$types = array();
		$mapper = new Admin_Model_Mapper_CustomerLanguage();
		$select = $mapper->getDbTable ()->
						select ( false )->
						setIntegrityCheck ( false )->
						from ( array ("l" => "language"), array (
								"l.language_id" => "language_id",
								"l.title" => "title") )->
						joinLeft ( array ("cl" => "customer_language"), "l.language_id = cl.language_id",
										array ("cl.customer_id") )->
						where("cl.customer_id=".Standard_Functions::getCurrentUser ()->customer_id);
		$languages = $mapper->getDbTable ()->fetchAll($select)->toArray();
		if($languages) {
			foreach ($languages as $lang) {
				$types[$lang["l.language_id"]] = $lang["l.title"];
			}
		}
		return $types;
	}
}