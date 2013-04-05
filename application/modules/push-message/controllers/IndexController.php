<?php
class PushMessage_IndexController extends Zend_Controller_Action {
	var $_module_id;
	public function init() {
		/* Initialize Action Controller Here.. */
		define("GOOGLE_API_KEY", "AIzaSyD0gczBUI3hOQQ4PAyToRQ2VMcGhRim_3Q");
		$modulesMapper = new Admin_Model_Mapper_Module();
		$module = $modulesMapper->fetchAll("name ='push-message'");
		if(is_array($module)) {
			$this->_module_id = $module[0]->getModuleId();
		}
	}
	public function indexAction() {
		$this->view->addlink = $this->view->url ( array (
				"module" => "push-message",
				"controller" => "index",
				"action" => "add" 
		), "default", true );
		$this->view->reorderlink = $this->view->url ( array (
				"module" => "push-message",
				"controller" => "index",
				"action" => "reorder"
		), "default", true );
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
	
			$mapper = new PushMessage_Model_Mapper_PushMessage();
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
				if($model && $model->getPushMessageId()!="") {
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
		 
		$mapper = new PushMessage_Model_Mapper_PushMessage();
		$select = $mapper->getDbTable ()->
							select ( false )->
							setIntegrityCheck ( false )->
							from ( array (
									"pm" => "module_push_message" 
							), array (
									"pm.push_message_id" => "push_message_id",
									"pm.status" => "status",
									"pm.order" => "order" 
							) )->joinLeft ( array (
									"pmd" => "module_push_message_detail" 
							), "pmd.push_message_id = pm.push_message_id AND pmd.language_id=" . $active_lang_id, array (
									"pmd.push_message_detail_id" => "push_message_detail_id",
									"pmd.title" => "title",
									"pmd.description" => "description" 
							) )->where ( "pm.customer_id=" . $customer_id )->order("pm.order");
		$response = $mapper->getDbTable()->fetchAll($select)->toArray();
		$this->view->data = $response;
	}
	public function gridAction() {
		$this->_helper->layout ()->disableLayout ();
		$this->_helper->viewRenderer->setNoRender ();
		
		$active_lang_id = Standard_Functions::getCurrentUser ()->active_language_id;
		$default_lang_id = Standard_Functions::getCurrentUser ()->default_language_id;
		$pushMessageMapper = new PushMessage_Model_Mapper_PushMessage ();
		
		$select = $pushMessageMapper->getDbTable ()->select ( false )->setIntegrityCheck ( false )->from ( array (
				"pm" => "module_push_message" 
		), array (
				"pm.push_message_id" => "push_message_id",
				"pm.status" => "status",
				"pm.order" => "order" 
		) )->joinLeft ( array (
				"pmd" => "module_push_message_detail" 
		), "pmd.push_message_id = pm.push_message_id AND pmd.language_id=" . $active_lang_id, array (
				"pmd.push_message_detail_id" => "push_message_detail_id",
				"pmd.title" => "title",
				"pmd.description" => "description",
				"pmd.message_date" => "message_date" 
		) )->where ( "pm.customer_id=" . Standard_Functions::getCurrentUser ()->customer_id );
		
		$response = $pushMessageMapper->getGridData ( array (
				'column' => array (
						'id' => array (
								'actions' 
						),
						'replace' => array (
								'pm.status' => array (
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
			if($row[4]['pmd.push_message_detail_id'] == ""){
				$mapper = new PushMessage_Model_Mapper_PushMessageDetail();
				$details = $mapper->fetchAll("push_message_id=".$row[5]["pm.push_message_id"]." AND language_id=".$default_lang_id);
				if(is_array($details)){
					$details = $details[0];
					$row[5][pmd.title] = $row[0] = $details->getTitle();
					$row[5][pmd.description] = $row[1] = $details->getDescription();
				}
			}
			$response['aaData'][$rowId] = $row;
			if ($languages) {
				foreach ( $languages as $lang ) {
					$editUrl = $this->view->url ( array (
							"module" => "push-message",
							"controller" => "index",
							"action" => "edit",
							"id" => $row [5] ["pm.push_message_id"],
							"lang" => $lang ["l.language_id"] 
					), "default", true );
					$edit [] = '<a href="' . $editUrl . '" ><img src="images/lang/' . $lang ["logo"] . '" alt="' . $lang ["l.title"] . '" /></a>';
				}
			}
			$deleteUrl = $this->view->url ( array (
					"module" => "push-message",
					"controller" => "index",
					"action" => "delete",
					"id" => $row [5] ["pm.push_message_id"] 
			), "default", true );
			$defaultEdit = '<div id="editLanguage">&nbsp;<div class="flag-list">'.implode("",$edit).'</div></div>';
			$delete = '<a href="' . $deleteUrl . '" class="button-grid greay grid_delete" >'.$this->view->translate('Delete').'</a>';
			$sap = '';
			$response ['aaData'] [$rowId] [5] = $defaultEdit . $sap . $delete;
		}
		$jsonGrid = Zend_Json::encode ( $response );
		$this->_response->appendBody ( $jsonGrid );
	}
	public function addAction() {
		// add Action
		$form = new PushMessage_Form_PushMessage ();
		$action = $this->view->url ( array (
				"module" => "push-message",
				"controller" => "index",
				"action" => "save" 
		), "default", true );
		$form->setAction ( $action );
		$this->view->form = $form;
		$this->view->assign ( array (
				"partial" => "index/partials/add.phtml" 
		) );
		$this->render ( "add-edit" );
	}
	public function editAction() {
		// edit action
		$form = new PushMessage_Form_PushMessage ();
		$request = $this->getRequest ();
		if ($request->getParam ( "id", "" ) != "" && $request->getParam ( "lang", "" ) != "") {
			$pushMessageMapper = new PushMessage_Model_Mapper_PushMessage ();
			$push_message_id = $request->getParam ( "id", "" );
			$language_id = $request->getParam ( "lang", "" );
			$languageMapper = new Admin_Model_Mapper_Language ();
			$languageData = $languageMapper->find ( $language_id );
			$this->view->language = $languageData->getTitle ();
			$default_lang_id = Standard_Functions::getCurrentUser ()->default_language_id;
			$data = $pushMessageMapper->find ( $push_message_id )->toArray ();
			$form->populate ( $data );
			$datadetails = array ();
			$pushMessageDetailMapper = new PushMessage_Model_Mapper_PushMessageDetail ();
			if ($pushMessageDetailMapper->countAll ( "push_message_id = " . $push_message_id . " AND language_id = " . $language_id ) > 0) {
				// Record For Language Found
				$dataDetails = $pushMessageDetailMapper->getDbTable ()->fetchAll ( "push_message_id = " . $push_message_id . " AND language_id = " . $language_id )->toArray ();
				//$dataDetails[0]['message_date'] = Standard_Functions::getCurrentDateTime ();
			} else {
				// Record For Language Not Found
				$dataDetails = $pushMessageDetailMapper->getDbTable ()->fetchAll ( "push_message_id = " . $push_message_id . " AND language_id = " . $default_lang_id )->toArray ();
				$dataDetails [0] ["push_message_detail_id"] = "";
				$dataDetails [0] ["language_id"] = $language_id;
				//$dataDetails[0]['message_date'] = Standard_Functions::getCurrentDateTime ();
				
			}
			if (isset ( $dataDetails [0] ) && is_array ( $dataDetails [0] )) {
				$form->populate ( $dataDetails [0] );
			}
			$action = $this->view->url ( array (
					"module" => "push-message",
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
	public function saveAction() {
		$form = new PushMessage_Form_PushMessage ();
		$request = $this->getRequest ();
		$response = array ();
		if ($this->_request->isPost ()) {
			if ($form->isValid ( $this->_request->getParams () )) {
				try {
					$customer_id = Standard_Functions::getCurrentUser ()->customer_id;
					$user_id = Standard_Functions::getCurrentUser ()->user_id;
					$date_time = Standard_Functions::getCurrentDateTime ();
					$pushMessageMapper = new PushMessage_Model_Mapper_PushMessage ();
					$pushMessageMapper->getDbTable ()->getAdapter ()->beginTransaction ();
					$allFormValues = $form->getValues ();
					$pushMessageModel = new PushMessage_Model_PushMessage ( $allFormValues );
					if($allFormValues["message_date"] != ""){
	                        $message_date = DateTime::createFromFormat ( "d/m/Y H:i", $allFormValues["message_date"] );
	                        if($message_date){
	                        	$allFormValues["message_date"] = $message_date->format ( "Y-m-d H:i:s" ) ;
	                        }
	                }else{
	                    unset($allFormValues["message_date"]);
	                }
					if ($request->getParam ( "push_message_id", "" ) == "") {
						// save push message
						$maxOrder = $pushMessageMapper->getNextOrder ( $customer_id );
						$pushMessageModel->setOrder ( $maxOrder + 1 );
						$pushMessageModel->setCustomerId ( $customer_id );
						$pushMessageModel->setCreatedBy ( $user_id );
						$pushMessageModel->setCreatedAt ( $date_time );
						$pushMessageModel->setLastUpdatedBy ( $user_id );
						$pushMessageModel->setLastUpdatedAt ( $date_time );
						$pushMessageModel = $pushMessageModel->save ();
						
						// save push message details
						$push_message_id = $pushMessageModel->get ( 'push_message_id' );
						$customerLanguageMapper = new Admin_Model_Mapper_CustomerLanguage ();
						$customerLanguageModel = $customerLanguageMapper->fetchAll ( "customer_id = " . $customer_id );
						if (is_array ( $customerLanguageModel )) {
							foreach ( $customerLanguageModel as $languages ) {
								$pushMessageDetailModel = new PushMessage_Model_PushMessageDetail ( $allFormValues );
								$pushMessageDetailModel->setPushMessageId ( $push_message_id );
								//$pushMessageDetailModel->setMessageDate ( $date_time );
								$pushMessageDetailModel->setLanguageId ( $languages->getLanguageId () );
								$pushMessageDetailModel = $pushMessageDetailModel->save ();
							}
						}
						//setting the message in variable only in add mode
						//if($allFormValues["description"] != ""){
							//$message = array();
							//$message['message'] = $allFormValues["description"];
						//}
					} else {
						$pushMessageModel->setLastUpdatedBy ( $user_id );
						$pushMessageModel->setLastUpdatedAt ( $date_time );
						$pushMessageModel = $pushMessageModel->save ();
						
						$pushMessageDetailModel = new PushMessage_Model_PushMessageDetail ( $allFormValues );
						//$pushMessageDetailModel->setMessageDate($date_time);
						$pushMessageDetailModel = $pushMessageDetailModel->save ();
					}
					$customermoduleMapper = new Admin_Model_Mapper_CustomerModule();
					$customermodule = $customermoduleMapper->fetchAll("customer_id=". $customer_id ." AND module_id=".$this->_module_id);
					if(is_array($customermodule)) {
						$customermodule = $customermodule[0];
						$customermodule->setIsPublish("NO");
						$customermodule->save();
					}
					$pushMessageMapper->getDbTable ()->getAdapter ()->commit ();
					$response = array (
							"success" => $pushMessageModel->toArray () 
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
	public function deleteAction() {
		$this->_helper->layout ()->disableLayout ();
		$this->_helper->viewRenderer->setNoRender ();
		$request = $this->getRequest ();
		
		if (($push_message_id = $request->getParam ( "id", "" )) != "") {
			$pushMessageModel = new PushMessage_Model_PushMessage ();
			$pushMessageModel->populate ( $push_message_id );
			if ($pushMessageModel) {
				try {    
					$pushMessageDetailMapper = new PushMessage_Model_Mapper_PushMessageDetail ();
					$pushMessageDetailMapper->getDbTable ()->getAdapter ()->beginTransaction ();
					
					$dataDetails = $pushMessageDetailMapper->fetchAll ( "push_message_id = " . $pushMessageModel->getPushMessageId () );
					foreach ( $dataDetails as $pushMessageDetail ) {
						$pushMessageDetail->delete ();
					}
					
					$deletedRows = $pushMessageModel->delete ();
					$customer_id = Standard_Functions::getCurrentUser ()->customer_id;
					$customermoduleMapper = new Admin_Model_Mapper_CustomerModule();
					$customermodule = $customermoduleMapper->fetchAll("customer_id=". $customer_id ." AND module_id=".$this->_module_id);
					if(is_array($customermodule)) {
						$customermodule = $customermodule[0];
						$customermodule->setIsPublish("NO");
						$customermodule->save();
					}
					
					$pushMessageDetailMapper->getDbTable ()->getAdapter ()->commit ();
					
					$response = array (
							"success" => array (
									"deleted_rows" => $deletedRows 
							) 
					);
				} catch ( Exception $e ) {
					
					$pushMessageDetailMapper->getDbTable ()->getAdapter ()->rollBack ();
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