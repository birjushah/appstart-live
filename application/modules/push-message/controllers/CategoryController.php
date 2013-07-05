<?php
class PushMessage_CategoryController extends Zend_Controller_Action
{
	var $_module_id;
	var $_customer_module_id;
	public function init()
	{
		/* Initialize action controller here */
		$modulesMapper = new Admin_Model_Mapper_Module();
		$module = $modulesMapper->fetchAll("name ='push-message'");
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
		/*$image_dir = Standard_Functions::getResourcePath(). "push-message/preset-icons";
		if(is_dir($image_dir)){
			$direc = opendir($image_dir);
			$iconpack = array();
			while($icon = readdir($direc)){
				if(is_file($image_dir."/".$icon) && getimagesize($image_dir."/".$icon)){
					$iconpack[] = $icon;
				}
			}
		}
		$this->_iconpack = $iconpack;*/
	}
	public function indexAction()
	{
		// action body
		$this->view->addlink = $this->view->url ( array (
				"module" => "push-message",
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
				"module" => "push-message",
				"controller" => "category",
				"action" => "reorder"
		), "default", true );
		$customer_id = Standard_Functions::getCurrentUser ()->customer_id;
		$language_id = Standard_Functions::getCurrentUser ()->active_language_id;
		$this->view->categoryTree = self::_getCategoryTree ( $customer_id, $language_id,false,true);
	}
	public function addAction() {
		$lang_id = Standard_Functions::getCurrentUser ()->default_language_id;
		$language = new Admin_Model_Mapper_Language();
		$lang = $language->find($lang_id);
		$this->view->language = $lang->getTitle();
	
		$customer_id = Standard_Functions::getCurrentUser ()->customer_id;
		$language_id = Standard_Functions::getCurrentUser ()->active_language_id;
		$this->view->categoryTree = self::_getCategoryTree ( $customer_id, $language_id);
		// add Action
		$form = new PushMessage_Form_PushMessageCategory();
		$form->getElement("parent_id")->setValue(0);
		$action = $this->view->url ( array (
				"module" => "push-message",
				"controller" => "category",
				"action" => "save"
		), "default", true );
		$form->setMethod ( 'POST' );
		$form->setAction ( $action );
		$this->view->form = $form;
		//$this->view->iconpack = $this->_iconpack;
		$this->view->assign ( array (
				"partial" => "category/partials/add.phtml"
		) );
		$this->render ( "add-edit" );
	}
	public function editAction()
	{
		$form = new PushMessage_Form_PushMessageCategory();
		foreach ( $form->getElements () as $element ) {
			if ($element->getDecorator ( 'Label' ))
				$element->getDecorator ( 'Label' )->setTag ( null );
		}
		$request = $this->getRequest ();
		if ($request->getParam ( "id", "" ) != "" && $request->getParam ( "lang", "" ) != "") {
			$push_message_id = $request->getParam ( "id", "" );
			$lang_id = $request->getParam ( "lang", "" );
	
			$language = new Admin_Model_Mapper_Language();
			$lang = $language->find($lang_id);
			$this->view->language = $lang->getTitle();
			 
			$default_lang_id = Standard_Functions::getCurrentUser ()->default_language_id;
	
			$mapper = new PushMessage_Model_Mapper_PushMessageCategory();
			$data = $mapper->find ( $push_message_id )->toArray ();
			$form->populate ( $data );
			
			$customer_id = Standard_Functions::getCurrentUser ()->customer_id;
			$language_id = Standard_Functions::getCurrentUser ()->active_language_id;
			$this->view->categoryTree = self::_getCategoryTree ( $customer_id, $language_id,$push_message_id);
			$parent_id = $data["parent_id"];
			$this->view->orignalParent = $parent_id;
			$detailMapper = new PushMessage_Model_Mapper_PushMessageCategoryDetail();
			if($parent_id != 0){
				$details = $detailMapper->getDbTable()->fetchAll("language_id = ".$language_id." AND push_message_category_id =" .$parent_id)->toArray();
				$this->view->parentCategory = $details[0]['title'];
			} else {
				$this->view->parentCategory = 'Menu';
			}
			
			$dataDetails = array();
			$details = new PushMessage_Model_Mapper_PushMessageCategoryDetail();			 
			if($details->countAll("push_message_category_id = ".$push_message_id." AND language_id = ".$lang_id) > 0) {
				// Record For Language Found
				$dataDetails = $details->getDbTable()->fetchAll("push_message_category_id = ".$push_message_id." AND language_id = ".$lang_id)->toArray();
			} else {
				// Record For Language Not Found
				$dataDetails = $details->getDbTable()->fetchAll("push_message_category_id = ".$push_message_id." AND language_id = ".$default_lang_id)->toArray();
				$dataDetails[0]["push_message_category_detail_id"] = "";
				$dataDetails[0]["language_id"] = $lang_id;
			}
	
			if(isset($dataDetails[0]) && is_array($dataDetails[0])) {
			    $form->populate ( $dataDetails[0] );
			}
	
			$action = $this->view->url ( array (
					"module" => "push-message",
					"controller" => "category",
					"action" => "save",
					"id" => $request->getParam ( "id", "" )
			), "default", true );
			$form->setAction($action);
		} else {
			$this->_redirect ( '/' );
		}
	
		$this->view->form = $form;
		//$this->view->iconpack = $this->_iconpack;
		$this->view->assign ( array (
				"partial" => "index/partials/edit.phtml"
		) );
		$this->render ( "add-edit" );
	}
	
	private function _getCategoryTree($customer_id = null, $language_id = null, $nochildsforthisid = null, $onlyParents = null) {
	    // Get customer_id, module_cms_id ,title, parent_id
	    $where = "c.customer_id =" . $customer_id;
	    if(isset($nochildsforthisid) && $nochildsforthisid != null){
	        $childs = self::_getChilds($nochildsforthisid);
	        $string = is_array($childs)?implode("','",$childs):false;
	        $where = "c.push_message_category_id NOT IN('".$string."') AND c.parent_id !='".$nochildsforthisid."' AND c.push_message_category_id != '".$nochildsforthisid."' AND c.customer_id = '" . $customer_id . "' AND cd.language_id =".$language_id ;
	    }
	    if($onlyParents){
	        $parent_ids = self::_getParentIds();
	        $where = "c.customer_id ='".$customer_id."' AND cd.language_id ='".$language_id."' AND c.push_message_category_id IN('".$parent_ids."')";
	    }
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
	private static function _getChilds($childs){
	    if(!is_array($childs)){
	        $childs = array($childs);
	    }
	    $blacklisted = array();
	    while(count(self::_getBlacklistedIds($childs)) != 0){
	        $resultset = self::_getBlacklistedIds($childs);
	        foreach ($resultset as $result) {
	            $blacklisted[] = $result;
	        }
	        $childs = $resultset;
	    }
	    return $blacklisted;
	}
	
	private static function _getBlacklistedIds(array $ids = array()){
	    $categoryMapper = new PushMessage_Model_Mapper_PushMessageCategory();
	    $idstack = array();
	    foreach ($ids as $id) {
	        $result = $categoryMapper->getDbTable()->fetchAll("parent_id =".$id)->toArray();
	        if(is_array($result)){
	            foreach ($result as $result) {
	                $idstack[] = $result['push_message_category_id'];
	            }
	        }
	    }
	    return $idstack;
	}
	
	private static function _getParentIds(){
	    $parentIds = array();
	    $mapper = new PushMessage_Model_Mapper_PushMessageCategory();
	    $select = $mapper->getDbTable()->select()->setIntegrityCheck(false)
	    ->from(array('dc'=>'module_push_message_category'),'dc.parent_id')
	    ->distinct('dc.parent_id');
	    $parentIdArrays = $mapper->getDbTable()->fetchAll($select);
	    foreach ($parentIdArrays as $parentIdArray) {
	        foreach ($parentIdArray as $parentId) {
	            $parentIds[] = $parentId;
	        }
	    }
	    $parentString = !empty($parentIds)?implode("','", $parentIds):false;
	    return $parentString;
	}
	
	public function saveAction() {
		$form = new PushMessage_Form_PushMessageCategory();
		$request = $this->getRequest ();
		$response = array ();
		$default_lang_id = Standard_Functions::getCurrentUser ()->default_language_id;
		$allFlag = $this->_request->getParam("all",false);
		if ($this->_request->isPost ()) {
		    if ($form->isValid ( $this->_request->getParams () )) {
				$mapper = new PushMessage_Model_Mapper_PushMessageCategory();
				$mapper->getDbTable()->getAdapter()->beginTransaction();
				 
				try {
				    $arrFormValues = $form->getValues();
					$parent_id = $arrFormValues['parent_id'];
					$customer_id = Standard_Functions::getCurrentUser ()->customer_id;
					$user_id = Standard_Functions::getCurrentUser ()->user_id;
					$date_time = Standard_Functions::getCurrentDateTime ();
					
					$model = new PushMessage_Model_PushMessageCategory($arrFormValues);
					if ($request->getParam ( "push_message_category_id", "" ) == "" || $request->getParam("parent") == "changed") {
					    $maxOrder = $mapper->getNextOrder ( $parent_id,$customer_id );
					    $model->setOrder ( $maxOrder + 1 );
					}
					if($request->getParam ( "push_message_category_id", "" ) == "") {
						// Add Category
						$model->setCustomerId ( $customer_id );
						$model->setCreatedBy ( $user_id );
						$model->setCreatedAt ( $date_time );
						$model->setLastUpdatedBy ( $user_id );
						$model->setLastUpdatedAt ( $date_time );
						$model = $model->save ();
						// Save Details
						$push_message_category_id = $model->getPushMessageCategoryId();
						$mapperLanguage = new Admin_Model_Mapper_CustomerLanguage();
						$modelLanguages = $mapperLanguage->fetchAll("customer_id = ".$customer_id);
						if(is_array($modelLanguages)) {
							foreach($modelLanguages as $languages) {
								$modelDetails = new PushMessage_Model_PushMessageCategoryDetail($arrFormValues);
								$modelDetails->setPushMessageCategoryId($push_message_category_id);
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
						$categoryDetailMapper = new PushMessage_Model_Mapper_PushMessageCategoryDetail();
						$categoryDetails = $categoryDetailMapper->getDbTable()->fetchAll("push_message_category_id =".$arrFormValues['push_message_category_id'])->toArray();
						if($arrFormValues['push_message_category_detail_id'] != null){
						    $currentDetails = $categoryDetailMapper->getDbTable()->fetchAll("push_message_category_detail_id =".$arrFormValues['push_message_category_detail_id'])->toArray();
						}else{
						    $currentDetails = $categoryDetailMapper->getDbTable()->fetchAll("push_message_id ='".$arrFormValues['push_message_category_id']."' AND language_id =".$default_lang_id)->toArray();
						}
						
						unset($arrFormValues['push_message_category_detail_id'],$arrFormValues['language_id']);
						if(count($categoryDetails) == count($customerLanguageModel)){
						    foreach ($categoryDetails as $categoryDetail) {
						        $categoryDetail = array_intersect_key($arrFormValues + $categoryDetail, $categoryDetail);
						        $categoryDetailModel = new PushMessage_Model_PushMessageCategoryDetail($categoryDetail);
						        $categoryDetailModel = $categoryDetailModel->save();
						    }    
						}else{
						    $categoryDetailMapper = new PushMessage_Model_Mapper_PushMessageCategoryDetail();
						    $categoryDetails = $categoryDetailMapper->fetchAll("push_message_category_id =".$arrFormValues['push_message_category_id']);
						    foreach ($categoryDetails as $categoryDetail){
						        $categoryDetail->delete();
						    }
						    if (is_array ( $customerLanguageModel )) {
						        foreach ( $customerLanguageModel as $languages ) {
						            $categoryDetailModel = new PushMessage_Model_PushMessageCategoryDetail($arrFormValues);
						            $categoryDetailModel->setLanguageId ( $languages->getLanguageId () );
						            $categoryDetailModel->setCreatedBy ( $user_id );
						            $categoryDetailModel->setCreatedAt ( $date_time );
						            $categoryDetailModel->setLastUpdatedBy ( $user_id );
						            $categoryDetailModel->setLastUpdatedAt ( $date_time );
						            $categoryDetailModel = $categoryDetailModel->save ();
						        }
						    }
						}
					} else {
						// Edit Category
						$model->setLastUpdatedBy ( $user_id );
						$model->setLastUpdatedAt ( $date_time );
						$model = $model->save ();
						
						$modelDetails = new PushMessage_Model_PushMessageCategoryDetail($arrFormValues);
						if(!$modelDetails || $modelDetails->getPushMessageCategoryDetailId()=="") {
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
					if($model && $model->getPushMessageCategoryId()!="") {
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
	
		if (($push_message_category_id = $request->getParam ( "id", "" )) != "") {
			$push_message_category = new PushMessage_Model_PushMessageCategory();
			$push_message_category->populate($push_message_category_id);
			if($push_message_category) {
				$mapper = new PushMessage_Model_Mapper_PushMessageCategory();
				$childexist = $mapper->fetchAll("parent_id =".$push_message_category_id);
				if($childexist){
				    $response = array(
				            'errors' => array(
				                    'message' => "Please delete its child first"
				                    )
				            );
				}else{
				    $mapper->getDbTable()->getAdapter()->beginTransaction();
				    try {
				        $detailsMapper = new PushMessage_Model_Mapper_PushMessageCategoryDetail();
				        $details = $detailsMapper->fetchAll("push_message_category_id=".$push_message_category->getPushMessageCategoryId());
				        if(is_array($details)) {
				            foreach($details as $PushMessageDetail) {
				                $PushMessageDetail->delete();
				            }
				        }
				    
				        $deletedRows = $push_message_category->delete ();
				    
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
				    
				    }catch (Zend_Db_Exception $ex) {
				        $mapper->getDbTable()->getAdapter()->rollBack();
				        if($ex->getCode() == 23000){
				            $response = array (
				                    "errors" => array (
				                            "message" => "Delete underlying categories and messages first"
				                    )
				            );
				        }else{
				            $response = array (
				                    "errors" => array (
				                            "message" => $ex->getMessage ()
				                    )
				            );
				        }
				    } catch (Exception $ex) {
				        $mapper->getDbTable()->getAdapter()->rollBack();
				        $response = array (
				                "errors" => array (
				                        "message" => $ex->getMessage ()
				                )
				        );
				    }    
				}
				
			} else {
				$response = array (
						"errors" => array (
								"message" => "No push_message to delete."
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
		
		$mapper = new PushMessage_Model_Mapper_PushMessageCategory();
		
		$select = $mapper->getDbTable ()->
							select ( false )->
							setIntegrityCheck ( false )->
							from ( array ("dc" => "module_push_message_category"),
									array (
										"dc.push_message_category_id" => "push_message_category_id",
										"dc.status" => "status",
										"dc.order" => "order"))->
							joinLeft ( array ("dcd" => "module_push_message_category_detail"),
										"dcd.push_message_category_id = dc.push_message_category_id AND dcd.language_id = ".$active_lang_id,
										array (
												"dcd.push_message_category_detail_id" => "push_message_category_detail_id",
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
			if($row [3] ["dcd.push_message_category_detail_id"]=="") {
				$mapper = new PushMessage_Model_Mapper_PushMessageDetail();
				$details = $mapper->fetchAll("push_message_category_id=".$row [3] ["dc.push_message_category_id"]." AND language_id=".$default_lang_id);
				if(is_array($details)) {
					$details = $details[0];
					$row [3] ["dcd.title"] = $row[0] = $details->getTitle();
				}
			}
		
			$response ['aaData'] [$rowId] = $row;
			if($languages) {
				foreach ($languages as $lang) {
					$editUrl = $this->view->url ( array (
							"module" => "push-message",
							"controller" => "category",
							"action" => "edit",
							"id" => $row [3] ["dc.push_message_category_id"],
							"lang" => $lang["l.language_id"]
					), "default", true );
					$edit[] = '<a href="'. $editUrl .'"><img src="'.$this->view->baseUrl('images/lang/'.$lang["logo"]).'" alt="'.$lang["l.title"].'" /></a>';
				}
			}
			$deleteUrl = $this->view->url ( array (
					"module" => "push-message",
					"controller" => "category",
					"action" => "delete",
					"id" => $row [3] ["dc.push_message_category_id"]
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
	
			$mapper = new PushMessage_Model_Mapper_PushMessageCategory();
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
		$this->view->categoryTree = self::_getCategoryTree ( $customer_id, $language_id);
		
		$mapper = new PushMessage_Model_Mapper_PushMessageCategory();
		$detailMapper = new PushMessage_Model_Mapper_PushMessageCategoryDetail();
		$request = $this->getRequest();
		$parent_id = $request->getParam ( "parent_id", 0 );
		if($parent_id != 0){
			$details = $detailMapper->getDbTable()->fetchAll("language_id = ".$language_id." AND push_message_category_id =" .$parent_id)->toArray();
			$this->view->parentTitle = $details[0]['title'];
		} else {
			$this->view->parentTitle = 'Menu';
		}
		
		$select = $mapper->getDbTable ()->
							select ( false )->
							setIntegrityCheck ( false )->
							from ( array ("dc" => "push_message_category"),
								array (
										"dc.push_message_category_id" => "push_message_category_id",
										"dc.status" => "status",
										"dc.order" => "order"))->
							joinLeft ( array ("dcd" => "push_message_category_detail"),
								"dcd.push_message_category_id = dc.push_message_category_id AND dcd.language_id = ".$active_lang_id,
								array (
										"dcd.push_message_category_detail_id" => "push_message_category_detail_id",
										"dcd.title" => "title"
							))->
							where("dc.parent_id = '".$parent_id."' AND dc.customer_id=".$customer_id)->order("dc.order");
		 
		$response = $mapper->getDbTable()->fetchAll($select)->toArray();
		$this->view->data = $response;
	}
}