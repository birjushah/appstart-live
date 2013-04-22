<?php
class Default_ConfigurationController extends Zend_Controller_Action {
	public function init() {
		/* Initialize action controller here */
	}
	public function indexAction() {
		// action body
		$form = new Default_Form_GeneralConfiguration ();
		$moduleForm = new Default_Form_CustomerModule ();
		$customer_id = Standard_Functions::getCurrentUser ()->customer_id;
		$configMapper = new Admin_Model_Mapper_CustomerConfiguration ();
		$config = $configMapper->fetchAll ( "customer_id=" . $customer_id );
		if ($config) {
			$config = $config [0];
			$form->populate ( $config->toArray () );
		} else {
			$form->populate ( array (
					"customer_id" => $customer_id 
			) );
		}
		
		// sending data for reorder tab to view
		$active_lang_id = Standard_Functions::getCurrentUser ()->active_language_id;
		$mapper = new Admin_Model_Mapper_CustomerModule ();
		$select = $mapper->getDbTable ()->select ( false )->setIntegrityCheck ( false )->from ( array (
				"cm" => "customer_module" 
		), array (
				"cm.customer_module_id" => "customer_module_id",
				"cm.status" => "status",
				"cm.order_number" => "order_number",
				"cm.visibility" => "visibility",
				"cm.is_publish" => "is_publish" 
		) )->joinLeft ( array (
				"cmd" => "customer_module_detail" 
		), "cmd.customer_module_id = cm.customer_module_id AND cmd.language_id=" . $active_lang_id, array (
				"cmd.customer_module_detail_id" => "customer_module_detail_id",
				"cmd.screen_name" => "screen_name" 
		) )->joinLeft ( array (
				"m" => "module" 
		), "m.module_id=cm.module_id", array (
				"m.name" => "name" 
		) )->where ( "m.status=1 AND cm.status=1 AND cm.customer_id=" . Standard_Functions::getCurrentUser ()->customer_id )->order ( "cm.order_number" );
		$response = $mapper->getDbTable ()->fetchAll ( $select )->toArray ();
		$this->view->data = $response;
		$this->view->generalConfigurationForm = $form;
		$this->view->moduleForm = $moduleForm;
		$this->view->publicUrl = $this->view->baseUrl();
	}
	public function saveGeneralConfigurationAction() {
		$form = new Default_Form_GeneralConfiguration ();
		
		$response = array ();
		if ($this->_request->isPost ()) {
			if ($form->isValid ( $this->_request->getParams () )) {
				$request = $this->getRequest ();
				$config = new Admin_Model_CustomerConfiguration ();
				$config->setOptions ( $request->getParams () );
				$config->setLastUpdatedBy ( Standard_Functions::getCurrentUser ()->user_id );
				$config->setLastUpdatedAt ( Standard_Functions::getCurrentDateTime () );
				
				if ($request->getParam ( "customer_configuration_id", "" ) == "") {
					$config->setCreatedBy ( Standard_Functions::getCurrentUser ()->user_id );
					$config->setCreatedAt ( Standard_Functions::getCurrentDateTime () );
				}
				$config->save ();
				$response = array (
						'success' => array (
								'message' => $config->toArray () 
						) 
				);
			} else {
				$errors = $form->getMessages ();
				
				foreach ( $errors as $name => $error ) {
					$errors [$name] = array_pop ( $error );
				}
				$response = array (
						"errors" => $errors 
				);
			}
			$this->_helper->json ( $response );
		}
	}
	public function gridAction() {
		$this->_helper->layout ()->disableLayout ();
		$this->_helper->viewRenderer->setNoRender ();
		
		$active_lang_id = Standard_Functions::getCurrentUser ()->active_language_id;
		$default_lang_id = Standard_Functions::getCurrentUser ()->default_language_id;
		$mapper = new Admin_Model_Mapper_CustomerModule ();
		
		$select = $mapper->getDbTable ()->select ( false )->setIntegrityCheck ( false )->from ( array (
				"cm" => "customer_module" 
		), array (
				"cm_customer_module_id" => "cm.customer_module_id",
				"cm.module_id",
				"cm_visibility" => "cm.visibility",
				"cm.customer_id",
				"cm_order_number" => "cm.order_number",
				"cm_status" => "cm.status",
				"cm_is_publish" => "cm.is_publish" 
		) )->joinLeft ( array (
				"m" => "module" 
		), "m.module_id=cm.module_id", array (
				"m_description" => "m.description" 
		) )->joinLeft ( array (
				"d" => "customer_module_detail" 
		), "d.customer_module_id=cm.customer_module_id AND d.language_id = " . $active_lang_id, array (
				"d_customer_module_detail_id" => "d.customer_module_detail_id",
				"d_language_id" => "d.language_id",
				"d_screen_name" => "d.screen_name",
				"d_background_image" => "d.background_image" 
		) )->where ( "m.status=1 AND cm.status=1 AND cm.customer_id=" . Standard_Functions::getCurrentUser ()->customer_id )->order ( "cm.order_number" );
		
		$response = $mapper->getGridData ( array (
				'column' => array (
						'id' => array (
								'actions' 
						),
						'replace' => array (
								'cm_visibility' => array (
										'1' => 'Yes',
										'0' => 'No' 
								) 
						) 
				) 
		), null, $select );
		
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
			if ($row [5] ["d_customer_module_detail_id"] == "") {
				$mapper = new Admin_Model_Mapper_CustomerModuleDetail ();
				$details = $mapper->fetchAll ( "customer_module_id=" . $row [5] ["cm_customer_module_id"] . " AND language_id=" . $default_lang_id );
				if (is_array ( $details )) {
					$details = $details [0];
					$row [5] ["d_customer_module_detail_id"] = $details->getCustomerModuleDetailId ();
					$row [5] ["d_language_id"] = $details->getLanguageId ();
					$row [5] ["d_screen_name"] = $row [1] = $details->getScreenName ();
					$row [5] ["d_background_image"] = $details->getBackgroundImage ();
				}
			}
			$edit = array ();
			if ($languages) {
				foreach ( $languages as $lang ) {
					$editUrl = $this->view->url ( array (
							"module" => "default",
							"controller" => "configuration",
							"action" => "edit",
							"id" => $row [5] ["cm_customer_module_id"],
							"lang" => $lang ["l.language_id"] 
					), "default", true );
					$edit [] = '<a href="' . $editUrl . '"  class="edit"><img src="images/lang/' . $lang ["logo"] . '" alt="' . $lang ["l.title"] . '" /></a>';
				}
			}
			
			$publishUrl = $this->view->url ( array (
					"module" => "default",
					"controller" => "configuration",
					"action" => "publish",
					"id" => $row [5] ["cm_customer_module_id"] 
			), "default", true );
    		$edit = '<div id="editLanguage">&nbsp;<div class="flag-list">'.implode("",$edit).'</div></div>';
			$publish = '<a href="' . $publishUrl . '" class="button-grid greay grid_publish" >'.$this->view->translate('Publish').'</a>';
			$sap = '';
			
			$response ['aaData'] [$rowId] [5] = $edit . $sap . $publish;
		}
		
		$jsonGrid = Zend_Json::encode ( $response );
		$this->_response->appendBody ( $jsonGrid );
	}
	public function publishAction() {
		$this->_helper->layout ()->disableLayout ();
		$this->_helper->viewRenderer->setNoRender ();
		$moduleId = $this->_request->getParam ( "id", "" );
		$response = array ();
		$mapper = new Admin_Model_Mapper_CustomerModule ();
		$mapper->getDbTable ()->getAdapter ()->beginTransaction ();
		try {
			$model = $mapper->find ( $moduleId );
			if ($model) {
				$model->setSyncDateTime ( Standard_Functions::getCurrentDateTime () );
				$model->setIsPublish ( "YES" );
				$model->save ();
				$response = array (
						"success" => array (
								"published_rows" => 1 
						) 
				);
				$mapper->getDbTable ()->getAdapter ()->commit ();
			} else {
				$response = array (
						"errors" => "Nothing to publish" 
				);
			}
		} catch ( Exception $ex ) {
			$mapper->getDbTable ()->getAdapter ()->rollBack ();
			$response = array (
					"errors" => $ex->getMessage () 
			);
		}
		$jsonGrid = Zend_Json::encode ( $response );
		$this->_response->appendBody ( $jsonGrid );
	}
	public function editAction() {
		$this->_helper->layout ()->disableLayout ();
		$this->_helper->viewRenderer->setNoRender ();
		
		$lang_id = $this->_request->getParam ( "lang", "" );
		$customer_module_id = $this->_request->getParam ( "id", "" );
		$default_lang_id = Standard_Functions::getCurrentUser ()->default_language_id;
		$mapper = new Admin_Model_Mapper_CustomerModule ();
		$customer_id = Standard_Functions::getCurrentUser ()->customer_id;		
		$icons = $this->_getIcons($customer_module_id);
		try {
			$select = $mapper->getDbTable ()->select ( false )->setIntegrityCheck ( false )->from ( array (
					"cm" => "customer_module" 
			), array (
					"cm_customer_module_id" => "cm.customer_module_id",
					"cm_visibility" => "cm.visibility",
					"cm.customer_id",
					"cm_order_number" => "cm.order_number",
					"cm_icon" => "cm.icon",
					"cm_status" => "cm.status",
					"cm_is_publish" => "cm.is_publish" 
			) )->joinLeft ( array (
					"d" => "customer_module_detail" 
			), "d.customer_module_id=cm.customer_module_id AND d.language_id = " . $lang_id, array (
					"d_customer_module_detail_id" => "d.customer_module_detail_id",
					"d_language_id" => "d.language_id",
					"d_screen_name" => "d.screen_name",
					"d_background_image" => "d.background_image",
					"d_background_color" => "d.background_color",
					"d_background_type" => "d.background_type" 
			) )->where ( "cm.customer_module_id=" . $customer_module_id );
			$response = $mapper->getDbTable ()->fetchAll ( $select )->toArray ();
			if (! $response) {
				$select = $mapper->getDbTable ()->select ( false )->setIntegrityCheck ( false )->from ( array (
						"cm" => "customer_module" 
				), array (
						"cm_customer_module_id" => "cm.customer_module_id",
						"cm_visibility" => "cm.visibility",
						"cm.customer_id",
						"cm_order_number" => "cm.order_number",
						"cm_icon" => "cm.icon",
						"cm_status" => "cm.status",
						"cm_is_publish" => "cm.is_publish" 
				) )->joinLeft ( array (
						"d" => "customer_module_detail" 
				), "d.customer_module_id=cm.customer_module_id AND d.language_id = " . $default_lang_id, array (
						"d_customer_module_detail_id" => "d.customer_module_detail_id",
						"d_language_id" => "d.language_id",
						"d_screen_name" => "d.screen_name",
						"d_background_image" => "d.background_image" 
				) )->where ( "cm.customer_module_id=" . $customer_module_id );
				$response = $mapper->getDbTable ()->fetchAll ( $select )->toArray ();
			}
			if ($response) {
				$response = $response [0];
				$response ["d_language_id"] = $lang_id;
				$dropdownData = $this->_getDropdownData ( $customer_id, $lang_id,$response['d_background_image'] );

				$response = array (
						"success" => $response,
						"dropdown" => $dropdownData,
						"icons" => $icons 
				);
			} else {
				$response = array (
						"errors" => "Unable to edit record" 
				);
			}
		} catch ( Exception $ex ) {
			$response = array (
					"errors" => $ex->getMessage () 
			);
		}
		$this->view->publicUrl = $this->view->baseUrl();
		$jsonGrid = Zend_Json::encode ( $response );
		$this->_response->appendBody ( $jsonGrid );
	}
	public function uploadAction() {
		$this->_helper->layout ()->disableLayout ();
		$this->_helper->viewRenderer->setNoRender ();
		
		$form = new Default_Form_CustomerModule ();
		$request = $this->getRequest ();
		$response = array ();
		if ($request->getParam ( "upload", "" ) != "") {
			$element = $request->getParam ( "upload" );
			$adapter = new Zend_File_Transfer_Adapter_Http ();
			$adapter->setDestination ( Standard_Functions::getResourcePath () . "default/images/" . str_replace ( "_image", "", $element ) );
			$adapter->receive ();
			
			if ($adapter->getFileName ( $element ) != "") {
				$response = array (
						"success" => array_pop ( explode ( '/', $adapter->getFileName ( $element ) ) ) 
				);
			} else {
				$response = array (
						"errors" => "Error Occured" 
				);
			}
		}
		echo Zend_Json::encode ( $response );
		exit ();
	}
	public function saveModuleAction() {
		$this->_helper->layout ()->disableLayout ();
		$this->_helper->viewRenderer->setNoRender ();
		
		$form = new Default_Form_CustomerModule ();
		$request = $this->getRequest ();
		$response = array ();
		if ($this->_request->isPost ()) {
			$form->removeElement ( "icon" );
			$form->removeElement ( "background_image" );
			
			if ($form->isValid ( $this->_request->getParams () )) {
				$mapper = new Admin_Model_Mapper_CustomerModule ();
				$mapper->getDbTable ()->getAdapter ()->beginTransaction ();
				try {
					$allFormValues = $form->getValues ();
					$customerId = Standard_Functions::getCurrentUser ()->customer_id;
					$default_lang_id = Standard_Functions::getCurrentUser ()->default_language_id;
					$customer_module_id = $request->getParam ( "customer_module_id" );
					$language_id = $request->getParam ( "language_id" );
					$this->view->dropdownData = $this->_getDropdownData ( $customerId, $language_id );
					$detailsMapper = new Admin_Model_Mapper_CustomerModuleDetail ();
					$details = $detailsMapper->fetchAll ( "customer_module_id = " . $customer_module_id . " AND language_id=" . $language_id );
					$allFlag = $this->_request->getParam("all",false);
				    if(!$allFlag){
					    if (! $details) {
					        $details = $detailsMapper->fetchAll ( "customer_module_id = " . $customer_module_id . " AND language_id=" . $default_lang_id );
					        $details = $details [0];
					        if (! $details) {
					            $details = new Admin_Model_CustomerModuleDetail ();
					            $details->setCustomerModuleId ( $customer_module_id );
					        }
					        $details->setCustomerModuleDetailId ( "" );
					        $details->setLanguageId ( $language_id );
					    } else {
					        $details = $details [0];
					    }
					    $details->setScreenName ( $request->getParam ( "screen_name" ) );
					    if ($request->getParam ( "background_image", "" ) !== null) {
					        $details->setBackgroundImage ( $request->getParam ( "background_image" ) );
					    }
					    if ($request->getParam ( "background_type", "" ) != "") {
					        $details->setBackgroundType ( $request->getParam ( "background_type" ) );
					    }
					    if(!$request->getParam ( "background_color" )){
					        $details->setBackgroundColor ("");
					    }else{
					        $details->setBackgroundColor ( $request->getParam ( "background_color" ) );
					    }
					    $details->save ();
					}else{
					    $customerLanguageMapper = new Admin_Model_Mapper_CustomerLanguage ();
					    $customerLanguageModel = $customerLanguageMapper->fetchAll ( "customer_id = " . $customerId );
					    $records = $detailsMapper->fetchAll ( "customer_module_id = " . $customer_module_id);
					    if(count($records) == count($customerLanguageModel)){
					        foreach ($records as $record){
					            $record->setScreenName ( $request->getParam ( "screen_name" ) );
					            if ($request->getParam ( "background_image", "" ) !== null) {
					                $record->setBackgroundImage ( $request->getParam ( "background_image" ) );
					            }
					            if ($request->getParam ( "background_type", "" ) != "") {
					                $record->setBackgroundType ( $request->getParam ( "background_type" ) );
					            }
					            if(!$request->getParam ( "background_color")){
					                $record->setBackgroundColor ("");
					            }else{
					                $record->setBackgroundColor ( $request->getParam ( "background_color" ) );
					            }
					            $record->save ();
					        }
					    }else{
					        foreach ($records as $record){
					            $record->delete();
					        }
					        if (is_array ( $customerLanguageModel )) {
					            foreach ( $customerLanguageModel as $languages ) {
					                $details = new Admin_Model_CustomerModuleDetail ();
					                $details->setCustomerModuleId ( $customer_module_id );
					                $details->setLanguageId ( $languages->getLanguageId () );
					                $details->setScreenName ( $request->getParam ( "screen_name" ) );
					                if ($request->getParam ( "background_image", "" ) !== null) {
					                    $details->setBackgroundImage ( $request->getParam ( "background_image" ) );
					                }
					                if ($request->getParam ( "background_type", "" ) != "") {
					                    $details->setBackgroundType ( $request->getParam ( "background_type" ) );
					                }
					                if ($request->getParam ( "background_color", "" ) !== null) {
					                    $details->setBackgroundColor ( $request->getParam ( "background_color" ) );
					                }
					                $details->save ();
					            }
					        }
					    }
					}
					
					$model = $mapper->find ( $customer_module_id ); 
					if($request->getParam("selIcon") != '0') {
						$sel_icon = $request->getParam("selIcon");
						$model->setIcon ($sel_icon);
					}elseif($request->getParam ( "icon_path", "" ) != "") {
						$model->setIcon ( $request->getParam ( "icon_path" ) );
					}
					$model->setVisibility ( $request->getParam ( "visibility", "0" ) );
					$model->setIsPublish("NO");
					$model->save ();
					$mapper->getDbTable ()->getAdapter ()->commit ();
					
					$response = array (
							"success" => 1 
					);
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
		
		echo Zend_Json::encode ( $response );
		exit ();
	}
	public function reorderAction() {
		$this->_helper->layout ()->disableLayout ();
		$this->_helper->viewRenderer->setNoRender ();
		$default_lang_id = Standard_Functions::getCurrentUser ()->default_language_id;
		$customer_id = Standard_Functions::getCurrentUser ()->customer_id;
		
		if ($this->_request->isPost ()) {
			$this->_helper->layout ()->disableLayout ();
			$this->_helper->viewRenderer->setNoRender ();
			
			$user_id = Standard_Functions::getCurrentUser ()->user_id;
			$date_time = Standard_Functions::getCurrentDateTime ();
			$response = array ();
			
			$order = $this->_request->getParam ( "order" );
			
			$mapper = new Admin_Model_Mapper_CustomerModule ();
			$mapper->getDbTable ()->getAdapter ()->beginTransaction ();
			try {
				foreach ( $order as $key => $value ) {
					$model = $mapper->find ( $value );
					$model->setOrderNumber ( $key );
					$model->save ();
				}
				
				$mapper->getDbTable ()->getAdapter ()->commit ();
				if ($model && $model->getCustomerModuleId () != "") {
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
	}
	public function _getDropdownData($customer_id = null, $language_id = null,$preSelectedImage=null) {
		$homeWallpaperMapper = new HomeWallpaper_Model_Mapper_HomeWallpaper ();
		$select = $homeWallpaperMapper->getDbTable ()->select ()->setIntegrityCheck ( false )->from ( array (
				'mhw' => 'module_home_wallpaper' 
		), array () )->joinLeft ( array (
				'mhwd' => 'module_home_wallpaper_detail' 
		), "mhwd.home_wallpaper_id  = mhw.home_wallpaper_id AND mhwd.language_id = " . $language_id, array (
				'text' => 'mhwd.image_title',
				'value' => 'mhwd.home_wallpaper_detail_id' 
		) )->where ( "mhw.customer_id=" . $customer_id );
		$data = $homeWallpaperMapper->getDbTable ()->fetchAll ( $select );
		$slice = $data->toArray ();
		foreach ( $slice as $key=>$value ) {
			$mapper = new HomeWallpaper_Model_Mapper_HomeWallpaperImage ();
			$detail = $mapper->fetchAll( array("home_wallpaper_detail_id = " . $slice[$key] ['value'],"image_path!=''"),"0","1" );
			if(is_array($detail)){
				$image_path = $detail[0]->toArray();
				$resolution_id = $image_path["resolution_id"];
				$img_uri = "resource/home-wallpaper/wallpapers/C" . $customer_id."/R".$resolution_id;
				$slice[$key]["imageSrc"] = $this->view->baseUrl($img_uri ."/" . $image_path['image_path']);
				if($preSelectedImage==$value["value"]){
					$slice[$key]["selected"] = true;
				}	
				else 
					$slice[$key]["selected"] = false;	 				
			}
		}
		return $slice;
	}

	public function _getIcons($customer_module_id){
		$customerModuleMapper = new Admin_Model_Mapper_CustomerModule();
		$moduleDetails = $customerModuleMapper->getDbTable()->fetchAll("customer_module_id = ". $customer_module_id)->toArray();
		$module_id = $moduleDetails[0]['module_id'];
		if($moduleDetails[0]['icon'] != ""){
			$selected_icon = $moduleDetails[0]['icon'];
		}
		$iconMapper = new Default_Model_Mapper_ModuleIcon();
		$iconDetails = $iconMapper->getDbTable()->fetchAll("module_id = ". $module_id)->toArray();
		foreach($iconDetails as $icon){
			$icons[] = $icon["module_icon_path"];
		}
		return array ("icons" =>$icons,"selected" =>$selected_icon);
	}
}