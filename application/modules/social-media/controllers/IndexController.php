<?php
class SocialMedia_IndexController extends Zend_Controller_Action
{
	var $_module_id;
	
    public function init()
    {
		/* Initialize action controller here */
    	$modulesMapper = new Admin_Model_Mapper_Module();
    	$module = $modulesMapper->fetchAll("name ='social-media'");
    	if(is_array($module)) {
    		$this->_module_id = $module[0]->getModuleId();
    	}
    }

    public function indexAction()
    {
        // action body
    	$this->view->addlink = $this->view->url ( array (
					    			"module" => "social-media",
					    			"controller" => "index",
					    			"action" => "add"
					    	), "default", true );
    	$this->view->reorderlink = $this->view->url ( array (
    			"module" => "social-media",
    			"controller" => "index",
    			"action" => "reorder"
    	), "default", true );
    }
    
    public function addAction()
    {
    	// action body
    	$lang_id = Standard_Functions::getCurrentUser ()->default_language_id;
    	$language = new Admin_Model_Mapper_Language();
    	$lang = $language->find($lang_id);
    	$this->view->language = $lang->getTitle();
    	 
    	$form = new SocialMedia_Form_SocialMedia();
    	foreach ( $form->getElements () as $element ) {
    		if ($element->getDecorator ( 'Label' ))
    			$element->getDecorator ( 'Label' )->setTag ( null );
    	}
    	$action = $this->view->url ( array (
    			"module" => "social-media",
    			"controller" => "index",
    			"action" => "save"
    	), "default", true );
    	$form->setAction($action);
    	$this->view->form = $form;
    	
    	// Get System Icon List
    	$mapper = new SocialMedia_Model_Mapper_SocialMediaIcon();
    	$this->view->icons = $mapper->fetchAll();
    	
    	$mapper = new SocialMedia_Model_Mapper_SocialMediaType();
    	$this->view->types = $mapper->fetchAll();
    	
    	$this->view->icon_path="";
    	$this->view->assign ( array (
    			"partial" => "index/partials/add.phtml"
    	) );
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
    		
    		$mapper = new SocialMedia_Model_Mapper_ModuleSocialMedia();
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
	    		if($model && $model->getModuleSocialMediaId()!="") {
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
    	
    	$mapper = new SocialMedia_Model_Mapper_ModuleSocialMedia();
    	$select = $mapper->getDbTable ()->
					    	select ( false )->
					    	setIntegrityCheck ( false )->
					    	from ( array ("sm" => "module_social_media"),
			    					array (
				    					"sm.module_social_media_id" => "module_social_media_id",
				    					"sm.status" => "status",
				    					"sm.order" => "order"
			    			))->
    						joinLeft ( array ("smd" => "module_social_media_detail"),
    							"smd.module_social_media_id = sm.module_social_media_id AND smd.language_id = ".$active_lang_id,
    							array (
    									"smd.module_social_media_detail_id" => "module_social_media_detail_id",
    									"smd.title" => "title",
    									"smd.social_media_type_id" => "social_media_type_id",
    									"smd.url" => "url",
    						))->
    						joinLeft ( array ("smt" => "social_media_type"),
    									"smt.social_media_type_id = smd.social_media_type_id",
    									array (
    											"smt.title" => "title"
    						))->
    						where("sm.customer_id=".$customer_id)->order("sm.order");
    	$response = $mapper->getDbTable()->fetchAll($select)->toArray();
    	$this->view->data = $response;
    }
    
    public function editAction()
    {
    	$form = new SocialMedia_Form_SocialMedia();
    	
    	$request = $this->getRequest ();
    	if ($request->getParam ( "id", "" ) != "" && $request->getParam ( "lang", "" ) != "") {
    		foreach ( $form->getElements () as $element ) {
    			if ($element->getDecorator ( 'Label' ))
    				$element->getDecorator ( 'Label' )->setTag ( null );
    		}
    		$action = $this->view->url ( array (
    				"module" => "social-media",
    				"controller" => "index",
    				"action" => "save"
    		), "default", true );
    		$form->setAction($action);
    		
    		$media_id = $request->getParam ( "id", "" );
    		$lang_id = $request->getParam ( "lang", "" );
    		$language = new Admin_Model_Mapper_Language();
    		$lang = $language->find($lang_id);
    		$this->view->language = $lang->getTitle();
    		 
    		$default_lang_id = Standard_Functions::getCurrentUser ()->default_language_id;
    		
    		$mapper = new SocialMedia_Model_Mapper_ModuleSocialMedia();
    		$data = $mapper->find ( $media_id )->toArray ();
    		$form->populate ( $data );
    		
    		$dataDetails = array();
    		
    		$details = new SocialMedia_Model_Mapper_ModuleSocialMediaDetail();
    		if($details->countAll("module_social_media_id = ".$media_id." AND language_id = ".$lang_id) > 0) {
    			$dataDetails = $details->getDbTable()->fetchAll("module_social_media_id = ".$media_id." AND language_id = ".$lang_id)->toArray();
    		} else {
    			$dataDetails = $details->getDbTable()->fetchAll("module_social_media_id = ".$media_id." AND language_id = ".$default_lang_id)->toArray();
    			$dataDetails[0]["module_social_media_detail_id"] = "";
    			$dataDetails[0]["language_id"] = $lang_id;
    		}
    		if(isset($dataDetails[0]) && is_array($dataDetails[0])) {
    			$form->populate ( $dataDetails[0] );
    			$this->view->icon_path=$dataDetails[0]["icon_path"];
    			$this->view->icon_src=$this->view->baseUrl("resource/social-media/" . $dataDetails[0]["icon_path"]);
    		}
    		// Get System Icon List
    		$mapper = new SocialMedia_Model_Mapper_SocialMediaIcon();
    		$this->view->icons = $mapper->fetchAll();
    		
    		$mapper = new SocialMedia_Model_Mapper_SocialMediaType();
    		$this->view->types = $mapper->fetchAll();
    	} else {
    		$this->_redirect ( '/' );
    	}
    	
    	$this->view->form = $form;
    	 
    	$this->view->logo_path="";
    	$this->view->assign ( array (
    			"partial" => "index/partials/edit.phtml"
    	) );
    	$this->render ( "add-edit" );
    }
    
    public function saveAction()
    {
    	$form = new SocialMedia_Form_SocialMedia();
    	$request = $this->getRequest ();
    	$response = array ();
    	if ($this->_request->isPost ()) {
    		if($request->getParam ( "upload", "" ) != "") {
    			$adapter = new Zend_File_Transfer_Adapter_Http();
    			$upload_dir = Standard_Functions::getResourcePath(). "social-media/images/C".Standard_Functions::getCurrentUser ()->customer_id;
    			if (! is_dir ( $upload_dir )) {
    				mkdir ( $upload_dir, 755 );
    			}
    			$adapter->setDestination($upload_dir);
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
    			$mapper = new SocialMedia_Model_Mapper_ModuleSocialMedia();
    			$mapper->getDbTable()->getAdapter()->beginTransaction();
    			 
    			try {
    				$arrFormValues = $form->getValues();
    				$customer_id = Standard_Functions::getCurrentUser ()->customer_id;
    				$user_id = Standard_Functions::getCurrentUser ()->user_id;
    				$date_time = Standard_Functions::getCurrentDateTime ();
    				$icon_path = $request->getParam ("icon_path", "");
    				
    				$model = new SocialMedia_Model_ModuleSocialMedia($arrFormValues);
    				
    				if($request->getParam ( "module_social_media_id", "" ) == "") {
    					$maxOrder = $mapper->getNextOrder($customer_id);
    						
    					$model->setOrder($maxOrder+1);
    					$model->setCustomerId ( $customer_id );
    					$model->setCreatedBy ( $user_id );
    					$model->setCreatedAt ( $date_time );
    					$model->setLastUpdatedBy ( $user_id );
    					$model->setLastUpdatedAt ( $date_time );
    					$model = $model->save ();
    					
    					// Save Social Media Details
    					$moduleSocialMediaId = $model->getModuleSocialMediaId();
    					$mapperLanguage = new Admin_Model_Mapper_CustomerLanguage();
    					$modelLanguages = $mapperLanguage->fetchAll("customer_id = ".$customer_id);
    					if(is_array($modelLanguages)) {
    						foreach($modelLanguages as $languages) {
    							$modelDetails = new SocialMedia_Model_ModuleSocialMediaDetail($arrFormValues);
    							$modelDetails->setModuleSocialMediaId($moduleSocialMediaId);
    							$modelDetails->setLanguageId($languages->getLanguageId());
    							if($request->getParam("selLogo",0) != 0) {
    								$iconId = $request->getParam("selLogo",0);
    								$icon = new SocialMedia_Model_SocialMediaIcon();
    								$icon->populate($iconId);
    								
    								$modelDetails->setIconPath("icons/".$icon->getIconPath());
    							} else if($icon_path != "") {
    								$modelDetails->setIconPath("images/C".$customer_id."/".$icon_path);
    							} else {
    								$modelDetails->setIconPath("");
    							}
    					
    							$modelDetails->setCreatedBy ( $user_id );
    							$modelDetails->setCreatedAt ( $date_time );
    							$modelDetails->setLastUpdatedBy ( $user_id );
    							$modelDetails->setLastUpdatedAt ( $date_time );
    							$modelDetails = $modelDetails->save();
    						}
    					}
    				} else {
    					$model->setLastUpdatedBy ( $user_id );
    					$model->setLastUpdatedAt ( $date_time );
    					$model = $model->save ();
    					
    					$modelDetails = new SocialMedia_Model_ModuleSocialMediaDetail($arrFormValues);
    					if($request->getParam("selLogo",0) != 0) {
    						$iconId = $request->getParam("selLogo",0);
    						$icon = new SocialMedia_Model_SocialMediaIcon();
    						$icon->populate($iconId);
    					
    						$modelDetails->setIconPath("icons/".$icon->getIconPath());
    					} else if($icon_path != "") {
    						$modelDetails->setIconPath("images/C".$customer_id."/".$icon_path);
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
    				if($model && $model->getModuleSocialMediaId()!="") {
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
    
    	if (($social_media_id = $request->getParam ( "id", "" )) != "") {
    		$social_media = new SocialMedia_Model_ModuleSocialMedia();
    		$social_media->populate($social_media_id);
    		if($social_media) {
    			$mapper = new SocialMedia_Model_Mapper_ModuleSocialMedia();
    			$mapper->getDbTable()->getAdapter()->beginTransaction();
    			try {
    				$detailsMapper = new SocialMedia_Model_Mapper_ModuleSocialMediaDetail();
    				$details = $detailsMapper->fetchAll("module_social_media_id=".$social_media->getModuleSocialMediaId());
    				if(is_array($details)) {
    					foreach($details as $socialMediaDetail) {
    						$socialMediaDetail->delete();
    					}
    				}
    				
    				$deletedRows = $social_media->delete ();
    				
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
    							"message" => "No social media entry to delete."
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
    	$customer_id = Standard_Functions::getCurrentUser ()->customer_id;
    	
    	$mapper = new SocialMedia_Model_Mapper_ModuleSocialMedia();
    	 
    	$select = $mapper->getDbTable ()->
					    	select ( false )->
					    	setIntegrityCheck ( false )->
					    	from ( array ("sm" => "module_social_media"),
					    			array (
					    					"sm.module_social_media_id" => "module_social_media_id",
					    					"sm.status" => "status",
					    					"sm.order" => "order"))->
    						joinLeft ( array ("smd" => "module_social_media_detail"),
    									"smd.module_social_media_id = sm.module_social_media_id AND smd.language_id = ".$active_lang_id,
		    							array (
		    									"smd.module_social_media_detail_id" => "module_social_media_detail_id",
		    									"smd.title" => "title",
		    									"smd.social_media_type_id" => "social_media_type_id",
		    									"smd.url" => "url",
		    							))->
		    				joinLeft ( array ("smt" => "social_media_type"),
		    							"smt.social_media_type_id = smd.social_media_type_id",
		    							array (
		    								"smt.title" => "title"
		    							))->
		    				where("sm.customer_id=".$customer_id)->order("sm.order");
    	$response = $mapper->getGridData ( array (
    									'column' => array (
    											'id' => array (
    													'actions'
    											),
    											'replace' => array (
    													'sm.status' => array (
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
    		if($row [5] ["smd.module_social_media_detail_id"]=="") {
    			$mapper = new SocialMedia_Model_Mapper_ModuleSocialMediaDetail();
    			$details = $mapper->fetchAll("module_social_media_id=".$row [5] ["sm.module_social_media_id"]." AND language_id=".$default_lang_id);
    			if(is_array($details)) {
    				$details = $details[0];
    				$type = new SocialMedia_Model_SocialMediaType();
    				$type->populate($details->getSocialMediaTypeId());
    				$row [5] ["smd.social_media_type_id"] = $details->getSocialMediaTypeId();
    				$row [5] ["smt.title"] = $row[0] = $type->getTitle();
    				$row [5] ["smd.title"] = $row[1] = $details->getTitle();
    				$row [5] ["smd.url"] = $row[2] = $details->getUrl();
    			}
    		}
    		    		
    		$response ['aaData'] [$rowId] = $row;
    		if($languages) {
    			foreach ($languages as $lang) {
    				$editUrl = $this->view->url ( array (
    												"module" => "social-media",
    												"controller" => "index",
    												"action" => "edit",
    												"id" => $row [5] ["sm.module_social_media_id"],
    												"lang" => $lang["l.language_id"]
    										), "default", true );
    				$edit[] = '<a href="'. $editUrl .'"><img src="images/lang/'.$lang["logo"].'" alt="'.$lang["l.title"].'" /></a>';
    			}
    		}
    		$deleteUrl = $this->view->url ( array (
    										"module" => "social-media",
    										"controller" => "index",
    										"action" => "delete",
    										"id" => $row [5] ["sm.module_social_media_id"]
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