<?php
class Admin_VersionController extends Zend_Controller_Action{
	public function indexAction() {
		$active_lang_id = Standard_Functions::getCurrentUser ()->active_language_id;
		$this->view->addlink = $this->view->url ( array (
				"module" => "admin",
				"controller" => "version",
				"action" => "add" 
		), "default", true );
	}

	public function addAction(){
		$form = new Admin_Form_Version();
		$action = $this->view->url ( array (
				"module" => "admin",
				"controller" => "version",
				"action" => "save"
		), "default", true );
		$this->view->assign ( array (
				"partial" => "version/partials/add.phtml"
		) );
		$form->setAction($action);
		$this->view->form = $form;
		$this->render ( "add-edit" );
	}

	public function saveAction(){
		$form = new Admin_Form_Version ();
		$request = $this->getRequest ();
		$response = array ();
		if ($this->_request->isPost ()) {
			if ($form->isValid ( $this->_request->getParams ())) {
				try {
					$versionMapper = new Admin_Model_Mapper_Version ();
					$versionMapper->getDbTable ()->getAdapter ()->beginTransaction ();
					$allFormValues = $form->getValues ();
					$date_time =  Standard_Functions::getCurrentDateTime ();
					$system_user = Standard_Functions::getCurrentUser ()->system_user_id;
					if(is_array($allFormValues['category'])){
							$categoryArray = $allFormValues['category'];
							$csv ="";
							foreach ($categoryArray as $category) {
								if($category != ""){
									$csv .= $category;
									$csv .= ',';
								}
							}
						$allFormValues['category'] = $csv;
					}
					if($allFormValues["created_at"] != ""){
                      	$start_date = DateTime::createFromFormat ( "d/m/Y H:i", $allFormValues["created_at"] );
	                       	if($start_date){
	                            $allFormValues["created_at"] = $start_date->format ( "Y-m-d H:i:s" );
	                        }
	                }else{
	                        unset($allFormValues["created_at"]);
	                }
	                $versionModel = new Admin_Model_Version ( $allFormValues );
					if ($request->getParam ( "version_id", "" ) == "") {
						// save push message
						$versionModel->setCreatedBy ( $system_user );
						$versionModel->setLastUpdatedBy ( $system_user );
						$versionModel->setLastUpdatedAt ( $date_time );
						$versionModel = $versionModel->save ();
						// save push message details
						$version_id = $versionModel->get ( 'version_id' );
						$LanguageMapper = new Admin_Model_Mapper_Language ();
						$LanguageModel = $LanguageMapper->fetchAll ();
						if (is_array ( $LanguageModel )) {
							foreach ( $LanguageModel as $languages ) {
								$versionDetailModel = new Admin_Model_VersionDetail ( $allFormValues );
								$versionDetailModel->setVersionId ( $version_id );
								$versionDetailModel->setLanguageId ( $languages->getLanguageId ());
								$versionDetailModel = $versionDetailModel->save ();
							}
						}
					} else {
						$versionModel->setLastUpdatedBy ( $system_user );
						$versionModel->setLastUpdatedAt ( $date_time );
						$versionModel = $versionModel->save ();
						
						$versionDetailModel = new Admin_Model_VersionDetail ( $allFormValues );
						//$pushMessageDetailModel->setMessageDate($date_time);
						$versionDetailModel = $versionDetailModel->save ();
					}
					$versionMapper->getDbTable ()->getAdapter ()->commit ();
					
					$response = array (
							"success" => $versionModel->toArray () 
					);
				} catch ( Exception $ex ) {
					$response = $ex->getMessage();
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

	public function gridAction()
    {
    	$active_lang_id = Standard_Functions::getAdminActiveLanguage()->language_id;
    	$LanguageMapper = new Admin_Model_Mapper_Language ();
    	$languages = $LanguageMapper->getDbTable()->fetchAll ()->toArray();
    	$this->_helper->layout ()->disableLayout ();
    	$this->_helper->viewRenderer->setNoRender ();
    	$mapper = new Admin_Model_Mapper_Version();
    	$select = $mapper->getDbTable ()
				    	->select ( false )
				    	->setIntegrityCheck ( false )
				    	->from ( array ("v" => "version"),
				    			array (
				    					"v.version_id" => "version_id",
				    					"v.created" => "created_at",
				    					"v.status" => "status",
				    					"v.created_at" => "created_at"
				 				) 
				    		)
    					->joinLeft ( array ("vd" => "version_detail"),
    						"vd.version_id = v.version_id AND language_id =".$active_lang_id,
    									array (
		    									"vd.title" => "title",
		    									"vd.version_number" => "version_number",
		    									"vd.language_id" => "language_id",
		    									"vd.description" => "description",
		    							));								
    	
    	$response = $mapper->getGridData(array (
							'column' => array (
									'id' => array (
											'actions' 
									),
							'replace' => array (
									'v_status' => array (
											'1' => 'Active',
											'0' => 'Inactive' 
									) 
							))
					),null,$select);
    	
    	$rows = $response ['aaData'];
    	foreach ( $rows as $rowId => &$row ) {
    		$edit = "";
    		if($languages) {
    			foreach ($languages as $lang) {
					$editUrl = $this->view->url ( array (
													"module" => "admin",
													"controller" => "version",
													"action" => "edit",
													"id" => $row [4] ["v.version_id"],
													"lang" => $lang["language_id"]
											), "default", true );
					$edit[] = '<a href="'. $editUrl .'"><img src="/images/lang/'.$lang["logo"].'" alt="'.$lang["title"].'" /></a>';
    			}
    		}
    		$deleteUrl = $this->view->url ( array (
    				"module" => "admin",
    				"controller" => "version",
    				"action" => "delete",
    				"id" => $row [4] ["v.version_id"]
    		), "default", true );
			$response ['aaData'] [$rowId] [3] = strip_tags($row[3]);
			$defaultEdit = '<div id="editLanguage">&nbsp;<div class="flag-list">'.implode("",$edit).'</div></div>';
			$delete = '<a href="' . $deleteUrl . '" class="button-grid greay grid_delete" >'.$this->view->translate('Delete').'</a>';
    		$response ['aaData'] [$rowId] [4] = $defaultEdit.$delete;
    	}
    	
    	$jsonGrid = Zend_Json::encode ( $response );
    	$this->_response->appendBody ( $jsonGrid );
    }

    public function editAction(){
    	// edit action
    	$active_lang_id = Standard_Functions::getAdminActiveLanguage()->language_id;
		$form = new Admin_Form_Version ();
		$request = $this->getRequest ();
		if ($request->getParam ( "id", "" ) != "" && $request->getParam ( "lang", "" ) != "") {
			$versionMapper = new Admin_Model_Mapper_Version ();
			$version_id = $request->getParam ( "id", "" );
			$language_id = $request->getParam ( "lang", "" );
			$languageMapper = new Admin_Model_Mapper_Language ();
			$languageData = $languageMapper->find ( $language_id );
			$this->view->language = $languageData->getTitle ();
			$data = $versionMapper->find ( $version_id )->toArray ();
			$form->populate ( $data );
			$dataDetails = array ();
			$versionDetailMapper = new Admin_Model_Mapper_VersionDetail ();
			if ($versionDetailMapper->countAll ( "version_id = " . $version_id . " AND language_id = " . $language_id ) > 0) {
				// Record For Language Found
				$dataDetails = $versionDetailMapper->getDbTable ()->fetchAll ( "version_id = " . $version_id . " AND language_id = " . $language_id )->toArray ();
				if($dataDetails[0]['category'] != ""){
					$category = $dataDetails[0]['category'];
					$categories = explode ( ",", $category );
					$dataDetails[0]['category'] = $categories;
				}
			} else {
				// Record For Language Not Found
				$dataDetails = $versionDetailMapper->getDbTable ()->fetchAll ( "version_id = " . $version_id . " AND language_id = " . $active_lang_id )->toArray ();
				$dataDetails [0] ["version_detail_id"] = "";
				$dataDetails [0] ["language_id"] = $language_id;
				//$dataDetails[0]['message_date'] = Standard_Functions::getCurrentDateTime ();
				
			}
			if (isset ( $dataDetails [0] ) && is_array ( $dataDetails [0] )) {
				$form->populate ( $dataDetails [0] );
			}
			$action = $this->view->url ( array (
					"module" => "admin",
					"controller" => "version",
					"action" => "save",
					"id" => $request->getParam ( "id", "" ) 
			), "default", true );
			$form->setAction ( $action );
		} else {
			$this->_redirect ( '/' );
		}
		$this->view->form = $form;
		$this->view->assign ( array (
				"partial" => "version/partials/edit.phtml" 
		) );
		$this->render ( "add-edit" );
    }

    public function deleteAction(){
    	$this->_helper->layout ()->disableLayout ();
		$this->_helper->viewRenderer->setNoRender ();
		$request = $this->getRequest ();
		
		if (($version_id = $request->getParam ( "id", "" )) != "") {
			$versionModel = new Admin_Model_Version ();
			$versionModel->populate ( $version_id );
			if ($versionModel) {
				try {    
					$versionDetailMapper = new Admin_Model_Mapper_VersionDetail ();
					$versionDetailMapper->getDbTable ()->getAdapter ()->beginTransaction ();
					
					$dataDetails = $versionDetailMapper->fetchAll ( "version_id = " . $versionModel->getVersionId () );
					foreach ( $dataDetails as $versionDetail ) {
						$versionDetail->delete ();
					}
					
					$deletedRows = $versionModel->delete ();
					$versionDetailMapper->getDbTable ()->getAdapter ()->commit ();
					
					$response = array (
							"success" => array (
									"deleted_rows" => $deletedRows 
							) 
					);
				} catch ( Exception $e ) {
					
					$versionDetailMapper->getDbTable ()->getAdapter ()->rollBack ();
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
}