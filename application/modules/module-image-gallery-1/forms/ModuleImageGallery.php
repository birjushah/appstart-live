<?php 
class ModuleImageGallery1_Form_ModuleImageGallery extends Standard_Form{
    static $lang = null;
	public function init(){
		$this->setMethod('POST');
		$notEmptyValidator = new Zend_Validate_NotEmpty ();
		$notEmptyValidator->setMessage ( 'Enter Valid Value For The Field.' );

		//  Image Gallery Id
		$image_gallery_id = $this->createElement ( "hidden", "module_image_gallery_1_id", array (
				'value' => '',
				'filters' => array (
						'StringTrim'
				)
		) );
		$image_gallery_id->setAttribs(array(
				'id' => 'module_image_gallery_id1',
				'name' => 'data[1][module_image_gallery_1_id]'
		));
		$this->addElement ( $image_gallery_id);
		
		//  Image Gallery Detail Id
		$image_gallery_detail_id = $this->createElement ( "hidden", "module_image_gallery_detail_1_id", array (
				'value' => '',
				'filters' => array (
						'StringTrim'
				)
		) );
		$image_gallery_detail_id->setAttribs(array(
				'id' => 'module_image_gallery_detail_id1',
				'name' => 'data[1][module_image_gallery_detail_1_id]'
		));
		$this->addElement ( $image_gallery_detail_id);
		
		// Language ID
		$language_id = $this->createElement ( "hidden", "language_id", array (
				'filters' => array (
						'StringTrim'
				)
		) );
		$language_id->setAttribs(array(
				'id' => 'language_id1',
				'name' => 'data[1][language_id]'
		));
		$this->addElement ( $language_id );
		
		//Image Gallery Category Id
		$image_gallery_category_id = $this->createElement ( "hidden", "module_image_gallery_category_1_id", array (
				'filters' => array (
						'StringTrim'
				)
		) );
		$image_gallery_category_id->setAttribs(array(
				'id' => 'image_gallery_category_id1',
				'name' => 'data[1][image_gallery_category_1_id]'
		));
		$this->addElement ( $image_gallery_category_id );
		
		//Select Category
		$categories = $this->_getCategories();
		$selectcategory = $this->createElement('select','module_image_gallery_category_1_id',array(
				'label' => 'Select Category',
				'Multioptions' => $categories,
				'validators' => array(
						array(
								$notEmptyValidator,
								true
							)
						)
		));
		$selectcategory->setAttribs(array(
				'required' => 'required',
				'id' => 'module_image_gallery_category_id1',
				'name' => 'data[1][module_image_gallery_category_1_id]'
		));
		$this->addElement($selectcategory);
		// Image Title
		$title = $this->createElement ( "text", "title", array (
				'label' => 'Image Title:',
				'size' => '90',
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
						'Invalid Image Title'
				)
		) );
		$title->setAttribs(array(
				'required' => 'required',
				'id' => 'title1',
				'name' => 'data[1][title]'
		));
		$this->addElement ($title);		
		//Image Path
		$image = $this->createElement('file','image');
		$image->setAttribs(array(
				'id' => 'image1',
				'name' => 'image1'
		));
		$image->setLabel(false)
			 ->setDestination(Standard_Functions::getResourcePath(). "module-image-gallery-1/images/")
			 ->addValidator('Size', false, 10485760)
			 ->addValidator('Extension', false, 'jpeg,jpg,png,gif');
		$this->addElement($image);
		
		//Image Gallery Description
		$description = $this->createElement("textarea","description",array(
				'label' => 'Image Description:',
				'size' => '55',
				'class'=>"mceEditor",
				'filters' => array(
						'StringTrim'
				),
				'errorMessages' => array(
						'Invalid Cms content Description'
				)
		));
		$description->setAttribs(array(
				'id' => 'description1',
				'name' => 'data[1][description]'
		));
		$this->addElement($description);
		
		// Image gallery status
		$status = $this->createElement('checkbox', 'status', array(
				'label'      => 'Active',
				'value'      => '1'
		));
		$status->setAttribs(array(
				'id' => 'status1',
				'name' => 'data[1][status]'
		));
		$this->addElement($status);
		
		//keywords
		$tag = $this->createElement ( "hidden", "tag", array (
				'filters' => array (
						'StringTrim'
				)
		) );
		$tag->setAttribs(array(
				'id' => 'tag1',
				'name' => 'data[1][tag]'
		));
		$this->addElement ( $tag );
		
		// Submit button
		$submit = $this->addElement ( 'submit', 'submit', array (
				'ignore' => true,
				'class' => "button"
		));
		
		// Submit For ALL button
		$allpyall = $this->addElement ( 'button', 'applyall', array (
		        'ignore' => true,
		        'class' => "button",
		        'label' => 'submit to all'
		) );
		
		// Reset button
		$reset = $this->addElement ( 'reset', 'reset', array (
				'ignore' => true,
				'class' => "button"
		) );
		$this->addElements ( array (
				$submit,
				$reset
		) );
		
	}
	public function _getCategories() {
		$active_lang_id = Standard_Functions::getCurrentUser ()->active_language_id;
	    $lang = (self::$lang != null)?self::$lang:$active_lang_id;
	    $customer_id = Standard_Functions::getCurrentUser ()->customer_id;
		$options = array (
				"" => 'Select Category'
		);
		$mapper = new ModuleImageGallery1_Model_Mapper_ModuleImageGalleryCategory1();
		$models = $mapper->fetchAll ("customer_id =".$customer_id);
		if($models){
			foreach($models as $key=>$records){
				$detailMapper = new ModuleImageGallery1_Model_Mapper_ModuleImageGalleryCategoryDetail1();
				$detailModels = $detailMapper->fetchAll("language_id ='".$lang."' AND module_image_gallery_category_1_id =" .$records->getModuleImageGalleryCategory1Id());
				foreach($detailModels as $categories){
					$options[$categories->getModuleImageGalleryCategory1Id()] = $categories->getTitle();
				}
			}
		}
		return $options;
	}	
}