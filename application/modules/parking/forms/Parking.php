<?php
class Parking_Form_Parking extends Standard_Form {
	public function init() {
		$this->setMethod ( 'POST' );
		
		$notEmptyValidator = new Zend_Validate_NotEmpty ();
		$notEmptyValidator->setMessage ( 'Enter Valid Value For The Field.' );
		
		// Module Parking ID
		$module_parking_id = $this->createElement ( "hidden", "module_parking_id", array (
				'value' => '',
				'filters' => array (
						'StringTrim' 
				) 
		) );
		$this->addElement ( $module_parking_id);
		
		// Module Parking Deatil ID
		$module_parking_detail_id = $this->createElement ( "hidden", "module_parking_detail_id", array (
				'value' => '',
				'filters' => array (
						'StringTrim'
				)
		) );
		$this->addElement ( $module_parking_detail_id);
		
		$source = $this->_getSource();
		if(count($source) > 1) {
			$this->addElement('select','module_parking_source_id',array(
					'label'		 => 'Source:',
					'MultiOptions' => $source,
					'validators'	=>	array(
							'NotEmpty'
					),
					'Required'	=>	true
			));
		} else {
			// Module Parking Source ID
			$module_parking_source_id = $this->createElement ( "hidden", "module_parking_source_id", array (
					'label'	=> '',
					'value' => key($source),
					'filters' => array (
							'StringTrim'
					)
			) );
			$this->addElement ( $module_parking_source_id);
		}
		
		// Language ID
		$language_id = $this->createElement ( "hidden", "language_id", array (
				'value' => '',
				'filters' => array (
						'StringTrim'
				)
		) );
		$this->addElement ( $language_id );
		
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
						'Invalid Title' 
				) 
		) );
		$name->setAttrib("required", "required");
		$this->addElement ( $name);
		
		// Total Capacity
		$total_capacity = $this->createElement ( "text", "total_capacity", array (
				'label' => 'Total Capacity:',
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
		$total_capacity->setAttrib("required", "required");
		$this->addElement ( $total_capacity);
		
		// image
		
		$icon = $this->createElement('file','icon');
		$icon->setLabel('Icon:')
			 ->setDestination(Standard_Functions::getResourcePath(). "parking/uploaded-icons")
			 ->addValidator('Size', false, 102400)
			 ->addValidator('Extension', false, 'jpg,png,gif');
		$this->addElement($icon);
		
		//Address
		$address = $this->createElement('textarea', 'address',array(
				'label' => 'Address',
				'cols' => '150',
				'rows' => '15',
				'value' => '',
				'filters' => array(
						'StringTrim'
						)
				)
		);
		$this->addElement($address);
		
		$this->addElement('multiselect','parking_type',array(
				'label'		 => 'Parking Types:',
				'MultiOptions' => $this->_getParkingType(),
				'validators'	=>	array(
						'NotEmpty'
				),
				'Required'	=>	true
		));
		
		// Source Reference ID
		$source_reference_id = $this->createElement('text', 'source_reference_id',array(
				'label' => 'Reference ID:',
				'value' => '',
				'filters' => array(
						'StringTrim'
				)
		)
		);
		$this->addElement($source_reference_id);

		//Latitude
		$latitude = $this->createElement('text', 'latitude',array(
				'label' => 'Latitude',
				'value' => '',
				'filters' => array(
						'StringTrim'
						)
				)
		);
		$this->addElement($latitude);

		//Longitude
		$longitude = $this->createElement('text', 'longitude',array(
				'label' => 'Longitude',
				'value' => '',
				'filters' => array(
						'StringTrim'
						)
				)
		);
		$this->addElement($longitude);

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
	
	public function _getParkingType()
	{
		$options = array();
		$active_lang_id = Standard_Functions::getCurrentUser ()->active_language_id;
		
		$mapper = new Parking_Model_Mapper_ModuleParkingType();
		$models = $mapper->fetchAll();
		if($models) {
			foreach($models as $types) {
				$mapperDetail = new Parking_Model_Mapper_ModuleParkingTypeDetail();
				$modelDetail = $mapperDetail->fetchAll("module_parking_type_id = ".$types->getModuleParkingTypeId(). " AND language_id=".$active_lang_id);
				
				if(is_array($modelDetail) && is_object($modelDetail[0])) {
					$options[$types->getModuleParkingTypeId()] = $modelDetail[0]->getTitle();
				}
			}
		}
		return $options;
	}
	
	public function _getSource()
	{
		$options = array();
		$active_lang_id = Standard_Functions::getCurrentUser ()->active_language_id;
		$customer_id = Standard_Functions::getCurrentUser ()->customer_id;
		
		$mapper = new Parking_Model_Mapper_ModuleParkingCustomerSource();
		$models = $mapper->fetchAll("customer_id=".$customer_id);
		if($models) {
			foreach($models as $types) {
				$mapperDetail = new Parking_Model_Mapper_ModuleParkingSourceDetail();
				$modelDetail = $mapperDetail->fetchAll("module_parking_source_id = ".$types->getModuleParkingSourceId(). " AND language_id=".$active_lang_id);
	
				if(is_array($modelDetail) && is_object($modelDetail[0])) {
					$options[$types->getModuleParkingSourceId()] = $modelDetail[0]->getTitle();
				}
			}
		}
		return $options;
	}
}