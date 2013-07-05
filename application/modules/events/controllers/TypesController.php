<?php
class Events_TypesController extends Zend_Controller_Action
{
	var $_module_id;
	var $_iconpack;
	var $_customer_module_id;
	public function init()
	{
		/* Initialize action controller here */
		$modulesMapper = new Admin_Model_Mapper_Module();
		$module = $modulesMapper->fetchAll("name ='events'");
		if(is_array($module)) {
			$this->_module_id = $module[0]->getModuleId();
		}
		$customer_id = Standard_Functions::getCurrentUser ()->customer_id;
		$customermoduleMapper = new Admin_Model_Mapper_CustomerModule();
		$customermodule = $customermoduleMapper->fetchAll("customer_id=". $customer_id ." AND module_id=".$this->_module_id);
		if(is_array($customermodule)) {
			$customermodule = $customermodule[0];
			$this->_customer_module_id = $customermodule->getCustomerModuleId();
		}
		
		$image_dir = Standard_Functions::getResourcePath(). "events/preset-icons";
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
	public function indexAction()
	{
		// action body
		$this->view->addlink = $this->view->url ( array (
				"module" => "events",
				"controller" => "types",
				"action" => "add"
		), "default", true );
		$this->view->publishlink = $this->view->url ( array (
		        "module" => "default",
		        "controller" => "configuration",
		        "action" => "publish",
		        "id" => $this->_customer_module_id
		), "default", true );
		$this->view->reorderlink = $this->view->url ( array (
				"module" => "events",
				"controller" => "types",
				"action" => "reorder"
		), "default", true );
		$customer_id = Standard_Functions::getCurrentUser ()->customer_id;
		$language_id = Standard_Functions::getCurrentUser ()->active_language_id;
	}
	public function addAction() {
		$lang_id = Standard_Functions::getCurrentUser ()->default_language_id;
		$language = new Admin_Model_Mapper_Language();
		$lang = $language->find($lang_id);
		$this->view->language = $lang->getTitle();
	
		$customer_id = Standard_Functions::getCurrentUser ()->customer_id;
		$language_id = Standard_Functions::getCurrentUser ()->active_language_id;
		
		// add Action
		$form = new Events_Form_EventsTypes();
		$action = $this->view->url ( array (
				"module" => "events",
				"controller" => "types",
				"action" => "save"
		), "default", true );
		$form->setMethod ( 'POST' );
		$form->setAction ( $action );
		$this->view->form = $form;
		$this->view->assign ( array (
				"partial" => "types/partials/add.phtml"
		) );
		$this->view->iconpack = $this->_iconpack;
		$this->render ( "add-edit" );
	}
	public function editAction()
	{
		$form = new Events_Form_EventsTypes();
		foreach ( $form->getElements () as $element ) {
			if ($element->getDecorator ( 'Label' ))
				$element->getDecorator ( 'Label' )->setTag ( null );
		}
		$request = $this->getRequest ();
		if ($request->getParam ( "id", "" ) != "" && $request->getParam ( "lang", "" ) != "") {
			$events_type_id = $request->getParam ( "id", "" );
			$lang_id = $request->getParam ( "lang", "" );
	
			$language = new Admin_Model_Mapper_Language();
			$lang = $language->find($lang_id);
			$this->view->language = $lang->getTitle();
			 
			$default_lang_id = Standard_Functions::getCurrentUser ()->default_language_id;
	
			$mapper = new Events_Model_Mapper_ModuleEventsTypes();
			$data = $mapper->find ( $events_type_id )->toArray ();
			$form->populate ( $data );
			
			$customer_id = Standard_Functions::getCurrentUser ()->customer_id;
			$language_id = Standard_Functions::getCurrentUser ()->active_language_id;
			
			$dataDetails = array();
			$details = new Events_Model_Mapper_ModuleEventsTypesDetail();			 
			if($details->countAll("module_events_types_id = ".$events_type_id." AND language_id = ".$lang_id) > 0) {
				// Record For Language Found
				$dataDetails = $details->getDbTable()->fetchAll("module_events_types_id = ".$events_type_id." AND language_id = ".$lang_id)->toArray();
			} else {
				// Record For Language Not Found
				$dataDetails = $details->getDbTable()->fetchAll("module_events_types_id = ".$events_type_id." AND language_id = ".$default_lang_id)->toArray();
				$dataDetails[0]["module_events_types_detail_id"] = "";
				$dataDetails[0]["language_id"] = $lang_id;
			}
	
			if(isset($dataDetails[0]) && is_array($dataDetails[0])) {
				if($dataDetails[0]['icon'] != null){
					if(count(explode('/',$dataDetails[0]['icon'])) > 1){
						$this->view->icon_src = $dataDetails[0]['icon'];
					}else{
						$this->view->icon_src = "preset-icons/".$dataDetails[0]['icon'];
					}
				}
			    $form->populate ( $dataDetails[0] );
			}
	
			$action = $this->view->url ( array (
					"module" => "events",
					"controller" => "types",
					"action" => "save",
					"id" => $request->getParam ( "id", "" )
			), "default", true );
			$form->setAction($action);
		} else {
			$this->_redirect ( '/' );
		}
	
		$this->view->form = $form;
		$this->view->iconpack = $this->_iconpack;
		$this->view->assign ( array (
				"partial" => "index/partials/edit.phtml"
		) );
		$this->render ( "add-edit" );
	}
	
	public function saveAction() {
		$form = new Events_Form_EventsTypes();
		$request = $this->getRequest ();
		$response = array ();
		$default_lang_id = Standard_Functions::getCurrentUser ()->default_language_id;
		$allFlag = $this->_request->getParam("all",false);
		if ($this->_request->isPost ()) {
			if($request->getParam ( "iconupload", "" ) != "") {
				$adapter = new Zend_File_Transfer_Adapter_Http();
				$adapter->setDestination(Standard_Functions::getResourcePath(). "events/types/uploaded-icons");
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
		    if ($form->isValid ( $this->_request->getParams () )) {
				$mapper = new Events_Model_Mapper_ModuleEventsTypes();
				$mapper->getDbTable()->getAdapter()->beginTransaction();
				 
				try {
				    $arrFormValues = $form->getValues();
					$customer_id = Standard_Functions::getCurrentUser ()->customer_id;
					$user_id = Standard_Functions::getCurrentUser ()->user_id;
					$date_time = Standard_Functions::getCurrentDateTime ();
					
					$model = new Events_Model_ModuleEventsTypes($arrFormValues);
					if ($request->getParam ( "module_events_types_id", "" ) == "") {
					    $maxOrder = $mapper->getNextOrder ( $customer_id );
					    $model->setOrder ( $maxOrder + 1 );
					}
					if($request->getParam("selLogo","0")){
						$selIcon = $request->getParam("selLogo","0");
					}
					$icon_path = $request->getParam("icon_path","");
					if($selIcon != 0){
						$arrFormValues["icon"] = $selIcon;
					}elseif($icon_path == "deleted"){
					    $arrFormValues["icon"] = "";
					}elseif ($icon_path != "" && $selIcon == 0){
						$arrFormValues["icon"] = "uploaded-icons/".$icon_path;
					}
					if($request->getParam ( "module_events_types_id", "" ) == "") {
						// Add Category
						$model->setCustomerId ( $customer_id );
						$model->setCreatedBy ( $user_id );
						$model->setCreatedAt ( $date_time );
						$model->setLastUpdatedBy ( $user_id );
						$model->setLastUpdatedAt ( $date_time );
						$model = $model->save ();
						// Save Details
						$module_events_types_id = $model->getModuleEventsTypesId();
						$mapperLanguage = new Admin_Model_Mapper_CustomerLanguage();
						$modelLanguages = $mapperLanguage->fetchAll("customer_id = ".$customer_id);
						if(is_array($modelLanguages)) {
							foreach($modelLanguages as $languages) {
								$modelDetails = new Events_Model_ModuleEventsTypesDetail($arrFormValues);
								$modelDetails->setModuleEventsTypesId($module_events_types_id);
								$modelDetails->setLanguageId ($languages->getLanguageId());
								$modelDetails->setCreatedBy ( $user_id );
								$modelDetails->setCreatedAt ( $date_time );
								$modelDetails->setLastUpdatedBy ( $user_id );
								$modelDetails->setLastUpdatedAt ( $date_time );
								$modelDetails = $modelDetails->save();
							}
						}
					}elseif($allFlag){
					    $model->setLastUpdatedBy ( $user_id );
						$model->setLastUpdatedAt ( $date_time );
						$model = $model->save ();
						$customerLanguageMapper = new Admin_Model_Mapper_CustomerLanguage ();
						$customerLanguageModel = $customerLanguageMapper->fetchAll ( "customer_id = " . $customer_id );
						$typesDetailMapper = new Events_Model_Mapper_ModuleEventsTypesDetail();
						$typesDetails = $typesDetailMapper->getDbTable()->fetchAll("module_events_types_id =".$arrFormValues['module_events_types_id'])->toArray();
						if($arrFormValues['module_events_types_detail_id'] != null){
						    $currentDetails = $typesDetailMapper->getDbTable()->fetchAll("module_events_types_detail_id =".$arrFormValues['module_events_types_detail_id'])->toArray();
						}else{
						    $currentDetails = $typesDetailMapper->getDbTable()->fetchAll("module_events_types_id ='".$arrFormValues['module_events_types_id']."' AND language_id =".$default_lang_id)->toArray();
						}
						if(is_array($currentDetails)){
							if(!isset($arrFormValues['icon'])){
								$arrFormValues['icon'] = $currentDetails[0]['icon'];
							}
						}
						unset($arrFormValues['module_events_types_detail_id'],$arrFormValues['language_id']);
						if(count($typesDetails) == count($customerLanguageModel)){
						    foreach ($typesDetails as $typesDetail) {
						        $typesDetail = array_intersect_key($arrFormValues + $typesDetail, $typesDetail);
						        $typesDetailModel = new Events_Model_ModuleEventsTypesDetail($typesDetail);
						        $typesDetailModel = $typesDetailModel->save();
						    }    
						}else{
						    $typesDetailMapper = new Events_Model_Mapper_ModuleEventsTypesDetail();
						    $typesDetails = $typesDetailMapper->fetchAll("module_events_types_id =".$arrFormValues['module_events_types_id']);
						    foreach ($typesDetails as $typesDetail){
						        $typesDetail->delete();
						    }
						    if (is_array ( $customerLanguageModel )) {
						        foreach ( $customerLanguageModel as $languages ) {
						            $typesDetailModel = new Events_Model_ModuleEventsTypesDetail($arrFormValues);
						            $typesDetailModel->setLanguageId ( $languages->getLanguageId () );
						            $typesDetailModel->setCreatedBy ( $user_id );
						            $typesDetailModel->setCreatedAt ( $date_time );
						            $typesDetailModel->setLastUpdatedBy ( $user_id );
						            $typesDetailModel->setLastUpdatedAt ( $date_time );
						            $typesDetailModel = $typesDetailModel->save ();
						        }
						    }
						}
					} else {
						// Edit Category
						$model->setLastUpdatedBy ( $user_id );
						$model->setLastUpdatedAt ( $date_time );
						$model = $model->save ();
						
						$modelDetails = new Events_Model_ModuleEventsTypesDetail($arrFormValues);
						if(!$modelDetails || $modelDetails->getModuleEventsTypesDetailId()=="") {
							$modelDetails->setCreatedBy ( $user_id );
							$modelDetails->setCreatedAt ( $date_time );
						}
						$modelDetails->setLastUpdatedBy ( $user_id );
						$modelDetails->setLastUpdatedAt ( $date_time );
						$modelDetails = $modelDetails->save();
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
					if($model && $model->getModuleEventsTypesId()!="") {
						$response = array (
								"success" => $model->toArray ()
						);
					}
				} catch (Exception $ex) {
					$response = array (
							"errors" => $ex->getMessage()
					);
					try {
						$mapper->getDbTable()->getAdapter()->rollBack();
					} catch (Exception $e) {}
				}
			} else {
    			$error ="";
    			$messages = $form->getMessages();
    			foreach ($messages as $key=>$msg) {
    				$error .= "<br>".$key.": ";
    				if(is_array($msg)) {
    					foreach($msg as $m) {
    						$error .= $m."<br>";
    					}
    				} else {
    					$error .= $msg;
    				}
    			}
    			$response = array (
    					"errors" => $error
    			);
    		}
		}
		$this->_helper->json ( $response );
	}
	
	public function deleteAction() {
		$this->_helper->layout ()->disableLayout ();
		$this->_helper->viewRenderer->setNoRender ();
		$request = $this->getRequest ();
	
		if (($module_events_types_id = $request->getParam ( "id", "" )) != "") {
			$module_events_types = new Events_Model_ModuleEventsTypes();
			$module_events_types->populate($module_events_types_id);
			if($module_events_types) {
				$mapper = new Events_Model_Mapper_ModuleEventsTypesDetail();
				$mapper->getDbTable()->getAdapter()->beginTransaction();
				try {
					$detailsMapper = new Events_Model_Mapper_ModuleEventsTypesDetail();
					$details = $detailsMapper->fetchAll("module_events_types_id=".$module_events_types->getModuleEventsTypesId());
					if(is_array($details)) {
						foreach($details as $PushMessageDetail) {
							$PushMessageDetail->delete();
						}
					}
	
					$deletedRows = $module_events_types->delete ();
	
					// set is pulish to false
					$customerId = Standard_Functions::getCurrentUser ()->customer_id;
					$customermoduleMapper = new Admin_Model_Mapper_CustomerModule();
					$customermodule = $customermoduleMapper->fetchAll("customer_id=".$customerId." AND module_id=".$this->_module_id);
					if(is_array($customermodule)) {
						$customermodule = $customermodule[0];
						$customermodule->setIsPublish("NO");
						$customermodule->save();
					}
	
					$mapper->getDbTable()->getAdapter()->commit();
	
					$response = array (
							"success" => array (
									"deleted_rows" => $deletedRows
							)
					);
	
				} catch (Exception $ex) {
					$mapper->getDbTable()->getAdapter()->rollBack();
					$response = array (
							"errors" => array (
									"message" => $ex->getMessage ()
							)
					);
				}
			} else {
				$response = array (
						"errors" => array (
								"message" => "No events to delete."
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
		$request = $this->getRequest();
		
		$active_lang_id = Standard_Functions::getCurrentUser ()->active_language_id;
		$default_lang_id = Standard_Functions::getCurrentUser ()->default_language_id;
		$customer_id = Standard_Functions::getCurrentUser ()->customer_id;
		//$parent_id = $request->getParam ( "parent_id", 0 );
		
		$mapper = new Events_Model_Mapper_ModuleEventsTypes();
		
		$select = $mapper->getDbTable ()->
							select ( false )->
							setIntegrityCheck ( false )->
							from ( array ("dc" => "module_events_types"),
									array (
										"dc.module_events_types_id" => "module_events_types_id",
										"dc.status" => "status",
										"dc.order" => "order"))->
							joinLeft ( array ("dcd" => "module_events_types_detail"),
										"dcd.module_events_types_id = dc.module_events_types_id AND dcd.language_id = ".$active_lang_id,
										array (
												"dcd.module_events_types_detail_id" => "module_events_types_detail_id",
												"dcd.title" => "title"
										))->
							where("dc.customer_id=".$customer_id)->order("dc.order");

		$response = $mapper->getGridData ( array (
										'column' => array (
												'id' => array (
														'actions'
												),
												'replace' => array (
														'dc.status' => array (
																'1' => $this->view->translate('Active'),
																'0' => $this->view->translate('Inactive')
														)
												)
										)
								),null, $select );
		
		$mapper = new Admin_Model_Mapper_CustomerLanguage();
		$select = $mapper->getDbTable ()->
							select ( false )->
							setIntegrityCheck ( false )->
							from ( array ("l" => "language"), array (
									"l.language_id" => "language_id",
									"l.title" => "title",
									"logo" => "logo") )->
							joinLeft ( array ("cl" => "customer_language"), "l.language_id = cl.language_id",
									array ("cl.customer_id") )->
							where("cl.customer_id=".Standard_Functions::getCurrentUser ()->customer_id);
		$languages = $mapper->getDbTable ()->fetchAll($select)->toArray();
		
		$rows = $response ['aaData'];
		
		foreach ( $rows as $rowId => $row ) {
			$edit = array();
			if($row [3] ["dcd.module_events_types_detail_id"]=="") {
				$mapper = new Events_Model_Mapper_PushMessageDetail();
				$details = $mapper->fetchAll("module_events_types_id=".$row [3] ["dc.module_events_types_id"]." AND language_id=".$default_lang_id);
				if(is_array($details)) {
					$details = $details[0];
					$row [3] ["dcd.title"] = $row[0] = $details->getTitle();
				}
			}
		
			$response ['aaData'] [$rowId] = $row;
			if($languages) {
				foreach ($languages as $lang) {
					$editUrl = $this->view->url ( array (
							"module" => "events",
							"controller" => "types",
							"action" => "edit",
							"id" => $row [3] ["dc.module_events_types_id"],
							"lang" => $lang["l.language_id"]
					), "default", true );
					$edit[] = '<a href="'. $editUrl .'"><img src="'.$this->view->baseUrl('images/lang/'.$lang["logo"]).'" alt="'.$lang["l.title"].'" /></a>';
				}
			}
			$deleteUrl = $this->view->url ( array (
					"module" => "events",
					"controller" => "types",
					"action" => "delete",
					"id" => $row [3] ["dc.module_events_types_id"]
			), "default", true );
			$defaultEdit = '<div id="editLanguage">&nbsp;<div class="flag-list">'.implode("",$edit).'</div></div>';
			$delete = '<a href="' . $deleteUrl . '" class="grid_delete button-grid greay" >'.$this->view->translate('Delete').'</a>';
			$sap = '';
		
			$response ['aaData'] [$rowId] [3] = $defaultEdit. $sap .$delete;
		}
		
		$jsonGrid = Zend_Json::encode ( $response );
		$this->_response->appendBody ( $jsonGrid );
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
			$response = array();
	
			$order = $this->_request->getParam ("order");
	
			$mapper = new Events_Model_Mapper_ModuleEventsTypes();
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
				$response = array (
						"success" => true
				);
			}catch(Exception $e) {
				$mapper->getDbTable()->getAdapter()->rollBack();
				$response = array (
						"errors" => $e->getMessage()
				);
			}
			echo Zend_Json::encode($response);
			exit;
		}
		
		$customer_id = Standard_Functions::getCurrentUser ()->customer_id;
		$language_id = Standard_Functions::getCurrentUser ()->active_language_id;
		
		$mapper = new Events_Model_Mapper_ModuleEventsTypes();
		$detailMapper = new Events_Model_Mapper_ModuleEventsTypesDetail();
		$request = $this->getRequest();
		
		$select = $mapper->getDbTable ()->
							select ( false )->
							setIntegrityCheck ( false )->
							from ( array ("dc" => "module_events_types"),
								array (
										"dc.module_events_types_id" => "module_events_types_id",
										"dc.status" => "status",
										"dc.order" => "order"))->
							joinLeft ( array ("dcd" => "module_events_types_detail"),
								"dcd.module_events_types_id = dc.module_events_types_id AND dcd.language_id = ".$active_lang_id,
								array (
										"dcd.module_events_types_detail_id" => "module_events_types_detail_id",
										"dcd.title" => "title"
							))->
							where("dc.customer_id=".$customer_id)->order("dc.order");
		 
		$response = $mapper->getDbTable()->fetchAll($select)->toArray();
		$this->view->data = $response;
	}
}