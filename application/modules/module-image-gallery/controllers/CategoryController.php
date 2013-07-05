<?php
class ModuleImageGallery_CategoryController extends Zend_Controller_Action {
	var $_module_id;
	var $_customer_module_id;
	public function init() {
		/* Initialize Action Controller Here.. */
		$modulesMapper = new Admin_Model_Mapper_Module ();
		$module = $modulesMapper->fetchAll ( "name ='module-image-gallery'" );
		if (is_array ( $module )) {
			$this->_module_id = $module [0]->getModuleId ();
		}
		$customer_id = Standard_Functions::getCurrentUser ()->customer_id;
		$customermoduleMapper = new Admin_Model_Mapper_CustomerModule();
		$customermodule = $customermoduleMapper->fetchAll("customer_id=". $customer_id ." AND module_id=".$this->_module_id);
		if(is_array($customermodule)) {
		    $customermodule = $customermodule[0];
		    $this->_customer_module_id = $customermodule->getCustomerModuleId();
		}
		$image_dir = Standard_Functions::getResourcePath(). "module-image-gallery/preset-icons";
		if(is_dir($image_dir)){
		    $direc = opendir($image_dir);
		    $iconpack = array();
		    while($icon = readdir($direc)){
		        if(is_file($image_dir."/".$icon) && getimagesize($image_dir."/".$icon)){
		            $iconpack[] = $icon;
		        }
		    }
		}
		$this->_iconpack = $iconpack;
	}
	
	public function indexAction() {
		$active_lang_id = Standard_Functions::getCurrentUser ()->active_language_id;
		$this->view->addlink = $this->view->url ( array (
				"module" => "module-image-gallery",
				"controller" => "category",
				"action" => "add"
		), "default", true );
		$this->view->publishlink = $this->view->url ( array (
		        "module" => "default",
		        "controller" => "configuration",
		        "action" => "publish",
		        "id" => $this->_customer_module_id
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
		$this->view->iconpack = $this->_iconpack;
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
				$dataDetails [0] ["module_image_gallery_category_detail_id"] = "";
				$dataDetails [0] ["language_id"] = $language_id;
				//$this->view->category = $dataDetails[0]['title'];
			}
			if (isset ( $dataDetails [0] ) && is_array ( $dataDetails [0] )) {
			    if($dataDetails[0]['icon'] != null){
			        if(count(explode('/',$dataDetails[0]['icon'])) > 1){
			            $this->view->icon_src = $dataDetails[0]['icon'];
			        }else{
			            $this->view->icon_src = "preset-icons/".$dataDetails[0]['icon'];
			        }
			    }
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
		$this->view->iconpack = $this->_iconpack;
		$this->render ( "add-edit" );
	}
	public function saveAction(){
		$this->_helper->layout ()->disableLayout ();
		$this->_helper->viewRenderer->setNoRender ();
		$form = new ModuleImageGallery_Form_Category();
		$default_lang_id = Standard_Functions::getCurrentUser ()->default_language_id;
		$request = $this->getRequest ();
		$response = array ();
		$allFlag = $this->_request->getParam("all",false);
		if ($this->_request->isPost ()) {
		    if($request->getParam ( "iconupload", "" ) != "") {
		        $adapter = new Zend_File_Transfer_Adapter_Http();
		        $adapter->setDestination(Standard_Functions::getResourcePath(). "module-image-gallery/uploaded-icons");
		        $adapter->receive();
		        if($adapter->getFileName("icon")!="")
		        {
		            $response = array (
		                    "success" => array_pop(explode('\\',$adapter->getFileName("icon")))
		            );
		        } else {
		            $response = array (
		                    "errors" => "Error Occured"
		            );
		        }
		    
		        echo Zend_Json::encode($response);
		        exit;
		    }
		    $form->removeElement("icon");
			if ($form->isValid ( $this->_request->getParams () )) {
				try{
				$allFormValues = $form->getValues();
				$customer_id = Standard_Functions::getCurrentUser ()->customer_id;
				$user_id = Standard_Functions::getCurrentUser ()->user_id;
				$date_time = Standard_Functions::getCurrentDateTime ();
				$mapper = new ModuleImageGallery_Model_Mapper_ModuleImageGalleryCategory ();
				$mapper->getDbTable()->getAdapter()->beginTransaction();
				$model = new ModuleImageGallery_Model_ModuleImageGalleryCategory ( $allFormValues );
				$selIcon = $request->getParam("selLogo","0");
				$icon_path = $request->getParam("icon_path","");
				if($selIcon != "0"){
				    $allFormValues["icon"] = $selIcon;
				}elseif($icon_path == "deleted"){
				    $allFormValues["icon"] = "";
				}elseif ($icon_path != ""){
				    $allFormValues["icon"] = "uploaded-icons/".$icon_path;
				}
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
    				}elseif($allFlag){
						$model->setLastUpdatedBy ( $user_id );
    					$model->setLastUpdatedAt ( $date_time );
    					$model = $model->save ();
    					$categoryDetail = new ModuleImageGallery_Model_Mapper_ModuleImageGalleryCategoryDetail($allFormValues);
						$categoryDetails = $categoryDetail->getDbTable()->fetchAll("module_image_gallery_category_id =".$allFormValues['module_image_gallery_category_id'])->toArray();
						$mapperLanguage = new Admin_Model_Mapper_CustomerLanguage ();
						$modelLanguages = $mapperLanguage->fetchAll ( "customer_id = " . $customer_id );
						if($allFormValues['module_image_gallery_category_detail_id'] != null){
						    $currentDetails = $categoryDetail->getDbTable()->fetchAll("module_image_gallery_category_detail_id =".$allFormValues['module_image_gallery_category_detail_id'])->toArray();
						}else{
						    $currentDetails = $categoryDetail->getDbTable()->fetchAll("module_image_gallery_category_id ='".$allFormValues['module_image_gallery_category_id']."' AND language_id =".$default_lang_id)->toArray();
						}
						if(is_array($currentDetails)){
						    if(!isset($allFormValues['icon'])){
						        $allFormValues['icon'] = $currentDetails[0]['icon'];
						    }
						}
						unset($allFormValues['module_image_gallery_category_detail_id'],$allFormValues['language_id']);
						if(count($modelLanguages) == count($categoryDetails)){
						    foreach ($categoryDetails as $categoryDetail) {
						        $categoryDetail = array_intersect_key($allFormValues + $categoryDetail, $categoryDetail);
						        $categoryDetailModel = new ModuleImageGallery_Model_ModuleImageGalleryCategoryDetail($categoryDetail);
						        $categoryDetailModel = $categoryDetailModel->save();
						    }   
						}else{
						    $categoryDetail = new ModuleImageGallery_Model_Mapper_ModuleImageGalleryCategoryDetail();
						    $categoryDetails = $categoryDetail->fetchAll("module_image_gallery_category_id =".$allFormValues['module_image_gallery_category_id']);
						    foreach ($categoryDetails as $categoryDetail){
						        $categoryDetail->delete();
						    }
						    if (is_array ( $modelLanguages )) {
						        $is_uploaded_image = false;
						        foreach ( $modelLanguages as $languages ) {
						            $categoryDetail = new ModuleImageGallery_Model_ModuleImageGalleryCategoryDetail($allFormValues);
						            $categoryDetail->setLanguageId ( $languages->getLanguageId () );
						            $categoryDetail->setCreatedBy ( $user_id );
						            $categoryDetail->setCreatedAt ( $date_time );
						            $categoryDetail->setLastUpdatedBy ( $user_id );
						            $categoryDetail->setLastUpdatedAt ( $date_time );
						            $categoryDetail = $categoryDetail->save();
						        }
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
				$imageMapper = new ModuleImageGallery_Model_Mapper_ModuleImageGallery();
				$imageexist = $imageMapper->fetchAll("module_image_gallery_category_id =" .$module_image_gallery_category_id);
				if($imageexist){
				    $response = array (
				            "errors" => array (
				                    "message" => "Please delete its images first."
				            )
				    );
				}else{
				    try{
					    $detailmapper = new ModuleImageGallery_Model_Mapper_ModuleImageGalleryCategoryDetail ();
					    $mapper = new ModuleImageGallery_Model_Mapper_ModuleImageGalleryCategory ();
					    $mapper->getDbTable ()->getAdapter ()->beginTransaction ();
					    $data = $mapper->fetchAll ( "module_image_gallery_category_id = " . $module_image_gallery_category_id );
					    if ($data) {
					        foreach ( $data as $category) {
					            $dataDetails = $detailmapper->fetchAll("module_image_gallery_category_id =" .$category->getModuleImageGalleryCategoryId());
					            if($dataDetails){
					                foreach($dataDetails as $dataDetail){
					                    $deletedRows = $dataDetail->delete();
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