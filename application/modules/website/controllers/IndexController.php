<?php
class Website_IndexController extends Zend_Controller_Action {
	var $_module_id;
	public function init() {
		/* Initialize Action Controller Here.. */
		$modulesMapper = new Admin_Model_Mapper_Module ();
		$module = $modulesMapper->fetchAll ( "name ='website'" );
		if (is_array ( $module )) {
			$this->_module_id = $module [0]->getModuleId ();
		}
	}
	public function indexAction() {
		$active_lang_id = Standard_Functions::getCurrentUser ()->active_language_id;
		$this->view->addlink = $this->view->url ( array (
				"module" => "website",
				"controller" => "index",
				"action" => "add" 
		), "default", true );
		$this->view->reorderlink = $this->view->url ( array (
				"module" => "website",
				"controller" => "index",
				"action" => "reorder"
		), "default", true );
	}
	
	public function addAction(){
		$form = new Website_Form_Website();
		$action = $this->view->url ( array (
				"module" => "website",
				"controller" => "index",
				"action" => "save"
		), "default", true );
		$this->view->assign ( array (
				"partial" => "index/partials/add.phtml"
		) );
		$form->setAction($action);
		$form->setMethod ( 'POST' );
		$this->view->form = $form;
		$this->render("add-edit");
	}
	
	public function saveAction(){
		$this->_helper->layout ()->disableLayout ();
		$this->_helper->viewRenderer->setNoRender ();
		$form = new Website_Form_Website();
		$request = $this->getRequest();
		$response = array();
		if($this->_request->isPost()){
			if ($request->getParam ( "upload", "" ) != "") {
				$adapter = new Zend_File_Transfer_Adapter_Http ();
				$adapter->setDestination ( Standard_Functions::getResourcePath () . "website/logos" );
				$adapter->receive ();
				if ($adapter->getFileName ( "website_logo" ) != "") {
					$response = array (
							"success" => array_pop ( explode ( '/', $adapter->getFileName ( "website_logo" ) ) ) 
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
			$form->removeElement("website_logo");
			if($form->isValid($this->_request->getParams())){
				try{
					$allFormValues = $form->getValues();
					$customer_id = Standard_Functions::getCurrentUser ()->customer_id;
					$user_id = Standard_Functions::getCurrentUser ()->user_id;
					$date_time = Standard_Functions::getCurrentDateTime ();
					$websiteMapper = new Website_Model_Mapper_ModuleWebsite ();
					$websiteMapper->getDbTable ()->getAdapter ()->beginTransaction ();
					$websiteModel = new Website_Model_ModuleWebsite( $allFormValues );
					$logo_path = $request->getParam ( "logo_path", "" );
					if ($request->getParam ( "module_website_id", "" ) == "") {
						// save Website
						$maxOrder = $websiteMapper->getNextOrder ( $customer_id );
						$websiteModel->setOrder ( $maxOrder + 1 );
						$websiteModel->setCustomerId ( $customer_id );
						$websiteModel->setCreatedBy ( $user_id );
						$websiteModel->setCreatedAt ( $date_time );
						$websiteModel->setLastUpdatedBy ( $user_id );
						$websiteModel->setLastUpdatedAt ( $date_time );
						$websiteModel = $websiteModel->save ();
						
						// save website details
						$module_website_id = $websiteModel->get ( 'module_website_id' );
						$customerLanguageMapper = new Admin_Model_Mapper_CustomerLanguage ();
						$customerLanguageModel = $customerLanguageMapper->fetchAll ( "customer_id = " . $customer_id );
						if (is_array ( $customerLanguageModel )) {
							foreach ( $customerLanguageModel as $languages ) {
								$websiteDetailModel = new Website_Model_ModuleWebsiteDetail ( $allFormValues );
								$websiteDetailModel->setModuleWebsiteId ( $module_website_id );
								$websiteDetailModel->setLanguageId ( $languages->getLanguageId () );
								$websiteDetailModel->setCreatedBy ( $user_id );
								$websiteDetailModel->setCreatedAt ( $date_time );
								$websiteDetailModel->setLastUpdatedBy ( $user_id );
								$websiteDetailModel->setLastUpdatedAt ( $date_time );
								if ($logo_path != "") {
									$websiteDetailModel->setWebsiteLogo ($logo_path);
								}
								$websiteDetailModel = $websiteDetailModel->save ();
							}
						}
					}else {
						$websiteModel->setLastUpdatedBy ( $user_id );
						$websiteModel->setLastUpdatedAt ( $date_time );
						$websiteModel = $websiteModel->save ();
						
						$websiteDetailModel = new Website_Model_ModuleWebsiteDetail ( $allFormValues );
						$websiteDetailModel->setCreatedBy ( $user_id );
						$websiteDetailModel->setCreatedAt ( $date_time );
						$websiteDetailModel->setLastUpdatedBy ( $user_id );
						$websiteDetailModel->setLastUpdatedAt ( $date_time );
						if ($logo_path != "") {
							$websiteDetailModel->setWebsiteLogo ($logo_path);
						}
						$websiteDetailModel = $websiteDetailModel->save ();
					}
					$customermoduleMapper = new Admin_Model_Mapper_CustomerModule();
					$customermodule = $customermoduleMapper->fetchAll("customer_id=". $customer_id ." AND module_id=".$this->_module_id);
					if(is_array($customermodule)) {
						$customermodule = $customermodule[0];
						$customermodule->setIsPublish("NO");
						$customermodule->save();
					}
					$websiteMapper->getDbTable ()->getAdapter ()->commit ();
						
					$response = array (
							"success" => $websiteModel->toArray ()
					);
						
				} catch(Exception $ex){
					$response = $ex->getMessage();
				}
			} else{
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
	
	public function gridAction(){
		$this->_helper->layout ()->disableLayout ();
		$this->_helper->viewRenderer->setNoRender ();
		$active_lang_id = Standard_Functions::getCurrentUser ()->active_language_id;
		$default_lang_id = Standard_Functions::getCurrentUser ()->default_language_id;
		$websiteMapper = new Website_Model_Mapper_ModuleWebsite();
		$select = $websiteMapper->getDbTable ()->select ( false )->setIntegrityCheck ( false )->from ( array (
				"w" => "module_website"
		), array (
				"w.module_website_id" => "module_website_id",
				"w.status" => "status",
				"w.order" => "order"
		) )->joinLeft ( array (
				"wd" => "module_website_detail"
		), "wd.module_website_id = w.module_website_id AND wd.language_id=" . $active_lang_id, array (
				"wd.module_website_detail_id" => "module_website_detail_id",
				"wd.title" => "title",
				"wd.url" => "url"
		) )->where ( "w.customer_id=" . Standard_Functions::getCurrentUser ()->customer_id );
		
		$response = $websiteMapper->getGridData ( array (
				'column' => array (
						'id' => array (
								'actions'
						),
						'replace' => array (
								'w.status' => array (
										'1' => $this->view->translate ( 'Active' ),
										'0' => $this->view->translate ( 'Inactive' )
								)
						)
				)
		), null, $select );
		$customerLanguageMapper = new Admin_Model_Mapper_CustomerLanguage ();
		$select = $customerLanguageMapper->getDbTable ()->select ( false )->setIntegrityCheck ( false )->from ( array (
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
		$languages = $customerLanguageMapper->getDbTable ()->fetchAll ( $select )->toArray ();
		$rows = $response ['aaData'];
		foreach ( $rows as $rowId => $row ) {
			$edit = array ();
			if($row[4]['wd.module_website_detail_id'] == ""){
				$mapper = new Website_Model_Mapper_ModuleWebsiteDetail();
				$details = $mapper->fetchAll("module_website_id=".$row[4]["m.module_website_id"]." AND language_id=".$default_lang_id);
				if(is_array($details)){
					$details = $details[0];
					$row[4][wd.title] = $row[0] = $details->getTitle();
					$row[4][wd.url] = $details->getUrl();
				}
			}
			$response['aaData'][$rowId] = $row;
			if ($languages) {
				foreach ( $languages as $lang ) {
					$editUrl = $this->view->url ( array (
							"module" => "website",
							"controller" => "index",
							"action" => "edit",
							"id" => $row [4] ["w.module_website_id"],
							"lang" => $lang ["l.language_id"]
					), "default", true );
					$edit [] = '<a href="' . $editUrl . '" ><img src="images/lang/' . $lang ["logo"] . '" alt="' . $lang ["l.title"] . '" /></a>';
				}
			}
			$deleteUrl = $this->view->url ( array (
					"module" => "website",
					"controller" => "index",
					"action" => "delete",
					"id" => $row [4] ["w.module_website_id"]
			), "default", true );
			$defaultEdit = '<div id="editLanguage">&nbsp;<div class="flag-list">'.implode("",$edit).'</div></div>';
			$delete = '<a href="' . $deleteUrl . '" class="button-grid greay grid_delete" >'.$this->view->translate('Delete').'</a>';
			$sap = '';
			$response ['aaData'] [$rowId] [4] = $defaultEdit . $sap . $delete;
		}
		$jsonGrid = Zend_Json::encode ( $response );
		$this->_response->appendBody ( $jsonGrid );
	}
	
	public function editAction(){
		$form = new Website_Form_Website ();
		$request = $this->getRequest ();
		if ($request->getParam ( "id", "" ) != "" && $request->getParam ( "lang", "" ) != "") {
			$websiteMapper = new Website_Model_Mapper_ModuleWebsite ();
			$module_website_id = $request->getParam ( "id", "" );
			$language_id = $request->getParam ( "lang", "" );
			$languageMapper = new Admin_Model_Mapper_Language ();
			$languageData = $languageMapper->find ( $language_id );
			$this->view->language = $languageData->getTitle ();
			$default_lang_id = Standard_Functions::getCurrentUser ()->default_language_id;
			$data = $websiteMapper->find ( $module_website_id )->toArray ();
			$form->populate ( $data );
			$datadetails = array ();
			$websiteDetailMapper = new Website_Model_Mapper_ModuleWebsiteDetail ();
			if ($websiteDetailMapper->countAll ( "module_website_id = " . $module_website_id . " AND language_id = " . $language_id ) > 0) {
				// Record For Language Found
				$dataDetails = $websiteDetailMapper->getDbTable ()->fetchAll ( "module_website_id = " . $module_website_id . " AND language_id = " . $language_id )->toArray ();
			} else {
				// Record For Language Not Found
				$dataDetails = $websiteDetailMapper->getDbTable ()->fetchAll ( "module_website_id = " . $module_website_id . " AND language_id = " . $default_lang_id )->toArray ();
				$dataDetails [0] ["module_website_detail_id"] = "";
				$dataDetails [0] ["language_id"] = $language_id;		
			}
			if (isset ( $dataDetails [0] ) && is_array ( $dataDetails [0] )) {
				$form->populate ( $dataDetails [0] );
				$image_path = $dataDetails[0]['website_logo'];
				$image_uri = "resource/website/logos/";
				$this->view->image_thumb = $this->view->baseUrl($image_uri.$image_path); 
			}
			$action = $this->view->url ( array (
					"module" => "website",
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
		$this->render ( "add-edit" );
	}
	
	public function deleteAction(){
		$this->_helper->layout ()->disableLayout ();
		$this->_helper->viewRenderer->setNoRender ();
		$request = $this->getRequest ();
		
		if (($module_website_id = $request->getParam ( "id", "" )) != "") {
			$WebsiteModel = new Website_Model_ModuleWebsite ();
			$WebsiteModel->populate ( $module_website_id );
			if ($WebsiteModel) {
				try {
					$WebsiteDetailMapper = new Website_Model_Mapper_ModuleWebsiteDetail ();
					$WebsiteDetailMapper->getDbTable ()->getAdapter ()->beginTransaction ();
						
					$dataDetails = $WebsiteDetailMapper->fetchAll ( "module_website_id = " . $WebsiteModel->getModuleWebsiteId () );
					foreach ( $dataDetails as $websiteDetail ) {
						$websiteDetail->delete ();
					}
						
					$deletedRows = $WebsiteModel->delete ();
						
					$customer_id = Standard_Functions::getCurrentUser ()->customer_id;
					$customermoduleMapper = new Admin_Model_Mapper_CustomerModule();
					$customermodule = $customermoduleMapper->fetchAll("customer_id=". $customer_id ." AND module_id=".$this->_module_id);
					if(is_array($customermodule)) {
						$customermodule = $customermodule[0];
						$customermodule->setIsPublish("NO");
						$customermodule->save();
					}
						
					$WebsiteDetailMapper->getDbTable ()->getAdapter ()->commit ();
						
					$response = array (
							"success" => array (
									"deleted_rows" => $deletedRows
							)
					);
				} catch ( Exception $e ) {
						
					$WebsiteDetailMapper->getDbTable ()->getAdapter ()->rollBack ();
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
	
	public function reorderAction(){
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
		
			$mapper = new Website_Model_Mapper_ModuleWebsite();
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
				if($model && $model->getModuleWebsiteId()!="") {
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
			
		$mapper = new Website_Model_Mapper_ModuleWebsite();
		$select = $mapper->getDbTable ()->
		select ( false )->
		setIntegrityCheck ( false )->
		from ( array (
				"w" => "module_website"
		), array (
				"w.module_website_id" => "module_website_id",
				"w.status" => "status",
				"w.order" => "order"
		) )->joinLeft ( array (
				"wd" => "module_website_detail"
		), "wd.module_website_id = w.module_website_id AND wd.language_id=" . $active_lang_id, array (
				"wd.module_website_detail_id" => "module_website_detail_id",
				"wd.title" => "title",
				"wd.url" => "url"
		) )->where ( "w.customer_id=" . $customer_id )->order("w.order");
		$response = $mapper->getDbTable()->fetchAll($select)->toArray();
		$this->view->data = $response;
	}
}