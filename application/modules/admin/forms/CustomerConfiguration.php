<?php
class Admin_Form_CustomerConfiguration extends Standard_Form {
	public function init() {
		$this->setMethod ( 'POST' );
		
		// Customer Configuration ID
		$customer_configuration_id = $this->createElement ( "hidden", "customer_configuration_id", array (
				'value' => '',
				'filters' => array (
						'StringTrim' 
				) 
		) );
		$this->addElement ( $customer_configuration_id );
		
		// Customer  ID
		$customer_id = $this->createElement ( "hidden", "customer_id", array (
				'value' => '',
				'filters' => array (
						'StringTrim'
				)
		) );
		$this->addElement ( $customer_id );
		
		// Font Type
		$font_type = $this->createElement ( "select", "font_type", array (
				'label' => 'Font Type:',
				'MultiOptions' => $this->_getFontType (),
				'filters' => array (
						'StringTrim' 
				) 
		) );
		$this->addElement ( $font_type );
		
		// Font Color
		$font_color = $this->createElement ( "text", "font_color", array (
				'label' => 'Font Color:',
				'size' => '6',
				'maxlength' => '6',
				'filters' => array (
						'StringTrim' 
				) 
		) );
		$this->addElement ( $font_color );
		
		// Font Size
		$font_size = $this->createElement ( "text", "font_size", array (
				'label' => 'Font Size:',
				'size' => '15',
				'filters' => array (
						'StringTrim' 
				) 
		) );
		$this->addElement ( $font_size );
		
		// Header Font Type
		$header_font_type = $this->createElement ( "select", "header_font_type", array (
		        'label' => 'Header Font Type:',
		        'MultiOptions' => $this->_getFontType (),
		        'filters' => array (
		                'StringTrim'
		        )
		) );
		$this->addElement ( $header_font_type );
		
		//Header Font Size
		$header_font_size = $this->createElement ( "text", "header_font_size", array (
		        'label' => 'Header Font Size:',
		        'size' => '15',
		        'filters' => array (
		                'StringTrim'
		        )
		) );
		$this->addElement ( $header_font_size );
		
		//Header Color
		$header_color = $this->createElement ( "text", "header_color", array (
		        'label' => 'Header Color:',
		        'size' => '6',
				'maxlength' => '6',
		        'filters' => array (
		                'StringTrim'
		        )
		) );
		$this->addElement ( $header_color );
		
		//Header Font Color
		$header_font_color = $this->createElement ( "text", "header_font_color", array (
		        'label' => 'Header Font Color:',
		        'size' => '6',
				'maxlength' => '6',
		        'filters' => array (
		                'StringTrim'
		        )
		) );
		$this->addElement ( $header_font_color );
		
		// List Font Type
		$list_font_type = $this->createElement ( "select", "list_font_type", array (
		        'label' => 'ListView Font Type:',
		        'MultiOptions' => $this->_getFontType (),
		        'filters' => array (
		                'StringTrim'
		        )
		) );
		$this->addElement ( $list_font_type );
		
		//List Font Color
		$list_font_color = $this->createElement ( "text", "list_font_color", array (
		        'label' => 'ListView Font Color:',
		        'size' => '6',
				'maxlength' => '6',
		        'filters' => array (
		                'StringTrim'
		        )
		) );
		$this->addElement ( $list_font_color );
		
		//List Background Color
		$list_background_color = $this->createElement ( "text", "list_background_color", array (
		        'label' => 'ListView Background Color:',
		        'size' => '6',
				'maxlength' => '6',
		        'filters' => array (
		                'StringTrim'
		        )
		) );
		$this->addElement ( $list_background_color );
		
		//List Font Size
		$list_font_size = $this->createElement ( "text", "list_font_size", array (
		        'label' => 'ListView Font Size:',
		        'size' => '15',
		        'filters' => array (
		                'StringTrim'
		        )
		) );
		$this->addElement ( $list_font_size );
		
		
		// Spacing
		$spacing = $this->createElement ( "text", "spacing", array (
				'label' => 'Spacing:',
				'size' => '15',
				'filters' => array (
						'StringTrim' 
				) 
		) );
		$this->addElement ( $spacing );
		
		// Theme Color
		$theme_color_color = $this->createElement ( "text", "theme_color", array (
				'label' => 'Theme Color:',
				'size' => '6',
				'maxlength' => '6',
				'filters' => array (
						'StringTrim'
				)
		) );
		$this->addElement ( $theme_color_color );
		
		// Sepaprator Color
		$separator = $this->createElement ( "text", "separator_color", array (
		        'label' => 'Line Separator Color:',
		        'size' => '6',
				'maxlength' => '6',
		        'filters' => array (
		                'StringTrim'
		        )
		) );
		
		$this->addElement ( $separator );
		
		// List Gradient Top
		$list_top = $this->createElement ( "text", "list_gradient_top", array (
		        'label' => 'List View Gradient  (top):',
		        'size' => '6',
				'maxlength' => '6',
		        'filters' => array (
		                'StringTrim'
		        )
		) );
		
		$this->addElement ( $list_top );
		
		// List Gradient Middle
		$list_middle= $this->createElement ( "text", "list_gradient_middle", array (
		        'label' => 'List View Gradient  (middle):',
		        'size' => '6',
				'maxlength' => '6',
		        'filters' => array (
		                'StringTrim'
		        )
		) );
		
		$this->addElement ( $list_middle );
		
		// List Gradient Bottom
		$list_bottom = $this->createElement ( "text", "list_gradient_bottom", array (
		        'label' => 'List View Gradient  (bottom):',
		        'size' => '6',
				'maxlength' => '6',
		        'filters' => array (
		                'StringTrim'
		        )
		) );
		
		$this->addElement ( $list_bottom );
		
		// Image Gallery Limit
		$imagegallerylimit = $this->createElement ( "text", "imagegallery_limit", array (
		        'label' => 'Image Gallery Limit:',
		        'size' => '15',
		        'filters' => array (
		                'StringTrim'
		        )
		) );
		$this->addElement ( $imagegallerylimit );
		
		// Document Limit
		$documentlimit = $this->createElement ( "text", "document_limit", array (
		        'label' => 'Document Limit:',
		        'size' => '15',
		        'filters' => array (
		                'StringTrim'
		        )
		) );
		$this->addElement ( $documentlimit );
		
		// Home Wallpaper Limit
		$homewallpaperlimit = $this->createElement ( "text", "homewallpaper_limit", array (
		        'label' => 'Home Wallpaper Limit:',
		        'size' => '15',
		        'filters' => array (
		                'StringTrim'
		        )
		) );
		$this->addElement ( $homewallpaperlimit );
		
		//Invert
		$this->addElement('checkbox', 'invert', array(
		        'label'      => 'Invert more',
		        'value'      => '0'
		));
		
		// Submit Button
		$submit = $this->createElement ( 'submit', 'submit', array (
				'ignore' => true 
		) );
		$this->addElement ( $submit );
		
		// Reset Button
		$reset = $this->createElement ( 'reset', 'reset', array (
				'ignore' => true 
		) );
		$this->addElement ( $reset );
	}
	function _getFontType ()
	{
		return array(
				''=>'',
				'Helvetica'=>'Helvetica',
				'Helvetica new'=>'Helvetica new',
				'Courier'=>'Courier',
				'Georgia'=>'Georgia',
				'Times New Roman'=>'Times New Roman',
				'Trebuchet MS'=>'Trebuchet MS',
				'Verdana'=>'Verdana',
				'HelveticaNeueLTStd' => 'HelveticaNeueLTStd'
				);
	}
}