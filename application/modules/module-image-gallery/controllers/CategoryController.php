<?php
class ModuleImageGallery_CategoryController extends Zend_Controller_Action {
	var $_module_id;
	public function init() {
		/* Initialize Action Controller Here.. */
		$modulesMapper = new Admin_Model_Mapper_Module ();
		$module = $modulesMapper->fetchAll ( "name ='module-image-gallery'" );
		if (is_array ( $module )) {
			$this->_module_id = $module [0]->getModuleId ();
		}
	}
	
	public function indexAction() {
		$active_lang_id = Standard_Functions::getCurrentUser ()->active_language_id;
		$this->view->addlink = $this->view->url ( array (
				"module" => "module-image-gallery",
				"controller" => "category",
				"action" => "add"
		), "default", true );
		$this->view->reorderlink = $this->view->url ( array (
				"module" => "module-image-gallery",
				"controller" => "category",
				"action" => "reorder"
		), "default", true );
	}
	
	public function addAction(){
		$form = new ModuleImageGallery_Form_Category();
		$action = $this->view->url ( array (
				"module" => "module-image-gallery",
				"controller" => "Category",
				"action" => "save"
		), "default", true );
		$form->setAction($action);
		$form->setMethod ( 'POST' );
		$this->view->assign ( array (
				"partial" => "category/partials/add.phtml"
		) );
		$this->view->form = $form;
		$this->render("add-edit");
	}
	
	public function editAction(){
		$form = new ModuleImageGallery_Form_Category ();
		$action = $this->view->url ( array (
				"module" => "module-image-gallery",
				"controller" => "category",
				"action" => "save"
		), "default", true );
		$form->setAction($action);
		$form->setMethod ( 'POST' );
		$this->view->form = $form;
		$request = $this->getRequest ();
		if ($request->getParam ( "id", "" ) != "" && $request->getParam ( "lang", "" ) != "") {
			$mapper = new ModuleImageGallery_Model_Mapper_ModuleImageGalleryCategory ();
			$module_image_gallery_category_id = $request->getParam ( "id", "" );
			$language_id = $request->getParam ( "lang", "" );
			$customer_id = Standard_Functions::getCurrentUser ()->customer_id;
			$languageMapper = new Admin_Model_Mapper_Language ();
			$languageData = $languageMapper->find ( $language_id );
			$this->view->language = $languageData->getTitle ();
			$default_lang_id = Standard_Functions::getCurrentUser ()->default_language_id;
			$data = $mapper->find($module_image_gallery_category_id)->toArray ();
			$form->populate ( $data );
			$datadetails = array ();
			$detailsMapper = new ModuleImageGallery_Model_Mapper_ModuleImageGalleryCategoryDetail ();
			if ($detailsMapper->countAll ( "module_image_gallery_category_id = " . $module_image_gallery_category_id . " AND language_id = " . $language_id ) > 0) {
				$dataDetails = $detailsMapper->getDbTable ()->fetchAll ( "module_image_gallery_category_id = " . $module_image_gallery_category_id . " AND language_id = " . $language_id )->toArray ();
			}
			else {
				// Record For Language Not Found
				$dataDetails = $detailsMapper->getDbTable ()->fetchAll ( "module_image_gallery_category_id = " . $module_image_gallery_category_id . " AND language_id = " . $default_lang_id )->toArray ();
				$dataDetails [0] ["module_image_gallery_category_id"] = "";
				$dataDetails [0] ["language_id"] = $language_id;
				//$this->view->category = $dataDetails[0]['title'];
			}
			if (isset ( $dataDetails [0] ) && is_array ( $dataDetails [0] )) {
				$form->populate ( $dataDetails [0] );
			}
			$action = $this->view->url ( array (
					"module" => "module-image-gallery",
					"controller" => "category",
					"action" => "save",
					"id" => $request->getParam ( "id", "" )
			), "default", true );
			$form->setAction ( $action );
			} else {
				$this->_redirect ( '/' );
			}
			$this->view->form = $form;
			$this->view->assign ( array (
					"partial" => "category/partials/edit.phtml"
			) );
		$this->render ( "add-edit" );
	}
	public function saveAction(){
		$this->_helper->layout ()->disableLayout ();
		$this->_helper->viewRenderer->setNoRender ();
		$form = new ModuleImageGallery_Form_Category();
		$request = $this->getRequest ();
		$response = array ();
		if ($this->_request->isPost ()) {
			if ($form->isValid ( $this->_request->getParams () )) {
				try{
				$allFormValues = $form->getValues();
				$customer_id = Standard_Functions::getCurrentUser ()->customer_id;
				$user_id = Standard_Functions::getCurrentUser ()->user_id;
				$date_time = Standard_Functions::getCurrentDateTime ();
				$mapper = new ModuleImageGallery_Model_Mapper_ModuleImageGalleryCategory ();
				$mapper->getDbTable()->getAdapter()->beginTransaction();
				$model = new ModuleImageGallery_Model_ModuleImageGalleryCategory ( $allFormValues );
				if ($request->getParam ( "id", "" ) == "") {
					// Add new category
					$maxOrder = $mapper->getNextOrder ( $customer_id );
					$model->setOrder ( $maxOrder + 1 );
					$model->setCustomerId ( $customer_id );
					$model->setCreatedBy ( $user_id );
					$model->setCreatedAt ( $date_time );
					$model->setLastUpdatedBy ( $user_id );
					$model->setLastUpdatedAt ( $date_time );
					$model = $model->save ();
					// Save image Details
					$module_image_gallery_category_id = $model->get ( "module_image_gallery_category_id" );
					$mapperLanguage = new Admin_Model_Mapper_CustomerLanguage ();
					$modelLanguages = $mapperLanguage->fetchAll ( "customer_id = " . $customer_id );
					if (is_array ( $modelLanguages )) {
						foreach ( $modelLanguages as $languages ) {
							$modelDetails = new ModuleImageGallery_Model_ModuleImageGalleryCategoryDetail($allFormValues);
							$modelDetails->setModuleImageGalleryCategoryId ( $module_image_gallery_category_id );
							$modelDetails->setLanguageId ( $languages->getLanguageId () );
							$modelDetails->setCreatedBy ( $user_id );
							$modelDetails->setCreatedAt ( $date_time );
							$modelDetails->setLastUpdatedBy ( $user_id );
							$modelDetails->setLastUpdatedAt ( $date_time );
							$modelDetails = $modelDetails->save ();
						}
					}
				}else {
					$model->setLastUpdatedBy ( $user_id );
					$model->setLastUpdatedAt ( $date_time );
					$model = $model->save ();
					// update cms details
					$modelDetails = new ModuleImageGallery_Model_ModuleImageGalleryCategoryDetail ( $allFormValues );
					$modelDetails = $modelDetails->save ();
				}
				$customermoduleMapper = new Admin_Model_Mapper_CustomerModule ();
				$customermodule = $customermoduleMapper->fetchAll ( "customer_id=" . $customer_id . " AND module_id=" . $this->_module_id );
				if (is_array ( $customermodule )) {
					$customermodule = $customermodule [0];
					$customermodule->setIsPublish ( "NO" );
					$customermodule->save ();
				}
				$mapper->getDbTable()->getAdapter()->commit();
				$response = array (
						"success" => $model->toArray ()
				);
			}catch(Exception $ex){
				$mapper->getDbTable ()->getAdapter ()->rollBack ();
				$response = $ex->getMessage ();
			}
		}else{echo "form is not valid"; exit();}
	} else {
			$errors = $form->getMessages ();
			foreach ( $errors as $name => $error ) {
				$errors [$name] = $error [0];
			}
			$response = array (
					"errors" => $errors 
			);
	}
	$this->_helper->json ( $response );
}

	public function deleteAction(){
		$this->_helper->layout ()->disableLayout ();
		$this->_helper->viewRenderer->setNoRender ();
		$request = $this->getRequest ();
		if (($module_image_gallery_category_id = $request->getParam ( "id", "" )) != "") {
			$model = new ModuleImageGallery_Model_ModuleImageGalleryCategory();
			if($model){
				try{
					$mapper = new ModuleImageGallery_Model_Mapper_ModuleImageGalleryCategory ();
					$mapper->getDbTable ()->getAdapter ()->beginTransaction ();
					$detailmapper = new ModuleImageGallery_Model_Mapper_ModuleImageGalleryCategoryDetail ();
					$imageMapper = new ModuleImageGallery_Model_Mapper_ModuleImageGallery();
					$imagedetailMapper = new ModuleImageGallery_Model_Mapper_ModuleImageGalleryDetail();
					$data = $mapper->fetchAll ( "module_image_gallery_category_id = " . $module_image_gallery_category_id );
					if ($data) {
						foreach ( $data as $category) {
							$dataDetails = $detailmapper->fetchAll("module_image_gallery_category_id =" .$category->getModuleImageGalleryCategoryId());
							if($dataDetails){
								foreach($dataDetails as $dataDetail){
									$deletedRows = $dataDetail->delete();
								}
							}
							$relatedImages = $imageMapper->fetchAll("module_image_gallery_category_id =" .$category->getModuleImageGalleryCategoryId());
							if($relatedImages){
								foreach($relatedImages as $relatedImage){
									$relatedImageDetails = $imagedetailMapper->fetchAll("module_image_gallery_id =".$relatedImage->getModuleImageGalleryId());
									if($relatedImageDetails){
										foreach($relatedImageDetails as $relatedImageDetail){
											$deletedImages = $relatedImageDetail->delete();
										}
									}
									$relatedImage->delete();
								}
							}
							$category->delete ();
						}
					}
					$customer_id = Standard_Functions::getCurrentUser ()->customer_id;
					$customermoduleMapper = new Admin_Model_Mapper_CustomerModule ();
					$customermodule = $customermoduleMapper->fetchAll ( "customer_id=" . $customer_id . " AND module_id=" . $this->_module_id );
					if (is_array ( $customermodule )) {
						$customermodule = $customermodule [0];
						$customermodule->setIsPublish ( "NO" );
						$customermodule->save ();
					}
					$mapper->getDbTable ()->getAdapter ()->commit ();
		
					$response = array (
							"success" => array (
									"deleted_rows" => $deletedRows
							)
					);
				} catch(Exception $e) {
					$mapper->getDbTable ()->getAdapter ()->rollBack ();
					$response = array (
							"errors" => array (
									"message" => $e->getMessage ()
							)
					);
				}
			}else {
				$response = array (
						"errors" => array (
								"message" => "No user to delete."
						)
				);
			}
		}else{
			$this->_redirect('/');
		}
		$this->_helper->json ( $response );
		}
		
	
	public function gridAction(){
		$this->_helper->layout ()->disableLayout ();
		$this->_helper->viewRenderer->setNoRender ();
		$customer_id = Standard_Functions::getCurrentUser ()->customer_id;
		$active_lang_id = Standard_Functions::getCurrentUser ()->active_language_id;
		$default_lang_id = Standard_Functions::getCurrentUser ()->default_language_id;
		$mapper = new ModuleImageGallery_Model_Mapper_ModuleImageGalleryCategory ();
		$select = $mapper->getDbTable ()->select ( false )->setIntegrityCheck ( false )->from ( array (
				"migc" => "module_image_gallery_category"
		), array (
				"migc.module_image_gallery_category_id" => "module_image_gallery_category_id",
				"migc.status" => "status",
				"migc.order" => "order",
		) )->joinLeft ( array (
				"migcd" => "module_image_gallery_category_detail"
		), "migcd.module_image_gallery_category_id = migc.module_image_gallery_category_id AND migcd.language_id = " . $active_lang_id, array (
				"migcd.module_image_gallery_category_detail_id" => "module_image_gallery_category_detail_id",
				"migcd.title" => "title"
		) )->where ( "migc.customer_id=" . Standard_Functions::getCurrentUser ()->customer_id );
		$response = $mapper->getGridData ( array (
				'column' => array (
						'id' => array (
								'actions'
						),
						'replace' => array (
								'migc.status' => array (
										'1' => $this->view->translate ( 'Active' ),
										'0' => $this->view->translate ( 'Inactive' )
								)
						)
				)
		), "customer_id=" . Standard_Functions::getCurrentUser ()->customer_id, $select );
		$mapper = new Admin_Model_Mapper_CustomerLanguage ();
		$select = $mapper->getDbTable ()->select ( false )->setIntegrityCheck ( false )->from ( array (
				"l" => "language"
		), array (
				"l.language_id" => "language_id",
				"l.title" => "title",
				"logo" => "logo"
		) )->joinLeft ( array (
				"cl" => "customer_language"
		), "l.language_id = cl.language_id", array (
				"cl.customer_id"
		) )->where ( "cl.customer_id=" . Standard_Functions::getCurrentUser ()->customer_id );
		$languages = $mapper->getDbTable ()->fetchAll ( $select )->toArray ();
		$rows = $response ['aaData'];

		foreach ( $rows as $rowId => $row ) {
			$edit = array ();
			if ($row [3] ["migcd.module_image_gallery_category_detail_id"] == "") {
				$mapper = new ModuleImageGallery_Model_Mapper_ModuleImageGalleryCategoryDetail ();
				$details = $mapper->fetchAll ( "module_image_gallery_category_id=" . $row [3] ["migc.module_image_gallery_category_id"] . " AND language_id=" . $default_lang_id );
				if (is_array ( $details )) {
					$details = $details [0];
					$row [3] ["migcd.title"] = $row [0] = $details->getTitle ();
				}
			}
			$response ['aaData'] [$rowId] = $row;
			if ($languages) {
				foreach ( $languages as $lang ) {
					$editUrl = $this->view->url ( array (
							"module" => "module-image-gallery",
							"controller" => "category",
							"action" => "edit",
							"id" => $row [3] ["migc.module_image_gallery_category_id"],
							"lang" => $lang ["l.language_id"]
					), "default", true );
					$edit [] = '<a href="' . $editUrl . '"><img src="../images/lang/' . $lang ["logo"] . '" alt="' . $lang ["l.title"] . '" /></a>';
				}
			}
			$deleteUrl = $this->view->url ( array (
					"module" => "module-image-gallery",
					"controller" => "category",
					"action" => "delete",
					"id" => $row [3] ["migc.module_image_gallery_category_id"]
			), "default", true );
				
			$defaultEdit = '<div id="editLanguage">&nbsp;<div class="flag-list">' . implode ( "", $edit ) . '</div></div>';
			$delete = '<a href="' . $deleteUrl . '" class="button-grid greay grid_delete" >'.$this->view->translate('Delete').'</a>';
			$sap = '';

			$response ['aaData'] [$rowId] [3] = $defaultEdit . $sap . $delete;
		}
		
		
		$jsonGrid = Zend_Json::encode ( $response );
		$this->_response->appendBody ( $jsonGrid );
		
	}
	
	public function reorderAction() {
		$customer_id = Standard_Functions::getCurrentUser ()->customer_id;
		$language_id = Standard_Functions::getCurrentUser ()->active_language_id;
		$request = $this->getRequest();
		$active_lang_id = Standard_Functions::getCurrentUser ()->active_language_id;
		$default_lang_id = Standard_Functions::getCurrentUser ()->default_language_id;
		$detailMapper = new ModuleCms_Model_Mapper_ModuleCmsDetail();
		if ($this->_request->isPost ()) {
			$this->_helper->layout ()->disableLayout ();
			$this->_helper->viewRenderer->setNoRender ();
			$user_id = Standard_Functions::getCurrentUser ()->user_id;
			$date_time = Standard_Functions::getCurrentDateTime ();
			$response = array();
			$order = $this->_request->getParam ("order");
	
			$mapper = new ModuleImageGallery_Model_Mapper_ModuleImageGalleryCategory();
			$mapper->getDbTable()->getAdapter()->beginTransaction();
			try {
				foreach($order as $key=>$value) {
					$model = $mapper->find($value);
					$model->setOrder($key);
					$model->setLastUpdatedBy ( $user_id );
					$model->setLastUpdatedAt ( $date_time );
					$model->save();
				}
					
				// set is pulish to false
				$customermoduleMapper = new Admin_Model_Mapper_CustomerModule();
				$customermodule = $customermoduleMapper->fetchAll("customer_id=". $customer_id ." AND module_id=".$this->_module_id);
				if(is_array($customermodule)) {
					$customermodule = $customermodule[0];
					$customermodule->setIsPublish("NO");
					$customermodule->save();
				}
				$mapper->getDbTable()->getAdapter()->commit();
				if($model && $model->getModuleImageGalleryCategoryId()!="") {
					$response = array (
							"success" => true
					);
				}
			}catch(Exception $e) {
				$mapper->getDbTable()->getAdapter()->rollBack();
				$response = array (
						"errors" => $e->getMessage()
				);
			}
			echo Zend_Json::encode($response);
			exit;
		}
		$mapper = new ModuleImageGallery_Model_Mapper_ModuleImageGalleryCategory();
		$select = $mapper->getDbTable ()->
		select ( false )->
		setIntegrityCheck ( false )->
		from ( array (
				"migc" => "module_image_gallery_category"
		), array (
				"migc.module_image_gallery_category_id" => "module_image_gallery_category_id",
				"migc.status" => "status",
				"migc.order" => "order"
		) )->joinLeft ( array (
				"migcd" => "module_image_gallery_category_detail"
		), "migcd.module_image_gallery_category_id = migc.module_image_gallery_category_id AND migcd.language_id=" . $active_lang_id, array (
				"migcd.module_image_gallery_category_detail_id" => "module_image_gallery_category_detail_id",
				"migcd.title" => "title"
		) )->where ( "migc.customer_id=" . $customer_id )->order("migc.order");
		$response = $mapper->getDbTable()->fetchAll($select)->toArray();
		$this->view->data = $response;
	}
}