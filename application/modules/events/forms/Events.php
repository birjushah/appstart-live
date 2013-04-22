<?php
class Events_Form_Events extends Standard_Form {
	public function init() {
		$this->setMethod ( 'POST' );
		
		$notEmptyValidator = new Zend_Validate_NotEmpty ();
		$notEmptyValidator->setMessage ( 'Enter Valid Value For The Field.' );
		
		// Module Events ID
		$module_events_id = $this->createElement ( "hidden", "module_events_id", array (
				'value' => '',
				'filters' => array (
						'StringTrim' 
				) 
		) );
		$this->addElement ( $module_events_id);
		
		// Module Events Detail ID
		$module_events_detail_id = $this->createElement ( "hidden", "module_events_detail_id", array (
				'value' => '',
				'filters' => array (
						'StringTrim'
				)
		) );
		$this->addElement ( $module_events_detail_id);
		
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
		$this->addElement ( $title);
		
		// Description
		$description = $this->createElement ( "textarea", "description", array (
				'label' => 'Description:',
				'id' => 'ta1',
				'size' => '128',
				'filters' => array (
						'StringTrim' 
				) 
		) );
		$this->addElement ( $description );
		
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
		
		// image
		
		$image = $this->createElement('file','image');
		$image->setLabel('Image:')
			 ->setDestination(Standard_Functions::getResourcePath(). "events/images")
			 ->addValidator('Size', false, 102400)
			 ->addValidator('Extension', false, 'jpg,png,gif');
		$this->addElement($image);
		
		//Address
		$address = $this->createElement('textarea', 'address',array(
				'label' => 'Address',
				'cols' => '150',
				'rows' => '15',
				'value' => '{{addressValue}}',
				'filters' => array(
						'StringTrim'
						)
				)
		);
		$address->setIsArray(true);
		$this->addElement($address);

		//Plz
		$plz = $this->createElement('text', 'plz',array(
				'label' => 'Plz',
				'value' => '{{plzValue}}',
				'filters' => array(
						'StringTrim'
						)
				)
		);
		$plz->setIsArray(true);
		$this->addElement($plz);

		//City
		$city = $this->createElement('text', 'city',array(
				'label' => 'City',
				'value' => '{{cityValue}}',
				'filters' => array(
						'StringTrim'
						)
				)
		);
		$city->setIsArray(true);
		$this->addElement($city);

		//Country
		$countryandcontinents =  Zend_Locale::getTranslationList('Territory','en');
		asort($countryandcontinents);
		$countries[""]="Select Country";
		foreach($countryandcontinents as $key=>$value){
			if(is_numeric($key)) continue;
			$countries[$value] = $value;
		}
		//array_unshift($countries, array("Select Country"=>"Select Country"));
		$country = $this->createElement('select','country',array(
				'label' => 'Country',
				'value' => '{{countryValue}}',
				'multiple' => false,
				'Multioptions' => $countries
		));
		$country->setIsArray(true);
		$this->addElement($country);
		
		//Location
		$location = $this->createElement('text', 'location',array(
				'label' => 'Location',
				'value' => '{{locationValue}}',
				'filters' => array(
						'StringTrim'
						)
				)
		);
		$location->setIsArray(true);
		$this->addElement($location);

		//Latitude
		$latitude = $this->createElement('text', 'latitude',array(
				'label' => 'Latitude',
				'value' => '{{latitudeValue}}',
				'filters' => array(
						'StringTrim'
						)
				)
		);
		$latitude->setIsArray(true);
		$this->addElement($latitude);

		//Longitude
		$longitude = $this->createElement('text', 'longitude',array(
				'label' => 'Longitude',
				'value' => '{{longitudeValue}}',
				'filters' => array(
						'StringTrim'
						)
				)
		);
		$longitude->setIsArray(true);
		$this->addElement($longitude);

		//notes
		$notes = $this->createElement("textarea", 'notes',array(
				'label' => 'Notes',
				'id' => 'ta2',
				'size' => '90',
				'filters' => array(
						'StringTrim'
						)
				)
		);
		$this->addElement($notes);
		
		// //Recurrence
		// $this->addElement('select','recurrence',array(
		// 		'label'		 => 'Recurrence:',
		// 		'MultiOptions' => $this->_getRecurrence(),
		// 		'validators'	=>	array(
		// 				'NotEmpty'
		// 		),
		// 		'Required'	=>	true
		// ));
		
		// // stop_by
		// $this->addElement('select','stop_by',array(
		// 		'label'		 => 'Stop By:',
		// 		'MultiOptions' => $this->_getStopBy(),
		// 		'validators'	=>	array(
		// 				'NotEmpty'
		// 		),
		// 		'Required'	=>	true
		// ));
		
		// // Stop At
		// $stop_at = $this->createElement ( "hidden", "stop_at", array (
		// 		'value' => '1',
		// 		'filters' => array (
		// 				'StringTrim' 
		// 		) 
		// ) );
		// $this->addElement ( $stop_at);
		
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
	
	// private function _getRecurrence() {
	// 	$options = array(
	// 					"Daily"=>"Daily",
	// 					"Weekly"=>"Weekly",
	// 					"Fortnightly"=>"Fortnightly",
	// 					"Monthly"=>"Monthly",
	// 					"Quarterly"=>"Quarterly",
	// 					"Half Yearly"=>"Half Yearly",
	// 					"Yearly"=>"Yearly");
	// 	return $options;
	// }
	// private function _getStopBy() {
	// 	$options = array(
	// 			"1"=>"Ocurrence",
	// 			"2"=>"Day");
	// 	return $options;
	// }
}