<?php 
class Contact_IndexController extends Zend_Controller_Action {
	var $_module_id;
	var $_customer_module_id;
	public function init() {
		/* Initialize action controller here */
		$modulesMapper = new Admin_Model_Mapper_Module ();
		$module = $modulesMapper->fetchAll ( "name ='contact'" );
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
		// action body
		$this->view->addlink = $this->view->url ( array (
				"module" => "contact",
				"controller" => "index",
				"action" => "add" 
		), "default", true );
		$this->view->publishlink = $this->view->url ( array (
				"module" => "default",
				"controller" => "configuration",
				"action" => "publish",
				"id" => $this->_customer_module_id
		), "default", true );
		$this->view->reorderlink = $this->view->url ( array (
				"module" => "contact",
				"controller" => "index",
				"action" => "reorder" 
		), "default", true );
	}
	public function addAction() {
		// action body
		$lang_id = Standard_Functions::getCurrentUser ()->default_language_id;
		$language = new Admin_Model_Mapper_Language ();
		$lang = $language->find ( $lang_id );
		$this->view->language = $lang->getTitle ();
		
		$form = new Contact_Form_Contact ();
		foreach ( $form->getElements () as $element ) {
			if ($element->getDecorator ( 'Label' ))
				$element->getDecorator ( 'Label' )->setTag ( null );
		}
		$action = $this->view->url ( array (
				"module" => "contact",
				"controller" => "index",
				"action" => "save" 
		), "default", true );
		$form->setAction ( $action );
		$this->view->form = $form;
		$this->view->logo_path = "";
		$this->view->assign ( array (
				"partial" => "index/partials/add.phtml" 
		) );
		$this->view->iconpack = $this->_iconpack;
		$this->render ( "add-edit" );
	}
	public function reorderAction() {
		$active_lang_id = Standard_Functions::getCurrentUser ()->active_language_id;
		$default_lang_id = Standard_Functions::getCurrentUser ()->default_language_id;
		$customer_id = Standard_Functions::getCurrentUser ()->customer_id;
		
		if ($this->_request->isPost ()) {
			$this->_helper->layout ()->disableLayout ();
			$this->_helper->viewRenderer->setNoRender ();
			
			$user_id = Standard_Functions::getCurrentUser ()->user_id;
			$date_time = Standard_Functions::getCurrentDateTime ();
			$response = array ();
			
			$order = $this->_request->getParam ( "order" );
			
			$mapper = new Contact_Model_Mapper_Contact ();
			$mapper->getDbTable ()->getAdapter ()->beginTransaction ();
			try {
				foreach ( $order as $key => $value ) {
					$model = $mapper->find ( $value );
					$model->setOrder ( $key );
					$model->setLastUpdatedBy ( $user_id );
					$model->setLastUpdatedAt ( $date_time );
					$model->save ();
				}
				
				// set is pulish to false
				$customermoduleMapper = new Admin_Model_Mapper_CustomerModule ();
				$customermodule = $customermoduleMapper->fetchAll ( "customer_id=" . $customer_id . " AND module_id=" . $this->_module_id );
				if (is_array ( $customermodule )) {
					$customermodule = $customermodule [0];
					$customermodule->setIsPublish ( "NO" );
					$customermodule->save ();
				}
				
				$mapper->getDbTable ()->getAdapter ()->commit ();
				if ($model && $model->getContactId () != "") {
					$response = array (
							"success" => true 
					);
				}
			} catch ( Exception $e ) {
				$mapper->getDbTable ()->getAdapter ()->rollBack ();
				$response = array (
						"errors" => $e->getMessage () 
				);
			}
			echo Zend_Json::encode ( $response );
			exit ();
		}
		
		$mapper = new Contact_Model_Mapper_Contact ();
		$select = $mapper->getDbTable ()->select ( false )->setIntegrityCheck ( false )->from ( array (
				"c" => "module_contact" 
		), array (
				"c.contact_id" => "contact_id",
				"c.status" => "status",
				"c.order" => "order" 
		) )->joinLeft ( array (
				"cd" => "module_contact_detail" 
		), "cd.contact_id = c.contact_id AND cd.language_id = " . $active_lang_id, array (
				"cd.contact_detail_id" => "contact_detail_id",
				"cd.location" => "location",
				"cd.address" => "address",
				"cd.phone_1" => "phone_1" 
		) )->where ( "c.customer_id=" . $customer_id )->order ( "c.order" );
		$response = $mapper->getDbTable ()->fetchAll ( $select )->toArray ();
		$this->view->data = $response;
	}
	public function editAction() {
		// action body
		$edit = true;
		$form = new Contact_Form_Contact ();
		$request = $this->getRequest ();
		$img_uri = "resource/contact/images";
		if ($request->getParam ( "id", "" ) != "" && $request->getParam ( "lang", "" ) != "") {
			$contact_id = $request->getParam ( "id", "" );
			$lang_id = $request->getParam ( "lang", "" );
			$language = new Admin_Model_Mapper_Language ();
			$lang = $language->find ( $lang_id );
			$this->view->language = $lang->getTitle ();
			
			$default_lang_id = Standard_Functions::getCurrentUser ()->default_language_id;
			
			$mapper = new Contact_Model_Mapper_Contact ();
			$data = $mapper->find ( $contact_id )->toArray ();
			$dataDetails = array ();
			$details = new Contact_Model_Mapper_ContactDetail ();
			if ($details->countAll ( "contact_id = " . $contact_id . " AND language_id = " . $lang_id ) > 0) {
				// Record For Language Found
				$dataDetails = $details->getDbTable ()->fetchAll ( "contact_id = " . $contact_id . " AND language_id = " . $lang_id )->toArray ();
			} else {
				// Record For Language Not Found
				$dataDetails = $details->getDbTable ()->fetchAll ( "contact_id = " . $contact_id . " AND language_id = " . $default_lang_id )->toArray ();
				$dataDetails [0] ["contact_detail_id"] = "";
				$dataDetails [0] ["language_id"] = $lang_id;
			}
			if (isset ( $dataDetails [0] ) && is_array ( $dataDetails [0] )) {
				//print_r($dataDetails [0]);
			    if($dataDetails[0]['icon'] != null){
			        if(count(explode('/',$dataDetails[0]['icon'])) > 1){
			            $this->view->icon_src = $dataDetails[0]['icon'];
			        }else{
			            $this->view->icon_src = "preset-icons/".$dataDetails[0]['icon'];
			        }
			    }
			    $timings = $dataDetails [0]['timings'];
				$form->populate ( $dataDetails [0] );
				$logoname = $dataDetails [0] ["logo"];
				$image_uri = "resource/contact/images/";
				$this->view->logo_path = $logoname;
				if($logoname != ""){
					$this->view->logo_display = $this->view->baseUrl($image_uri.$logoname);
				}
				$this->view->edit = $edit;
				$this->view->timings = $timings;
			}
			foreach ( $form->getElements () as $element ) {
				if ($element->getDecorator ( 'Label' ))
					$element->getDecorator ( 'Label' )->setTag ( null );
			}
			
			$action = $this->view->url ( array (
					"module" => "contact",
					"controller" => "index",
					"action" => "save",
					"id" => $request->getParam ( "id", "" ) 
			), "default", true );
			$form->setAction ( $action );
		} else {
			$this->_redirect ( '/' );
		}
		$this->view->form = $form;
		$this->view->assign ( array (
				"partial" => "index/partials/edit.phtml" 
		) );
		$this->view->iconpack = $this->_iconpack;
		$this->render ( "add-edit" );
	}
	public function saveAction() {
		// action body
		$form = new Contact_Form_Contact ();
		$request = $this->getRequest ();
		$response = array ();
		$default_lang_id = Standard_Functions::getCurrentUser ()->default_language_id;
		if ($this->_request->isPost ()) {
			$source_dir = Standard_Functions::getResourcePath () . "contact/images/";
			$upload_dir = Standard_Functions::getResourcePath () . "contact/thumb/";
			if ($request->getParam ( "upload", "" ) != "") {
				$adapter = new Zend_File_Transfer_Adapter_Http ();
				$adapter->setDestination ( Standard_Functions::getResourcePath () . "contact/images" );
				$adapter->receive ();
				if ($adapter->getFileName ( "logo" ) != "") {
					$response = array (
							"success" => array_pop ( explode ( '/', $adapter->getFileName ( "logo" ) ) ) 
					);
				} else {
					$response = array (
							"errors" => "Error Occured" 
					);
				}
				
				echo Zend_Json::encode ( $response );
				// $this->_helper->json ( $response );
				exit ();
			}
			if($request->getParam ( "iconupload", "" ) != "") {
			    $adapter = new Zend_File_Transfer_Adapter_Http();
			    $adapter->setDestination(Standard_Functions::getResourcePath(). "contact/uploaded-icons");
			    $adapter->receive();
			    if($adapter->getFileName("icon")!="")
			    {
			        $response = array (
			                "success" => array_pop(explode('/',$adapter->getFileName("icon")))
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
			$form->removeElement ( "logo" );
			$allFlag = $this->_request->getParam("all",false);
			if ($form->isValid ( $this->_request->getParams () )) {
				try {
					$timings = $request->getParam("xml","");
					$arrFormValues = $form->getValues ();
					$customer_id = Standard_Functions::getCurrentUser ()->customer_id;
					$user_id = Standard_Functions::getCurrentUser ()->user_id;
					$date_time = Standard_Functions::getCurrentDateTime ();
					$logo_path = $request->getParam ( "logo_path", "" );
					$mapper = new Contact_Model_Mapper_Contact ();
					$mapper->getDbTable ()->getAdapter ()->beginTransaction ();
					$model = new Contact_Model_Contact ( $arrFormValues );
					if($request->getParam("selLogo","0")){
					    $selIcon = $request->getParam("selLogo","0");
					}
					$icon_path = $request->getParam("icon_path","");
					if($selIcon != 0){
					    $arrFormValues["icon"] = $selIcon;
					}elseif ($icon_path != ""){
					    $arrFormValues["icon"] = "uploaded-icons/".$icon_path;
					}
					if ($request->getParam ( "contact_id", "" ) == "") {
						// Add new Record
						$maxOrder = $mapper->getNextOrder ( $customer_id );
						$model->setOrder ( $maxOrder + 1 );
						$model->setCustomerId ( $customer_id );
						$model->setCreatedBy ( $user_id );
						$model->setCreatedAt ( $date_time );
						$model->setLastUpdatedBy ( $user_id );
						$model->setLastUpdatedAt ( $date_time );
						$model = $model->save ();
						
						// Save Contact Details
						$contact_id = $model->get ( "contact_id" );
						$mapperLanguage = new Admin_Model_Mapper_CustomerLanguage ();
						$modelLanguages = $mapperLanguage->fetchAll ( "customer_id = " . $customer_id );
						if (is_array ( $modelLanguages )) {
							$is_uploaded_image = false;
							foreach ( $modelLanguages as $languages ) {
								$modelDetails = new Contact_Model_ContactDetail ( $arrFormValues );
								$modelDetails->setContactId ( $contact_id );
								$modelDetails->setLanguageId ( $languages->getLanguageId () );
								$modelDetails->setTimings ( $timings );
								$modelDetails->setLogo($logo_path);
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
						$mapperLanguage = new Admin_Model_Mapper_CustomerLanguage ();
						$modelLanguages = $mapperLanguage->fetchAll ( "customer_id = " . $customer_id );
						$mapperDetails = new Contact_Model_Mapper_ContactDetail ( $arrFormValues );
						$mapperDetailsData = $mapperDetails->getDbTable()->fetchAll("contact_id =".$arrFormValues['contact_id'])->toArray();
						if($arrFormValues['contact_detail_id'] != ""){
						    $currentmapperDetailsData = $mapperDetails->getDbTable()->fetchAll("contact_detail_id =".$arrFormValues['contact_detail_id'])->toArray();
						}else{
						    $currentmapperDetailsData = $mapperDetails->getDbTable()->fetchAll("contact_id ='".$arrFormValues['contact_id']."' AND language_id =".$default_lang_id)->toArray();
						}
						if(is_array($currentmapperDetailsData)){
						    $arrFormValues['logo'] = $currentmapperDetailsData[0]['logo'];
						    if(!$arrFormValues['icon']){
						        $arrFormValues['icon'] = $currentmapperDetailsData[0]['icon'];
						    }    
						}
						unset($arrFormValues['contact_detail_id'],$arrFormValues['language_id']);
						if(count($mapperDetails) == count($modelLanguages)){
						    foreach ($mapperDetailsData as $mapperDetailData) {
						        $mapperDetailData = array_intersect_key($arrFormValues + $mapperDetailData, $mapperDetailData);
						        $contactDetailModel = new Contact_Model_ContactDetail($mapperDetailData);
						        if ($logo_path == "deleted" ) {
						            $contactDetailModel->setLogo ("");
						        }
						        if ($logo_path != "" && $logo_path != "deleted" && $logo_path != "/appstart/public/") {
						            $contactDetailModel->setLogo ($logo_path);
						        }
						        $contactDetailModel = $contactDetailModel->save();
						    }   
						}else{
						    $mapperDetails = new Contact_Model_Mapper_ContactDetail ( $arrFormValues );
						    $mapperDetailsDatas = $mapperDetails->fetchAll("contact_id =".$arrFormValues['contact_id']);
						    foreach ($mapperDetailsDatas as $mapperDetailData){
						        $mapperDetailData->delete();
						    }
						    if (is_array ( $modelLanguages )) {
						        $is_uploaded_image = false;
						        foreach ( $modelLanguages as $languages ) {
						            $modelDetails = new Contact_Model_ContactDetail ( $arrFormValues );
						            $modelDetails->setLanguageId ( $languages->getLanguageId () );
						            $modelDetails->setTimings ( $timings );
    						        if ($logo_path == "deleted" ) {
            							$modelDetails->setLogo ("");
            						}elseif ($logo_path != "" && $logo_path != "deleted" && $logo_path != "/appstart/public/"){
            						    $modelDetails->setLogo ($logo_path);
            						}
						            $modelDetails->setCreatedBy ( $user_id );
						            $modelDetails->setCreatedAt ( $date_time );
						            $modelDetails->setLastUpdatedBy ( $user_id );
						            $modelDetails->setLastUpdatedAt ( $date_time );
						    
						            $modelDetails = $modelDetails->save ();
						        }
						    }        
						}
					} else {
						// Update record]
						$model->setLastUpdatedBy ( $user_id );
						$model->setLastUpdatedAt ( $date_time );
						$model = $model->save ();
						
						// Save Contact Details
						$contact_id = $model->get ( "contact_id" );
						
						$modelDetails = new Contact_Model_ContactDetail ( $arrFormValues );
						if ($logo_path == "deleted" ) {
							$modelDetails->setLogo ("");
						}
						if ($logo_path != "" && $logo_path != "deleted" && $logo_path != "/appstart/public/") {
							$modelDetails->setLogo ($logo_path);
						}
						$modelDetails->setTimings ( $timings );
						$modelDetails->setCreatedBy ( $user_id );
						$modelDetails->setCreatedAt ( $date_time );
						$modelDetails->setLastUpdatedBy ( $user_id );
						$modelDetails->setLastUpdatedAt ( $date_time );
						$modelDetails = $modelDetails->save ();
					}
					
					// set is pulish to false
					$customermoduleMapper = new Admin_Model_Mapper_CustomerModule ();
					$customermodule = $customermoduleMapper->fetchAll ( "customer_id=" . $customer_id . " AND module_id=" . $this->_module_id );
					if (is_array ( $customermodule )) {
						$customermodule = $customermodule [0];
						$customermodule->setIsPublish ( "NO" );
						$customermodule->save ();
					}
					
					$mapper->getDbTable ()->getAdapter ()->commit ();
					if ($model && $model->getContactId () != "") {
						$response = array (
								"success" => $model->toArray () 
						);
					}
				} catch ( Exception $ex ) {
					$mapper->getDbTable ()->getAdapter ()->rollBack ();
					$response = array (
							"errors" => $ex->getMessage () 
					);
				}
			} else {
				$errors = $form->getMessages ();
				foreach ( $errors as $name => $error ) {
					$errors [$name] = $error [0];
				}
				$response = array (
						"errors" => $errors 
				);
			}
		}
		// Send error or success message accordingly
		$this->_helper->json ( $response );
	}
	public function deleteAction() {
		$this->_helper->layout ()->disableLayout ();
		$this->_helper->viewRenderer->setNoRender ();
		$request = $this->getRequest ();
		
		if (($contactId = $request->getParam ( "id", "" )) != "") {
			$contact = new Contact_Model_Contact ();
			$contact->populate ( $contactId );
			if ($contact) {
				try {
					$details = new Contact_Model_Mapper_ContactDetail ();
					$details->getDbTable ()->getAdapter ()->beginTransaction ();
					
					$dataDetails = $details->fetchAll ( "contact_id = " . $contact->getContactId () );
					foreach ( $dataDetails as $contactDetail ) {
						$contactDetail->delete ();
					}
					
					$deletedRows = $contact->delete ();
					
					// set is pulish to false
					$customerId = Standard_Functions::getCurrentUser ()->customer_id;
					$customermoduleMapper = new Admin_Model_Mapper_CustomerModule ();
					$customermodule = $customermoduleMapper->fetchAll ( "customer_id=" . $customerId . " AND module_id=" . $this->_module_id );
					if (is_array ( $customermodule )) {
						$customermodule = $customermodule [0];
						$customermodule->setIsPublish ( "NO" );
						$customermodule->save ();
					}
					
					$details->getDbTable ()->getAdapter ()->commit ();
					
					$response = array (
							"success" => array (
									"deleted_rows" => $deletedRows 
							) 
					);
				} catch ( Exception $e ) {
					
					$details->getDbTable ()->getAdapter ()->rollBack ();
					$response = array (
							"errors" => array (
									"message" => $e->getMessage () 
							) 
					);
				}
			} else {
				$response = array (
						"errors" => array (
								"message" => "No user to delete." 
						) 
				);
			}
		} else {
			$this->_redirect ( '/' );
		}
		
		$this->_helper->json ( $response );
	}
	public function gridAction() {
		$this->_helper->layout ()->disableLayout ();
		$this->_helper->viewRenderer->setNoRender ();
		
		$active_lang_id = Standard_Functions::getCurrentUser ()->active_language_id;
		$default_lang_id = Standard_Functions::getCurrentUser ()->default_language_id;
		$mapper = new Contact_Model_Mapper_Contact ();
		
		$select = $mapper->getDbTable ()->select ( false )->setIntegrityCheck ( false )->from ( array (
				"c" => "module_contact" 
		), array (
				"c.contact_id" => "contact_id",
				"c.status" => "status",
				"c.order" => "order" 
		) )->joinLeft ( array (
				"cd" => "module_contact_detail" 
		), "cd.contact_id = c.contact_id AND cd.language_id = " . $active_lang_id, array (
				"cd.contact_detail_id" => "contact_detail_id",
				"cd.location" => "location",
				"cd.city" => "city",
				"cd.phone_1" => "phone_1" 
		) )->where ( "c.customer_id=" . Standard_Functions::getCurrentUser ()->customer_id );
		// print_r($select->__toString());
		// echo "\n";
		$response = $mapper->getGridData ( array (
				'column' => array (
						'id' => array (
								'actions' 
						),
						'replace' => array (
								'c.status' => array (
										'1' => $this->view->translate ( 'Active' ),
										'0' => $this->view->translate ( 'Inactive' ) 
								) 
						) 
				) 
		), "customer_id=" . Standard_Functions::getCurrentUser ()->customer_id, $select );
		$records = $response ['aaData'];
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
			if ($row [5] ["cd.contact_detail_id"] == "") {
				$mapper = new Contact_Model_Mapper_ContactDetail ();
				$details = $mapper->fetchAll ( "contact_id=" . $row [5] ["c.contact_id"] . " AND language_id=" . $default_lang_id );
				if (is_array ( $details )) {
					$details = $details [0];
					$row [5] ["cd.location"] = $row [0] = $details->getLocation ();
					$row [5] ["cd.city"] = $row [1] = $details->getCity ();
					$row [5] ["cd.phone_1"] = $row [4] = $details->getPhone1 ();
				}
			}
			$response ['aaData'] [$rowId] = $row;
			if ($languages) {
				foreach ( $languages as $lang ) {
					$editUrl = $this->view->url ( array (
							"module" => "contact",
							"controller" => "index",
							"action" => "edit",
							"id" => $row [5] ["c.contact_id"],
							"lang" => $lang ["l.language_id"] 
					), "default", true );
					$edit [] = '<a href="' . $editUrl . '"><img src="images/lang/' . $lang ["logo"] . '" alt="' . $lang ["l.title"] . '" /></a>';
				}
			}
			$deleteUrl = $this->view->url ( array (
					"module" => "contact",
					"controller" => "index",
					"action" => "delete",
					"id" => $row [5] ["c.contact_id"] 
			), "default", true );
    		$edit = '<div id="editLanguage">&nbsp;<div class="flag-list">'.implode("",$edit).'</div></div>';
			$delete = '<a href="' . $deleteUrl . '" class="button-grid greay grid_delete" >'.$this->view->translate('Delete').'</a>';
			$sap = '';
			
			$response ['aaData'] [$rowId] [5] = $edit . $sap . $delete;
		}
		
		$jsonGrid = Zend_Json::encode ( $response );
		$this->_response->appendBody ( $jsonGrid );
	}
	
}