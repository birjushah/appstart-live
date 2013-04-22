<?php
class Music_Form_Music extends Standard_Form {
	public function init() {
		$this->setMethod ( 'POST' );
		
		$notEmptyValidator = new Zend_Validate_NotEmpty ();
		$notEmptyValidator->setMessage ( 'Enter Valid Value For The Field.' );
		
		// Module Music ID
		$module_music_id = $this->createElement ( "hidden", "module_music_id", array (
				'value' => '',
				'filters' => array (
						'StringTrim' 
				) 
		) );
		$this->addElement ( $module_music_id);
		
		// Module Music Detail ID
		$module_music_detail_id = $this->createElement ( "hidden", "module_music_detail_id", array (
				'value' => '',
				'filters' => array (
						'StringTrim'
				)
		) );
		$this->addElement ( $module_music_detail_id);
		
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
				'size' => '120',
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
		
		// Artist
		$artist = $this->createElement ( "text", "artist", array (
				'label' => 'Artist:',
				'size' => '120',
				'filters' => array (
						'StringTrim'
				)
		) );
		$this->addElement ( $artist);
		
		// Album
		$album = $this->createElement ( "text", "album", array (
				'label' => 'Album:',
				'size' => '120',
				'filters' => array (
						'StringTrim'
				)
		) );
		$this->addElement ( $album );
		
		// Purchase URL
		$track_url = $this->createElement ( "text", "track_url", array (
				'label' => 'Purchase URL:',
				'size' => '120',
				'filters' => array (
						'StringTrim'
				)
		) );
		$this->addElement ( $track_url );
		
		// Preview
		$preview = $this->createElement('file','preview');
		$preview->setLabel('Track:')
				 ->setDestination(Standard_Functions::getResourcePath(). "music/tracks")
				 ->addValidator('Size', false, 1024000);
		$this->addElement($preview);
		
		// Album Art
		$album = $this->createElement('file','album_art');
		$album->setLabel('Album Art:')
				->setDestination(Standard_Functions::getResourcePath(). "music/images")
				->addValidator('Size', false, 1024000)
				->addValidator('Extension', false, 'jpg,png,gif');
		$this->addElement($album);
		
		//checkbox
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