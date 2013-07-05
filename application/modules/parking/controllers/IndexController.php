<?php
class Parking_IndexController extends Zend_Controller_Action
{
	var $_module_id;
	var $_customer_module_id;
	
	public function init()
	{
		/* Initialize action controller here */
		$modulesMapper = new Admin_Model_Mapper_Module();
		$module = $modulesMapper->fetchAll("name ='parking'");
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
		$image_dir = Standard_Functions::getResourcePath(). "parking/preset-icons";
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
				"module" => "parking",
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
				"module" => "parking",
				"controller" => "index",
				"action" => "reorder"
		), "default", true );
		$this->view->addtypes = $this->view->url(array (
				"module" => "parking",
				"controller" => "type",
				"action" => "index"
		), "default", true);
		
	}

	public function addAction()
	{
		// action body
		$lang_id = Standard_Functions::getCurrentUser ()->default_language_id;
		$language = new Admin_Model_Mapper_Language();
		$lang = $language->find($lang_id);
		$this->view->language = $lang->getTitle();

		$form = new Parking_Form_Parking();
		foreach ( $form->getElements () as $element ) {
			if ($element->getDecorator ( 'Label' ))
				$element->getDecorator ( 'Label' )->setTag ( null );
		}
		$action = $this->view->url ( array (
				"module" => "parking",
				"controller" => "index",
				"action" => "save"
		), "default", true );
		$form->setAction($action);
		$this->view->form = $form;
		$this->view->icon_path="";
		$customer_id = Standard_Functions::getCurrentUser ()->customer_id;
		
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
			$response = array();

			$order = $this->_request->getParam ("order");

			$mapper = new Parking_Model_Mapper_ModuleParking();
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
				if($model && $model->getModuleParkingId()!="") {
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
		
		$mapper = new Parking_Model_Mapper_ModuleParking();
		$select = $mapper->getDbTable ()->
						select ( false )->
						setIntegrityCheck ( false )->
						from ( array ("p" => "module_parking"),
								array (
										"p.module_parking_id" => "module_parking_id",
										"p.status" => "status",
										"p.order" => "order"))->
						joinLeft ( array ("pd" => "module_parking_detail"),
								"pd.module_parking_id = p.module_parking_id AND pd.language_id = ".$active_lang_id,
								array (
										"pd.module_parking_detail_id" => "module_parking_detail_id",
										"pd.name" => "name"
						))->
						where("p.customer_id=".$customer_id)->order("p.order");
		$response = $mapper->getDbTable()->fetchAll($select)->toArray();
		$this->view->data = $response;
	}

	public function editAction()
	{
		$form = new Parking_Form_Parking();
		$request = $this->getRequest ();
		if ($request->getParam ( "id", "" ) != "" && $request->getParam ( "lang", "" ) != "") {
			$parking_id = $request->getParam ( "id", "" );
			$lang_id = $request->getParam ( "lang", "" );
			$language = new Admin_Model_Mapper_Language();
			$lang = $language->find($lang_id);
			$this->view->language = $lang->getTitle();
			 
			$default_lang_id = Standard_Functions::getCurrentUser ()->default_language_id;
			 
			$mapper = new Parking_Model_Mapper_ModuleParking();
			$data = $mapper->find ( $parking_id )->toArray ();
			$form->populate ( $data );
			 
			$dataDetails = array();
			$details = new Parking_Model_Mapper_ModuleParkingDetail();
			 
			if($details->countAll("module_parking_id = ".$parking_id." AND language_id = ".$lang_id) > 0) {
				// Record For Language Found
				$dataDetails = $details->getDbTable()->fetchAll("module_parking_id = ".$parking_id." AND language_id = ".$lang_id)->toArray();
			} else {
				// Record For Language Not Found
				$dataDetails = $details->getDbTable()->fetchAll("module_parking_id = ".$parking_id." AND language_id = ".$default_lang_id)->toArray();
				$default_event_detail_id = $dataDetails[0]["module_parking_detail_id"];
				$dataDetails[0]["module_parking_detail_id"] = "";
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
			
			$modelParkingTypeMapper = new Parking_Model_Mapper_ModuleParkingTypeMapping();
			$modelParkingType = $modelParkingTypeMapper->fetchAll("module_parking_id=".$parking_id);
			if($modelParkingType) {
				$values = array();
				foreach($modelParkingType as $parking_type) {
					$values[] = $parking_type->getModuleParkingTypeId();
				}
				$form->getElement("parking_type")->setValue($values);
			}
			
			foreach ( $form->getElements () as $element ) {
				if ($element->getDecorator ( 'Label' ))
					$element->getDecorator ( 'Label' )->setTag ( null );
			}
			 
			$action = $this->view->url ( array (
					"module" => "parking",
					"controller" => "index",
					"action" => "save",
					"id" => $request->getParam ( "id", "" )
			), "default", true );
			$form->setAction($action);
		} else {
			$this->_redirect ( '/' );
		}
		$customer_id = Standard_Functions::getCurrentUser ()->customer_id;
		 
		$this->view->form = $form;
		$this->view->assign ( array (
				"partial" => "index/partials/edit.phtml"
		) );
		$this->view->iconpack = $this->_iconpack;
		$this->render ( "add-edit" );
	}

	public function saveAction()
	{
		// action body
		$form = new Parking_Form_Parking();
		$request = $this->getRequest ();
		$response = array ();
		$default_lang_id = Standard_Functions::getCurrentUser ()->default_language_id;
		if ($this->_request->isPost ()) {
			if($request->getParam ( "iconupload", "" ) != "") {
				$adapter = new Zend_File_Transfer_Adapter_Http();
				$adapter->setDestination(Standard_Functions::getResourcePath(). "parking/uploaded-icons");
				$adapter->receive();
				if($adapter->getFileName("parking_logo")!="")
				{
					$response = array (
							"success" => array_pop(explode('/',str_replace("\\","/",$adapter->getFileName("parking_logo"))))
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
			$allFlag = $this->_request->getParam("all",false);
			if ($form->isValid ( $this->_request->getParams () )) {
				 
				$mapper = new Parking_Model_Mapper_ModuleParking();
				$mapper->getDbTable()->getAdapter()->beginTransaction();
				 
				try {
					$arrFormValues = $form->getValues();
					$customer_id = Standard_Functions::getCurrentUser ()->customer_id;
					$user_id = Standard_Functions::getCurrentUser ()->user_id;
					$date_time = Standard_Functions::getCurrentDateTime ();
					
					$model = new Parking_Model_ModuleParking($arrFormValues);
					$selIcon = $request->getParam("selLogo","0");
					
					$icon_path = $request->getParam("icon_path","");
					if($selIcon != "0"){
						$arrFormValues["icon"] = $selIcon;
					}elseif ($icon_path != ""){
						$arrFormValues["icon"] = "uploaded-icons/".$icon_path;
					}
					if($request->getParam ( "module_parking_id", "" ) == "") {
						// Add New Event
						$maxOrder = $mapper->getNextOrder($customer_id);
							
						$model->setOrder($maxOrder+1);
						$model->setCustomerId ( $customer_id );
						$model->setCreatedBy ( $user_id );
						$model->setCreatedAt ( $date_time );
						$model->setLastUpdatedBy ( $user_id );
						$model->setLastUpdatedAt ( $date_time );
						$model = $model->save ();
							
						// Save Parking Details
							
						$moduleParkingId = $model->getModuleParkingId();
						$mapperLanguage = new Admin_Model_Mapper_CustomerLanguage();
						$modelLanguages = $mapperLanguage->fetchAll("customer_id = ".$customer_id);
						if(is_array($modelLanguages)) {
							foreach($modelLanguages as $languages) {
								$modelDetails = new Parking_Model_ModuleParkingDetail($arrFormValues);
								$modelDetails->setModuleParkingId($moduleParkingId);
								$modelDetails->setLanguageId($languages->getLanguageId());
								$modelDetails->setCreatedBy ( $user_id );
								$modelDetails->setCreatedAt ( $date_time );
								$modelDetails->setLastUpdatedBy ( $user_id );
								$modelDetails->setLastUpdatedAt ( $date_time );
								$modelDetails = $modelDetails->save();
							}
						}
						$parkingTypes = $request->getParam ( "parking_type", "" );
						if($parkingTypes != "") {
							foreach($parkingTypes as $parking_type) {
								$modelParkingType = new Parking_Model_ModuleParkingTypeMapping();
								$modelParkingType->setModuleParkingId($moduleParkingId);
								$modelParkingType->setModuleParkingTypeId($parking_type);
								$modelParkingType->save();
							}
						}
					} else if ($allFlag){
						$model->setLastUpdatedBy ( $user_id );
						$model->setLastUpdatedAt ( $date_time);
						$model = $model->save ();
						
						$modelParkingTypeMapper = new Parking_Model_Mapper_ModuleParkingTypeMapping();
						$modelParkingType = $modelParkingTypeMapper->fetchAll("module_parking_id=".$arrFormValues["module_parking_id"]);
						if($modelParkingType) {
							foreach($modelParkingType as $parking_type) {
								$parking_type->delete();
							}
						}
						
						$parkingTypes = $request->getParam ( "parking_type", "" );
						if($parkingTypes != "") {
							foreach($parkingTypes as $parking_type) {
								$modelParkingType = new Parking_Model_ModuleParkingTypeMapping();
								$modelParkingType->setModuleParkingId($arrFormValues["module_parking_id"]);
								$modelParkingType->setModuleParkingTypeId($parking_type);
								$modelParkingType->save();
							}
						}
						
						$customerLanguageMapper = new Admin_Model_Mapper_CustomerLanguage ();
						$customerLanguageModel = $customerLanguageMapper->fetchAll ( "customer_id = " . $customer_id );
						$parkingdetailMapper = new Parking_Model_Mapper_ModuleParkingDetail();
						$parkingdetails = $parkingdetailMapper->getDbTable()->fetchAll("module_parking_id =".$arrFormValues["module_parking_id"])->toArray();
						if($arrFormValues["module_parking_detail_id"] != null){
							$currentparking = $parkingdetailMapper->getDbTable()->fetchAll("module_parking_detail_id =".$arrFormValues["module_parking_detail_id"])->toArray();
						}else{
							$currentparking = $parkingdetailMapper->getDbTable()->fetchAll("module_parking_id ='".$arrFormValues["module_parking_id"]."' AND language_id =".$default_lang_id)->toArray();
						}
						if(is_array($currentparking) && isset($arrFormValues['icon']) && !$arrFormValues['icon']){
							$arrFormValues['icon'] = $currentparking[0]['icon'];
						}
						unset($arrFormValues['module_parking_detail_id'],$arrFormValues['language_id']);
						if(count($parkingdetails) == count($customerLanguageModel)){
							foreach ($parkingdetails as $parkingdetail) {
								$parkingdetail = array_intersect_key($arrFormValues + $parkingdetail, $parkingdetail);
								$parkingDetailModel = new Parking_Model_ModuleParkingDetail($parkingdetail);
								$parkingDetailModel = $parkingDetailModel->save();
								//Updating locations
								$detail_id = $parkingDetailModel->get("module_parking_detail_id");
							}
						} else {
							$parkingDetailMapper = new Parking_Model_Mapper_ModuleParkingDetail();
							$parkingDetails = $eventDetailMapper->fetchAll("module_parking_id =".$arrFormValues['module_parking_id']);
							foreach ($parkingDetails as $parkingDetail){
								$parkingDetails->delete();
							}
							if (is_array ( $customerLanguageModel )) {
								foreach ( $customerLanguageModel as $languages ) {
									$parkingDetailModel = new Parking_Model_ModuleParkingDetail($arrFormValues);
									$parkingDetailModel->setLanguageId ( $languages->getLanguageId () );
									$parkingDetailModel->setCreatedBy ( $user_id );
									$parkingDetailModel->setCreatedAt ( $date_time );
									$parkingDetailModel->setLastUpdatedBy ( $user_id );
									$parkingDetailModel->setLastUpdatedAt ( $date_time );
									$parkingDetailModel = $eventDetailModel->save ();
									$detail_id = $parkingDetailModel->get("module_parking_detail_id");
								}
							}
						}
					} else {
						// Edit Parking
						$model->setLastUpdatedBy ( $user_id );
						$model->setLastUpdatedAt ( $date_time);
						$model = $model->save ();
						
						$modelParkingTypeMapper = new Parking_Model_Mapper_ModuleParkingTypeMapping();
						$modelParkingType = $modelParkingTypeMapper->fetchAll("module_parking_id=".$arrFormValues["module_parking_id"]);
						if($modelParkingType) {
							foreach($modelParkingType as $parking_type) {
								$parking_type->delete();
							}
						}
						
						$parkingTypes = $request->getParam ( "parking_type", "" );
						if($parkingTypes != "") {
							foreach($parkingTypes as $parking_type) {
								$modelParkingType = new Parking_Model_ModuleParkingTypeMapping();
								$modelParkingType->setModuleParkingId($arrFormValues["module_parking_id"]);
								$modelParkingType->setModuleParkingTypeId($parking_type);
								$modelParkingType->save();
							}
						}
						
						// Save Parking Details
							
						$modelDetails = new Parking_Model_ModuleParkingDetail($arrFormValues);
						$detail_id = $modelDetails->get('module_parking_detail_id');
						
						if(!$modelDetails || $modelDetails->getModuleParkingDetailId()=="") {
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
					if($model && $model->getModuleParkingId()!="") {
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
					} catch (Exception $e) {
							
					}
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
		 
		$this->_helper->json ( $response );
	}

	public function deleteAction() {
		$this->_helper->layout ()->disableLayout ();
		$this->_helper->viewRenderer->setNoRender ();
		$request = $this->getRequest ();
		$response = array();
		 
		if (($parkingId = $request->getParam ( "id", "" )) != "") {
			$parking = new Parking_Model_ModuleParking();
			$parking->populate($parkingId);
			if($parking) {
				$mapper = new Parking_Model_Mapper_ModuleParking();
				$mapper->getDbTable()->getAdapter()->beginTransaction();
				try {
					$detailsMapper = new Parking_Model_Mapper_ModuleParkingDetail();
					$details = $detailsMapper->fetchAll("module_parking_id=".$parking->getModuleParkingId());
					if(is_array($details)) {
						foreach($details as $parkingDetail) {
							$parkingDetail->delete();
						}
					}

					$deletedRows = $parking->delete ();

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
		$request = $this->getRequest ();
		 
		$active_lang_id = Standard_Functions::getCurrentUser ()->active_language_id;
		$default_lang_id = Standard_Functions::getCurrentUser ()->default_language_id;
		$customer_id = Standard_Functions::getCurrentUser ()->customer_id;
		 
		$mapper = new Parking_Model_Mapper_ModuleParking();
		$select = $mapper->getDbTable ()->
					select ( false )->
					setIntegrityCheck ( false )->
					from ( array ("p" => "module_parking"),
							array (
									"p.module_parking_id" => "module_parking_id",
									"p.total_capacity" => "total_capacity",
									"p.status" => "status",
									"p.order" => "order"))->
					joinLeft ( array ("pd" => "module_parking_detail"),
							"pd.module_parking_id = p.module_parking_id AND pd.language_id = ".$active_lang_id,
							array (
									"pd.module_parking_detail_id" => "module_parking_detail_id",
									"pd.name" => "name"
							))->
					joinLeft ( array ("pld" => "module_parking_live_data"),
							"pld.module_parking_id = p.module_parking_id",
							array ("pld.free_space"=>"free_space"))->
					where("p.customer_id=".$customer_id)->order("p.order");
					
		$response = $mapper->getGridData ( array (
				'column' => array (
						'id' => array (
								'actions'
						),
						'replace' => array (
								'p.status' => array (
										'1' => $this->view->translate('Active'),
										'0' => $this->view->translate('Inactive')
								)
						)
				)
		),"customer_id=".Standard_Functions::getCurrentUser ()->customer_id, $select );
		$records = $response['aaData'];
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
				if($row [5] ["pd.module_parking_detail_id"]=="") {
					$mapper = new Parking_Model_Mapper_ModuleParkingDetail();
					$details = $mapper->fetchAll("module_parking_id=".$row [5] ["p.module_parking_id"]." AND language_id=".$default_lang_id);
					if(is_array($details)) {
						$details = $details[0];
						$row [5] ["pd.name"] = $row[0] = $details->getName();
					}
				}
				$response ['aaData'] [$rowId] = $row;
				if($languages) {
					foreach ($languages as $lang) {
						$editUrl = $this->view->url ( array (
								"module" => "parking",
								"controller" => "index",
								"action" => "edit",
								"id" => $row [5] ["p.module_parking_id"],
								"lang" => $lang["l.language_id"]
						), "default", true );
						$edit[] = '<a href="'. $editUrl .'"><img src="images/lang/'.$lang["logo"].'" alt="'.$lang["l.title"].'" /></a>';
					}
				}
				$deleteUrl = $this->view->url ( array (
						"module" => "parking",
						"controller" => "index",
						"action" => "delete",
						"id" => $row [5] ["p.module_parking_id"]
				), "default", true );
				$defaultEdit = '<div id="editLanguage">&nbsp;<div class="flag-list">'.implode("",$edit).'</div></div>';
				$delete = '<a href="' . $deleteUrl . '" class="button-grid greay grid_delete" >'.$this->view->translate('Delete').'</a>';
				$sap = '';

				$response ['aaData'] [$rowId] [5] = $defaultEdit. $sap .$delete;
			}
			$jsonGrid = Zend_Json::encode ( $response );
			$this->_response->appendBody ( $jsonGrid );
	}
}