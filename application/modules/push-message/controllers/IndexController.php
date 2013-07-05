<?php
class PushMessage_IndexController extends Zend_Controller_Action {
	var $_module_id;
	public function init() {
		/* Initialize Action Controller Here.. */
		define("GOOGLE_API_KEY", "AIzaSyD0gczBUI3hOQQ4PAyToRQ2VMcGhRim_3Q");
		$modulesMapper = new Admin_Model_Mapper_Module();
		$module = $modulesMapper->fetchAll("name ='push-message'");
		if(is_array($module)) {
			PushMessage_Form_PushMessage::$_module_id = $this->_module_id = $module[0]->getModuleId();
		}
		$image_dir = Standard_Functions::getResourcePath(). "push-message/preset-icons";
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
		$this->view->addcategory = $this->view->url(array (
    			"module" => "push-message",
    			"controller" => "category",
    			"action" => "index"
    	), "default", true);
		//getting total categories
		$mapper = new PushMessage_Model_Mapper_PushMessageCategory();
		$customer_id = Standard_Functions::getCurrentUser()->customer_id;
		$DBExpr = new Zend_Db_Expr("COUNT(push_message_category_id)");
		$select = $mapper->getDbTable()->select(false)
		->setIntegrityCheck(false)
		->from('module_push_message_category',array('count'=>$DBExpr ))
		->where("customer_id =".$customer_id);
		$stack = $mapper->getDbTable()->fetchRow($select)->toArray();
		$this->view->totalcategories = $stack['count'];
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
					$customermodule->setSyncDateTime ( Standard_Functions::getCurrentDateTime () );
					$customermodule->setIsPublish("YES");
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
		) )->joinLeft ( array ("pmcd" => "module_push_message_category_detail"),
                "pmcd.push_message_category_id = pm.push_message_category_id AND pmcd.language_id = ".$active_lang_id, array (
                        "pmcd.title" => "title", 
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
			if($row[6]['pmd.push_message_detail_id'] == ""){
				$mapper = new PushMessage_Model_Mapper_PushMessageDetail();
				$details = $mapper->fetchAll("push_message_id=".$row[5]["pm.push_message_id"]." AND language_id=".$default_lang_id);
				if(is_array($details)){
					$details = $details[0];
					$row[6]["pmd.title"] = $row[0] = $details->getTitle();
					$row[6]["pmd.description"] = $row[2] = $details->getDescription();
				}
			}
			if($row[6]["pmd.message_date"] != null){
			    $message_date = Standard_Functions::getLocalDateTime($row[6]["pmd.message_date"]);
    	        $message_date_format = DateTime::createFromFormat ( "Y-m-d H:i:s", $message_date );
    	        if($message_date_format){
    	            $message_date_format = $message_date_format->format ( "d/m/Y H:i" );
    	        }
				$row[6]["pmd.message_date"] = $row[3] = $message_date_format;
			}
			$response['aaData'][$rowId] = $row;
			if ($languages) {
				foreach ( $languages as $lang ) {
					$editUrl = $this->view->url ( array (
							"module" => "push-message",
							"controller" => "index",
							"action" => "edit",
							"id" => $row [6] ["pm.push_message_id"],
							"lang" => $lang ["l.language_id"] 
					), "default", true );
					$edit [] = '<a href="' . $editUrl . '" ><img src="images/lang/' . $lang ["logo"] . '" alt="' . $lang ["l.title"] . '" /></a>';
				}
			}
			$deleteUrl = $this->view->url ( array (
					"module" => "push-message",
					"controller" => "index",
					"action" => "delete",
					"id" => $row [6] ["pm.push_message_id"] 
			), "default", true );
			$defaultEdit = '<div id="editLanguage">&nbsp;<div class="flag-list">'.implode("",$edit).'</div></div>';
			$delete = '<a href="' . $deleteUrl . '" class="button-grid greay grid_delete" >'.$this->view->translate('Delete').'</a>';
			$sap = '';
			$response ['aaData'] [$rowId] [6] = $defaultEdit . $sap . $delete;
		}
		$jsonGrid = Zend_Json::encode ( $response );
		$this->_response->appendBody ( $jsonGrid );
	}
	public function addAction() {
		// add Action
	    $request = $this->getRequest ();
	    $language_id = Standard_Functions::getCurrentUser ()->default_language_id;
	    $form = new PushMessage_Form_PushMessage ();
	    $customer_id = Standard_Functions::getCurrentUser ()->customer_id;
	    if($request->getParam('id',"") != null){
	        $events_id = $request->getParam('id');
	        $dataDetails = array();
	        $details = new Events_Model_Mapper_ModuleEventsDetail();
	        
	        if($details->countAll("module_events_id = ".$events_id." AND language_id = ".$language_id) > 0) {
	            $dataDetails = $details->getDbTable()->fetchAll("module_events_id = ".$events_id." AND language_id = ".$language_id)->toArray();
	        }
	        if($dataDetails[0]['notes'] != null){
	            $dataDetails[0]['notes'] = strip_tags($dataDetails[0]['notes']);
	        }
	        if($dataDetails[0]['description'] != null){
	            $dataDetails[0]['description'] = strip_tags($dataDetails[0]['description']);
	        }
	        $form->populate ( $dataDetails[0] );
	        $this->view->form = $form;
	    }else{
	        $this->view->form = $form;
	    }
	    $action = $this->view->url ( array (
	            "module" => "push-message",
	            "controller" => "index",
	            "action" => "save"
	    ), "default", true );
	    $form->setAction ( $action );
		$this->view->assign ( array (
				"partial" => "index/partials/add.phtml" 
		) );
		$this->view->categoryTree = $this->_getCategoryTree ( $customer_id, $language_id);
		$this->view->iconpack = $this->_iconpack;
		$this->render ( "add-edit" );
	}
	public function editAction() {
		// edit action
		$form = new PushMessage_Form_PushMessage ();
		$request = $this->getRequest ();
		$customer_id = Standard_Functions::getCurrentUser ()->customer_id;
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
			    if($dataDetails[0]['icon'] != null){
			        if(count(explode('/',$dataDetails[0]['icon'])) > 1){
			            $this->view->icon_src = $dataDetails[0]['icon'];
			        }else{
			            $this->view->icon_src = "preset-icons/".$dataDetails[0]['icon'];
			        }
			    }
				$dataDetails[0]['message_date'] = Standard_Functions::getLocalDateTime ($dataDetails[0]['message_date']);
				if($dataDetails[0]["message_date"] != ""){
				    $message_date = DateTime::createFromFormat ( "Y-m-d H:i:s", $dataDetails[0]["message_date"] );
				    if($message_date){
				        $dataDetails[0]["message_date"] = $message_date->format ( "d/m/Y H:i" );
				    }
				}
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
		$this->view->categoryTree = $this->_getCategoryTree ( $customer_id, $language_id);
		$parent_id = $data["push_message_category_id"];
		$this->view->orignalParent = $parent_id;
		$detailMapper = new PushMessage_Model_Mapper_PushMessageCategoryDetail();
		if($parent_id != 0){
		    $details = $detailMapper->getDbTable()->fetchAll("language_id = ".$language_id." AND push_message_category_id =" .$parent_id)->toArray();
		    $this->view->parentCategory = $details[0]['title'];
		} else {
		    $this->view->parentCategory = 'Menu';
		}
		$this->view->iconpack = $this->_iconpack;
		$this->render ( "add-edit" );
	}
	public function saveAction() {
		$form = new PushMessage_Form_PushMessage ();
		$request = $this->getRequest ();
		$response = array ();
		$default_lang_id = Standard_Functions::getCurrentUser ()->default_language_id;
		if ($this->_request->isPost ()) {
		    if($request->getParam ( "iconupload", "" ) != "") {
		        $adapter = new Zend_File_Transfer_Adapter_Http();
		        $adapter->setDestination(Standard_Functions::getResourcePath(). "push-message/uploaded-icons");
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
	                        $allFormValues["message_date"] = Standard_Functions::getServerDateTime ($allFormValues['message_date']);
	                }else{
	                    unset($allFormValues["message_date"]);
	                }
	                if($request->getParam("selLogo","0")){
	                    $selIcon = $request->getParam("selLogo","0");
	                }
	                $icon_path = $request->getParam("icon_path","");
	                if(isset($selIcon) && $selIcon != 0){
	                    $allFormValues["icon"] = $selIcon;
	                }elseif ($icon_path != ""){
	                    $allFormValues["icon"] = "uploaded-icons/".$icon_path;
	                }
	                $allFlag = $this->_request->getParam("all",false);
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
								$pushMessageDetailModel->setSent(0);
								$pushMessageDetailModel->setLanguageId ( $languages->getLanguageId () );
								$pushMessageDetailModel = $pushMessageDetailModel->save ();
							}
						}
					}elseif($allFlag){
					    $pushMessageModel->setLastUpdatedBy ( $user_id );
						$pushMessageModel->setLastUpdatedAt ( $date_time );
						$pushMessageModel = $pushMessageModel->save ();
						$customerLanguageMapper = new Admin_Model_Mapper_CustomerLanguage ();
						$customerLanguageModel = $customerLanguageMapper->fetchAll ( "customer_id = " . $customer_id );
						$pushDetailMapper = new PushMessage_Model_Mapper_PushMessageDetail();
						$pushDetails = $pushDetailMapper->getDbTable()->fetchAll("push_message_id =".$allFormValues['push_message_id'])->toArray();
						if($allFormValues['push_message_detail_id'] != null){
						    $currentDetails = $pushDetailMapper->getDbTable()->fetchAll("push_message_detail_id =".$allFormValues['push_message_detail_id'])->toArray();
						}else{
						    $currentDetails = $pushDetailMapper->getDbTable()->fetchAll("push_message_id ='".$allFormValues['push_message_id']."' AND language_id =".$default_lang_id)->toArray();
						}
						if(is_array($currentDetails)){
						    if(!isset($allFormValues['icon'])){
						        $allFormValues['icon'] = $currentDetails[0]['icon'];
						    }
						}
						unset($allFormValues['push_message_detail_id'],$allFormValues['language_id']);
						if(count($pushDetails) == count($customerLanguageModel)){
						    foreach ($pushDetails as $pushDetail) {
						        $pushDetail = array_intersect_key($allFormValues + $pushDetail, $pushDetail);
						        $pushDetailModel = new PushMessage_Model_PushMessageDetail($pushDetail);
						        $pushDetailModel->setSent(0);
						        $pushDetailModel = $pushDetailModel->save();
						    }    
						}else{
						    $pushDetailMapper = new PushMessage_Model_Mapper_PushMessageDetail();
						    $pushDetails = $pushDetailMapper->fetchAll("push_message_id =".$allFormValues['push_message_id']);
						    foreach ($pushDetails as $pushDetail){
						        $pushDetail->delete();
						    }
						    if (is_array ( $customerLanguageModel )) {
						        $is_uploaded_image = false;
						        foreach ( $customerLanguageModel as $languages ) {
						            $pushDetailModel = new PushMessage_Model_PushMessageDetail($allFormValues);
						            $pushDetailModel->setLanguageId ( $languages->getLanguageId () );
						            $pushDetailModel->setSent(0);
						            $pushDetailModel = $pushDetailModel->save ();
						        }
						    }
						}
					} else {
						$pushMessageModel->setLastUpdatedBy ( $user_id );
						$pushMessageModel->setLastUpdatedAt ( $date_time );
						$pushMessageModel = $pushMessageModel->save ();
						
						$pushMessageDetailModel = new PushMessage_Model_PushMessageDetail ( $allFormValues );
						$pushMessageDetailModel->setSent(0);
						$pushMessageDetailModel = $pushMessageDetailModel->save ();
					}
					$customermoduleMapper = new Admin_Model_Mapper_CustomerModule();
					$customermodule = $customermoduleMapper->fetchAll("customer_id=". $customer_id ." AND module_id=".$this->_module_id);
					if(is_array($customermodule)) {
						$customermodule = $customermodule[0];
						$customermodule->setSyncDateTime ( Standard_Functions::getCurrentDateTime () );
						$customermodule->setIsPublish("YES");
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
						$customermodule->setSyncDateTime ( Standard_Functions::getCurrentDateTime () );
						$customermodule->setIsPublish("YES");
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
	private function _getCategoryTree($customer_id = null, $language_id = null) {
	    // Get customer_id, module_cms_id ,title, parent_id
	    $where = "c.customer_id =" . $customer_id;
	    $data = array();
	    $this->_getChildrens($where,0,$data,$language_id);
	    return Zend_Json::encode ( $data );
	}
	private function _getChildrens($where, $parent,&$data,$language_id){
	    $categoryMapper = new PushMessage_Model_Mapper_PushMessageCategory();
	    $select = $categoryMapper->getDbTable ()->select ()
							->setIntegrityCheck ( false )
							->from ( array (
									'c' => 'module_push_message_category'), 
									array (
										'id' => 'c.push_message_category_id',
										'parentId' => 'c.parent_id') )
							->joinLeft ( array (
									'cd' => 'module_push_message_category_detail'), 
									"cd.push_message_category_id  = c.push_message_category_id AND cd.language_id = " . $language_id, 
									array ('text' => 'cd.title') );
        $select = $select->where ( "c.parent_id=".$parent." AND ".$where );
        $item = $categoryMapper->getDbTable ()->fetchAll ( $select )->toArray ();
        if(count($item) > 0) {
            foreach ($item as $child) {
                $data[] = $child;
                $this->_getChildrens($where,$child['id'],$data,$language_id);
            }
        } else {
            return false;
        }
    }
}