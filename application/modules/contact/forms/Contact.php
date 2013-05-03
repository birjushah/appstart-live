<?php
class Contact_Form_Contact extends Standard_Form {
	public function init() {
		$this->setMethod ( 'POST' );
		
		$notEmptyValidator = new Zend_Validate_NotEmpty ();
		$notEmptyValidator->setMessage ( 'Enter Valid Value For The Field.' );
		
		// Contact ID
		$contact_id = $this->createElement ( "hidden", "contact_id", array (
				'value' => '',
				'filters' => array (
						'StringTrim' 
				) 
		) );
		$this->addElement ( $contact_id );
		
		// Contact Detail ID
		$contact_detail_id = $this->createElement ( "hidden", "contact_detail_id", array (
				'value' => '',
				'filters' => array (
						'StringTrim' 
				) 
		) );
		$this->addElement ( $contact_detail_id );
		
		// Language ID
		$language_id = $this->createElement ( "hidden", "language_id", array (
				'value' => '',
				'filters' => array (
						'StringTrim' 
				) 
		) );
		$this->addElement ( $language_id );
		
		// Location
		$location = $this->createElement ( "text", "location", array (
				'label' => 'Location:',
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
						'Invalid Location' 
				) 
		) );
		$location->setAttrib ( "required", "required" );
		$this->addElement ( $location );
		
		// Address
		$address = $this->createElement ( "textarea", "address", array (
				'label' => 'Address:',
				'size' => '90',
				'filters' => array (
						'StringTrim' 
				) 
		) );
		$this->addElement ( $address );

		//PLZ
		$plz = $this->createElement ( "text", "plz", array (
				'label' => 'PLZ:',
				'size' => '20',
				'filters' => array (
						'StringTrim' 
				) 
		) );
		$this->addElement ( $plz );
		
		//country
		$countryandcontinents =  Zend_Locale::getTranslationList('Territory','en');
		asort($countryandcontinents);
		$countries[""]="Select Country";
		foreach($countryandcontinents as $key=>$value){
			if(is_numeric($key)) continue;
			$countries[$value] = $value;
		}
		//array_unshift($countries, array("Select Country");
		$country = $this->createElement('select','country',array(
				'label' => 'Country:',
				'Multioptions' => $countries,
		));
		$this->addElement($country);

		//City
		$city = $this->createElement ( "text", "city", array (
				'label' => 'City:',
				'size' => '20',
				'filters' => array (
						'StringTrim' 
				) 
		) );
		$this->addElement ( $city );

		// Phone 1
		$phone_1 = $this->createElement ( "text", "phone_1", array (
				'label' => 'Phone 1:',
				'size' => '20',
				'filters' => array (
						'StringTrim' 
				) 
		) );
		$this->addElement ( $phone_1 );
		
		// Phone 1
		$phone_2 = $this->createElement ( "text", "phone_2", array (
				'label' => 'Phone 2:',
				'class' => 'hidden',
				'size' => '20',
				'filters' => array (
						'StringTrim' 
				) 
		) );
		$this->addElement ( $phone_2 );
		
		// Phone 3
		$phone_3 = $this->createElement ( "text", "phone_3", array (
				'label' => 'Phone 3:',
				'class' => 'hidden',
				'size' => '20',
				'filters' => array (
						'StringTrim' 
				) 
		) );
		$this->addElement ( $phone_3 );
		
		// fax
		$fax = $this->createElement ( "text", "fax", array (
				'label' => 'Fax:',
				'size' => '20',
				'filters' => array (
						'StringTrim' 
				) 
		) );
		$this->addElement ( $fax );
		
		// Latitude
		$latitude = $this->createElement ( "text", "latitude", array (
				'label' => 'Latitude:',
				'size' => '20',
				'filters' => array (
						'StringTrim' 
				) 
		) );
		$this->addElement ( $latitude );
		
		// Longitude
		$longitude = $this->createElement ( "text", "longitude", array (
				'label' => 'Longitude:',
				'size' => '20',
				'filters' => array (
						'StringTrim' 
				) 
		) );
		$this->addElement ( $longitude );
		
		// Email 1
		$email_1 = $this->createElement ( "text", "email_1", array (
				'label' => 'Email 1:',
				'size' => '35',
				'filters' => array (
						'StringTrim' 
				) 
		) );
		$this->addElement ( $email_1 );
		
		// Email 2
		$email_2 = $this->createElement ( "text", "email_2", array (
				'label' => 'Email 2:',
				'size' => '35',
				'filters' => array (
						'StringTrim' 
				) 
		) );
		$this->addElement ( $email_2 );
		
		// Email 3
		$email_3 = $this->createElement ( "text", "email_3", array (
				'label' => 'Email 3:',
				'size' => '35',
				'filters' => array (
						'StringTrim' 
				) 
		) );
		$this->addElement ( $email_3 );
		
		// Website
		$website = $this->createElement ( "text", "website", array (
				'label' => 'Website:',
				'size' => '35',
				'style' => 'width: 465px;',
				'filters' => array (
						'StringTrim' 
				) 
		) );
		$this->addElement ( $website );
		
		// Timings

		// logo
		
		$logo = $this->createElement ( 'file', 'logo' );
		$logo->setLabel ( 'Logo:' )->setDestination ( Standard_Functions::getResourcePath () . "contact/images" )->addValidator ( 'Size', false, 102400 )->addValidator ( 'Extension', false, 'jpg,png,gif' );
		$this->addElement ( $logo );
		
		$this->addElement ( 'checkbox', 'status', array (
				'label' => 'Active:',
				'value' => '1' 
		) );
		
		//Information
		$information = $this->createElement('textarea', 'information',array(
				'label' => "Information:",
				'id' => "content_textarea",
				'filters' => array (
						'StringTrim'
				)
		));
		$this->addElement($information);
		
		// Submit button
		$submit = $this->addElement ( 'submit', 'submit', array (
				'ignore' => true,
				'class' => "button",
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