<?php
class ModuleCms1_IndexController extends Zend_Controller_Action {
	var $_module_id;
	public function init() {
		/* Initialize Action Controller Here.. */
		$modulesMapper = new Admin_Model_Mapper_Module ();
		$module = $modulesMapper->fetchAll ( "name ='module-cms-1'" );
		if (is_array ( $module )) {
			$this->_module_id = $module [0]->getModuleId ();
		}
	}
	public function indexAction() {
		$active_lang_id = Standard_Functions::getCurrentUser ()->active_language_id;
		$this->view->addlink = $this->view->url ( array (
				"module" => "module-cms-1",
				"controller" => "index",
				"action" => "add" 
		), "default", true );
		$this->view->reorderlink = $this->view->url ( array (
				"module" => "module-cms-1",
				"controller" => "index",
				"action" => "reorder"
		), "default", true );
		$customer_id = Standard_Functions::getCurrentUser ()->customer_id;
		$language_id = Standard_Functions::getCurrentUser ()->active_language_id;
		$this->view->parentCategories = $this->_getModuleCmsTree ( $customer_id, $language_id, true);
	}
	public function uploadimageAction(){
		$this->_helper->layout ()->disableLayout ();
		if ($this->_request->isPost ()) {
			//@todo Change base_dir!
			$base_dir = $this->view->baseUrl();
			//@todo Change image location and naming (if needed)
			$image = $_FILES["image"]["name"];
			$adapter = new Zend_File_Transfer_Adapter_Http ();
			$adapter->setDestination ( Standard_Functions::getResourcePath () . "module-cms-1/tinymce" );
			$adapter->receive ();
			//move_uploaded_file($_FILES["image"]["name"], $base_dir ."/resource/module-cms/tinymce");
			//die;
			$source_dir = "resource/module-cms-1/tinymce/";
			//echo $this->view->baseurl( $source_dir.$image);die;
			$this->view->imageSet = $this->view->baseUrl( $source_dir.$image);
		}		
	}
	public function reorderAction() {
		$customer_id = Standard_Functions::getCurrentUser ()->customer_id;
		$language_id = Standard_Functions::getCurrentUser ()->active_language_id;
		$this->view->parentCategories = $this->_getModuleCmsTree ( $customer_id, $language_id, true);
		$request = $this->getRequest();
		$parent_id = $request->getParam("id",0);
		$active_lang_id = Standard_Functions::getCurrentUser ()->active_language_id;
		$default_lang_id = Standard_Functions::getCurrentUser ()->default_language_id;
		$detailMapper = new ModuleCms1_Model_Mapper_ModuleCmsDetail1();
		if($parent_id != 0){
			$details = $detailMapper->getDbTable()->fetchAll("language_id = ".$language_id." AND module_cms_1_id =" .$parent_id)->toArray();
			$this->view->parentTitle = $details[0]['title'];
		}else{$this->view->parentTitle = 'Root Parents';}	
		if ($this->_request->isPost ()) {
			$this->_helper->layout ()->disableLayout ();
			$this->_helper->viewRenderer->setNoRender ();
	
			$user_id = Standard_Functions::getCurrentUser ()->user_id;
			$date_time = Standard_Functions::getCurrentDateTime ();
			$response = array();
	
			$order = $this->_request->getParam ("order");
			$mapper = new ModuleCms1_Model_Mapper_ModuleCms1();
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
				if($model && $model->getModuleCms1Id()!="") {
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
		$mapper = new ModuleCms1_Model_Mapper_ModuleCms1();
		$select = $mapper->getDbTable ()->
		select ( false )->
		setIntegrityCheck ( false )->
		from ( array (
				"mc" => "module_cms_1"
		), array (
				"mc.module_cms_1_id" => "module_cms_1_id",
				"mc.status" => "status",
				"mc.order" => "order",
				"mc.parent_id" => "parent_id"
		) )->joinLeft ( array (
				"mcd" => "module_cms_detail_1"
		), "mcd.module_cms_1_id = mc.module_cms_1_id AND mcd.language_id=" . $active_lang_id, array (
				"mcd.module_cms_detail_1_id" => "module_cms_detail_1_id",
				"mcd.title" => "title",
				"mcd.content" => "content",
				"mcd.thumb" => "thumb"
		) )->where ( "mc.parent_id =".$parent_id." AND mc.customer_id=" . $customer_id )->order("mc.order");
		$response = $mapper->getDbTable()->fetchAll($select)->toArray();
		foreach($response as $key=>$thread){
			$image_path = $thread['mcd.thumb'];
			$image_uri = "resource/module-cms-1/thumb/";
			$ext_image_path = array_pop ( explode ( ".", $image_path ) );
			if ($image_path!="" && file_exists ( $image_uri . str_replace ( "." . $ext_image_path, "_thumb." . $ext_image_path, $image_path ) )) {
				$image_path = str_replace ( "." . $ext_image_path, "_thumb." . $ext_image_path, $image_path );
			}
			$response[$key]['mcd.thumb'] = "<img src='" .$this->view->baseUrl($image_uri.$image_path). "' />";
		}
		$this->view->data = $response;
	}
	public function addAction() {
		$lang_id = Standard_Functions::getCurrentUser ()->default_language_id;
		$language = new Admin_Model_Mapper_Language();
		$lang = $language->find($lang_id);
		$this->view->language = $lang->getTitle();
		
		$customer_id = Standard_Functions::getCurrentUser ()->customer_id;
		$language_id = Standard_Functions::getCurrentUser ()->active_language_id;
		$this->view->moduleCmsTree = $this->_getModuleCmsTree ( $customer_id, $language_id, false );
		// add Action
		$form = new ModuleCms1_Form_ModuleCms1 ();
		$form->getElement("parent_id")->setValue(0);
		$action = $this->view->url ( array (
				"module" => "module-cms-1",
				"controller" => "index",
				"action" => "save" 
		), "default", true );
		$this->view->uploadimagelink = $this->view->url ( array (
				"module" => "module-cms-1",
				"controller" => "index",
				"action" => "uploadimage"
		), "default", true );
		$this->view->publicUrl = $this->view->baseUrl();
		$form->setMethod ( 'POST' );
		$form->setAction ( $action );
		$this->view->form = $form;
		$this->view->assign ( array (
				"partial" => "index/partials/add.phtml" 
		) );
		$this->render ( "add-edit" );
	}
	public function saveAction() {
		$this->_helper->layout ()->disableLayout ();
		$this->_helper->viewRenderer->setNoRender ();
		$form = new ModuleCms1_Form_ModuleCms1 ();
		$request = $this->getRequest ();
		$response = array ();
		if ($this->_request->isPost ()) {
			if ($request->getParam ( "upload", "" ) != "") {
				$adapter = new Zend_File_Transfer_Adapter_Http ();
				$adapter->setDestination ( Standard_Functions::getResourcePath () . "module-cms-1/images" );
				$adapter->receive ();
				if ($adapter->getFileName ( "thumb" ) != "") {
					$response = array (
							"success" => array_pop ( explode ( '/', $adapter->getFileName ( "thumb" ) ) ) 
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
			$form->removeElement("thumb");
			if ($form->isValid ( $this->_request->getParams () )) {
				try {
					$allFormValues = $form->getValues ();
					$parent_id = $allFormValues['parent_id'];
					$customer_id = Standard_Functions::getCurrentUser ()->customer_id;
					$user_id = Standard_Functions::getCurrentUser ()->user_id;
					$date_time = Standard_Functions::getCurrentDateTime ();
					$thumb_path = $request->getParam ( "thumb_path", "" );
					$source_dir = Standard_Functions::getResourcePath () . "module-cms-1/images/";
					$upload_dir = Standard_Functions::getResourcePath () . "module-cms-1/thumb/";
					$mapper = new ModuleCms1_Model_Mapper_ModuleCms1 ();
					$mapper->getDbTable ()->getAdapter ()->beginTransaction ();
					$model = new ModuleCms1_Model_ModuleCms1 ( $allFormValues );
					if ($request->getParam ( "module_cms_1_id", "" ) == "" || $request->getParam("parent") == "changed") {
						$maxOrder = $mapper->getNextOrder ( $parent_id,$customer_id );
						$model->setOrder ( $maxOrder + 1 );
					}
					if ($request->getParam ( "module_cms_1_id", "" ) == "") {
						// Add new cms
						$model->setCustomerId ( $customer_id );
						$model->setCreatedBy ( $user_id );
						$model->setCreatedAt ( $date_time );
						$model->setLastUpdatedBy ( $user_id );
						$model->setLastUpdatedAt ( $date_time );
						$model = $model->save ();
						// Save Cms Details
						$module_cms_id = $model->get ( "module_cms_1_id" );
						$mapperLanguage = new Admin_Model_Mapper_CustomerLanguage ();
						$modelLanguages = $mapperLanguage->fetchAll ( "customer_id = " . $customer_id );
						if (is_array ( $modelLanguages )) {
							$is_uploaded_image = false;
							foreach ( $modelLanguages as $languages ) {
								$modelDetails = new ModuleCms1_Model_ModuleCmsDetail1 ( $allFormValues );
								$modelDetails->setModuleCms1Id ( $module_cms_id );
								$modelDetails->setLanguageId ( $languages->getLanguageId () );
								if (! is_dir ( $upload_dir )) {
									mkdir ( $upload_dir, 755 );
								}
								if (!$is_uploaded_image && $thumb_path != "") {
									$filename = $this->moveUploadFile ( $source_dir, $upload_dir, $thumb_path );
									$modelDetails->setThumb ($filename);
									$is_uploaded_image = true;
								} else if($request->getParam ( "thumb_path" ,null) !== null) {
									$modelDetails->setThumb ($thumb_path );
								}
								$modelDetails = $modelDetails->save ();
							}
						}
					} else {
						// update cms
						$model->setLastUpdatedBy ( $user_id );
						$model->setLastUpdatedAt ( $date_time );
						$model = $model->save ();
						// update cms details
						$source_dir = Standard_Functions::getResourcePath () . "module-cms-1/images/";
						$upload_dir = Standard_Functions::getResourcePath () . "module-cms-1/thumb/";
						$modelDetails = new ModuleCms1_Model_ModuleCmsDetail1 ( $allFormValues );
						if (! is_dir ( $upload_dir )) {
							mkdir ( $upload_dir, 755 );
						}
						if ($thumb_path != "") {
							$filename = $this->moveUploadFile ( $source_dir, $upload_dir,$thumb_path );
							$modelDetails->setThumb ($filename);
							$is_uploaded_image = true;
						}
						$modelDetails = $modelDetails->save ();
					}
					$customermoduleMapper = new Admin_Model_Mapper_CustomerModule ();
					$customermodule = $customermoduleMapper->fetchAll ( "customer_id=" . $customer_id . " AND module_id=" . $this->_module_id );
					if (is_array ( $customermodule )) {
						$customermodule = $customermodule [0];
						$customermodule->setIsPublish ( "NO" );
						$customermodule->save ();
					}
					$mapper->getDbTable ()->getAdapter ()->commit ();
					
					$response = array (
							"success" => $model->toArray () 
					);
				} catch ( Exception $ex ) {
					$response = $ex->getMessage ();
				}
			}else{echo 'form is not valid';exit();}
		} else {
			$errors = $form->getMessages ();
			foreach ( $errors as $name => $error ) {
				$errors [$name] = $error [0];
			}
			$response = array (
					"errors" => $errors 
			);
		}
		$this->_helper->json ( $response );
	}
	public function gridAction() {
		$this->_helper->layout ()->disableLayout ();
		$this->_helper->viewRenderer->setNoRender ();
		$request = $this->getRequest();
		$active_lang_id = Standard_Functions::getCurrentUser ()->active_language_id;
		$default_lang_id = Standard_Functions::getCurrentUser ()->default_language_id;
		$mapper = new ModuleCms1_Model_Mapper_ModuleCms1 ();
		//$parent_id = $request->getParam ( "search_parent_id", 0 );
		
		$select = $mapper->getDbTable ()->select ( false )->setIntegrityCheck ( false )->from ( array (
				"c" => "module_cms_1" 
		), array (
				"c.module_cms_1_id" => "module_cms_1_id",
				"c.status" => "status",
				"c.order" => "order",
				"c.parent_id" => "parent_id"
		) )->joinLeft ( array (
				"cd" => "module_cms_detail_1" 
		), "cd.module_cms_1_id = c.module_cms_1_id AND cd.language_id = " . $active_lang_id, array (
				"cd.module_cms_detail_1_id" => "module_cms_detail_1_id",
				"cd.title" => "title",
				"cd.thumb" => "thumb"
		) )->where ( "c.customer_id=" . Standard_Functions::getCurrentUser ()->customer_id );
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
				),
				 'search_type' => array(
			  		'c.parent_id'=>"="
			  ) 
		), "customer_id=" . Standard_Functions::getCurrentUser ()->customer_id, $select );
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
			if ($row [4] ["cd.module_cms_detail_1_id"] == "") {
				$detailmapper = new ModuleCms1_Model_Mapper_ModuleCmsDetail1 ();
				$details = $detailmapper->fetchAll ( "module_cms_1_id=" . $row [4] ["c.module_cms_1_id"] . " AND language_id=" . $default_lang_id );
				if (is_array ( $details )) {
					$details = $details [0];
					$row [4] ["cd.title"] = $row [0] = $details->getTitle ();
					$row [4] ["cd.thumb"] = $row [1] = $details->getThumb ();
				}
			}
			$response ['aaData'] [$rowId] = $row;
			$mapper = new ModuleCms1_Model_Mapper_ModuleCms1();
			if($row[4]["c.parent_id"] !== "" && $row[4]["c.parent_id"] !== null){
				$select = $mapper->getDbTable()->select(false)
				->setIntegrityCheck(false)
				->from(array("mc" =>"module_cms_1"),
					array("mc.parent_id"))
				->joinLeft(array("mcd"=>"module_cms_detail_1")
					,"mc.module_cms_1_id = mcd.module_cms_1_id",array("mcd.language_id"))
				->where("mc.parent_id ='".$row [4] ['c.module_cms_1_id']."' AND mcd.language_id =".$active_lang_id);
				$childs = $mapper->countAll($select);
				if($childs>0)
					$response['aaData'][$rowId][0] = '<span class="child" id="'.$row [4] ["c.module_cms_1_id"].'">'.$row[0]."<i style='color:#009606'> Child: ".$childs.'</i></span>';
				else
					$response['aaData'][$rowId][0] = $row[0];
			}
			
			if ($languages) {
				foreach ( $languages as $lang ) {
					$editUrl = $this->view->url ( array (
							"module" => "module-cms-1",
							"controller" => "index",
							"action" => "edit",
							"id" => $row [4] ["c.module_cms_1_id"],
							"lang" => $lang ["l.language_id"] 
					), "default", true );
					$edit [] = '<a href="' . $editUrl . '"><img src="images/lang/' . $lang ["logo"] . '" alt="' . $lang ["l.title"] . '" /></a>';
				}
			}
			$deleteUrl = $this->view->url ( array (
					"module" => "module-cms-1",
					"controller" => "index",
					"action" => "delete",
					"id" => $row [4] ["c.module_cms_1_id"],
					"p_id" => $row[4] ["c.parent_id"] 
			), "default", true );
			
			$defaultEdit = '<div id="editLanguage">&nbsp;<div class="flag-list">' . implode ( "", $edit ) . '</div></div>';
			$delete = '<a href="' . $deleteUrl . '" class="button-grid greay grid_delete" >'.$this->view->translate('Delete').'</a>';
			$sap = '';
			$image_path = $row[4]["cd.thumb"];
			$image_uri = "resource/module-cms-1/thumb/";
			$ext_image_path = array_pop ( explode ( ".", $image_path ) );
			if ($image_path!="" && file_exists ( $image_uri . str_replace ( "." . $ext_image_path, "_thumb." . $ext_image_path, $image_path ) )) {
				$image_path = str_replace ( "." . $ext_image_path, "_thumb." . $ext_image_path, $image_path );
			}	
			$response['aaData'][$rowId][1] = "<img src='" .$this->view->baseUrl($image_uri.$image_path). "' />";
			$response ['aaData'] [$rowId] [4] = $defaultEdit . $sap . $delete;
		}
		$jsonGrid = Zend_Json::encode ( $response );
		$this->_response->appendBody ( $jsonGrid );
	}
	public function editAction() {
		$form = new ModuleCms1_Form_ModuleCms1 ();
		$request = $this->getRequest ();
		if ($request->getParam ( "id", "" ) != "" && $request->getParam ( "lang", "" ) != "") {
			$moduleCmsMapper = new ModuleCms1_Model_Mapper_ModuleCms1 ();
			$module_cms_id = $request->getParam ( "id", "" );
			$language_id = $request->getParam ( "lang", "" );
			$customer_id = Standard_Functions::getCurrentUser ()->customer_id;
			$languageMapper = new Admin_Model_Mapper_Language ();
			$languageData = $languageMapper->find ( $language_id );
			$this->view->language = $languageData->getTitle ();
			$this->view->moduleCmsTree = $this->_getModuleCmsTree ( $customer_id, $language_id,false,$module_cms_id );
			$default_lang_id = Standard_Functions::getCurrentUser ()->default_language_id;
			$data = $moduleCmsMapper->find($module_cms_id)->toArray ();
			$this->view->orignalParent = $data['parent_id'];
			$form->populate ( $data );
			$datadetails = array ();
			$moduleCmsDetailMapper = new ModuleCms1_Model_Mapper_ModuleCmsDetail1 ();
			if ($moduleCmsDetailMapper->countAll ( "module_cms_1_id = " . $module_cms_id . " AND language_id = " . $language_id ) > 0) {
				// Record For Language Found
				$dataDetails = $moduleCmsDetailMapper->getDbTable ()->fetchAll ( "module_cms_1_id = " . $module_cms_id . " AND language_id = " . $language_id )->toArray ();
			} else {
				// Record For Language Not Found
				$dataDetails = $moduleCmsDetailMapper->getDbTable ()->fetchAll ( "module_cms_1_id = " . $module_cms_id . " AND language_id = " . $default_lang_id )->toArray ();
				$dataDetails [0] ["module_cms_detail_1_id"] = "";
				$dataDetails [0] ["language_id"] = $language_id;
				//$this->view->category = $dataDetails[0]['title'];
			}
			if (isset ( $dataDetails [0] ) && is_array ( $dataDetails [0] )) {
				$form->populate ( $dataDetails [0] );
				$image_path = $dataDetails[0]['thumb'];
				$image_uri = "resource/module-cms-1/thumb/";
				$ext_image_path = array_pop ( explode ( ".", $image_path ) );
				if ($image_path!="" && file_exists ( $image_uri . str_replace ( "." . $ext_image_path, "_thumb." . $ext_image_path, $image_path ) )) {
					$image_path = str_replace ( "." . $ext_image_path, "_thumb." . $ext_image_path, $image_path );
				}
				$this->view->image_thumb = $this->view->baseUrl($image_uri ."/" . $image_path);
				
				//sending the parent category title to view
				if($data['parent_id'] != 0){
					$parentDetails = $moduleCmsMapper->getDbTable()->fetchAll("module_cms_1_id = " . $dataDetails[0]['module_cms_1_id'])->toArray();
					$parentLabel = $moduleCmsDetailMapper->getDbTable ()->fetchAll ( "module_cms_1_id = " . $parentDetails[0]['parent_id']. " AND language_id = " . $language_id )->toArray();
					if(empty($parentLabel)){
						$this->view->parentCategory = $dataDetails[0]['title'];
					} else {$this->view->parentCategory = $parentLabel[0]['title'];}
				}else{
					$this->view->parentCategory = "Parent";
				}
			}
			$action = $this->view->url ( array (
					"module" => "module-cms-1",
					"controller" => "index",
					"action" => "save",
					"id" => $request->getParam ( "id", "" ) 
			), "default", true );
			$form->setAction ( $action );
		} else {
			$this->_redirect ( '/' );
		}
		$this->view->uploadimagelink = $this->view->url ( array (
				"module" => "module-cms-1",
				"controller" => "index",
				"action" => "uploadimage"
		), "default", true );
		$this->view->publicUrl = $this->view->baseUrl();
		$this->view->form = $form;
		$this->view->assign ( array (
				"partial" => "index/partials/edit.phtml" 
		) );
		$this->render ( "add-edit" );
	}
	public function deleteAction() {
		$this->_helper->layout ()->disableLayout ();
		$this->_helper->viewRenderer->setNoRender ();
		$request = $this->getRequest ();
		
		if (($module_cms_id = $request->getParam ( "id", "" )) != "") {
			$childs = $this->_getChilds($module_cms_id);
			array_unshift($childs, $module_cms_id);
			$model = new ModuleCms1_Model_ModuleCms1 ();
			if ($model) {
				try {
					$modelMapper = new ModuleCms1_Model_Mapper_ModuleCms1 ();
					$modelMapper->getDbTable ()->getAdapter ()->beginTransaction ();
					foreach ($childs as $child) {
						$modelDetailMapper = new ModuleCms1_Model_Mapper_ModuleCmsDetail1 ();	
						$data = $modelMapper->getDbTable()->fetchAll ( "module_cms_1_id =".$child);
						if ($data) {
							$dataDetails = $modelDetailMapper->fetchAll("module_cms_1_id =" .$child);
							foreach($dataDetails as $dataDetail){
								$deletedRows = $dataDetail->delete();
							}
							$data[0]->delete();
						}
					}
					$customer_id = Standard_Functions::getCurrentUser ()->customer_id;
					$customermoduleMapper = new Admin_Model_Mapper_CustomerModule ();
					$customermodule = $customermoduleMapper->fetchAll ( "customer_id=" . $customer_id . " AND module_id=" . $this->_module_id );
					if (is_array ( $customermodule )) {
						$customermodule = $customermodule [0];
						$customermodule->setIsPublish ( "NO" );
						$customermodule->save ();
					}
					
					$modelMapper->getDbTable ()->getAdapter ()->commit ();
					
					$response = array (
							"success" => array (
									"deleted_rows" => $deletedRows 
							) 
					);
				} catch ( Exception $e ) {
					
					//$modelMapper->getDbTable ()->getAdapter ()->rollBack ();
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
	private function _getModuleCmsTree($customer_id = null, $language_id = null, $getOnlyParents = false,$nochilds = false) {
		// Get customer_id, module_cms_id ,title, parent_id
		$moduleCmsMapper = new ModuleCms1_Model_Mapper_ModuleCms1 ();
		$select = $moduleCmsMapper->getDbTable ()->select ()->setIntegrityCheck ( false )->from ( array (
				'mc' => 'module_cms_1' 
		), array (
				'id' => 'mc.module_cms_1_id',
				'parentId' => 'mc.parent_id' 
		) )->joinLeft ( array (
				'mcd' => 'module_cms_detail_1' 
		), "mcd.module_cms_1_id  = mc.module_cms_1_id", array (
				'text' => 'mcd.title' 
		) );
		if($getOnlyParents){
			$parent_ids = $this->_getParentIds();
			$select = $select->where("mc.customer_id ='".$customer_id."' AND mcd.language_id ='".$language_id."' AND mc.module_cms_1_id IN('".$parent_ids."')");
		} elseif($nochilds) {
			$blacklisted = array();			
			$blacklisted = $this->_getChilds($nochilds);
			$string = is_array($blacklisted)?implode("','",$blacklisted):false;
			$select = $select->where ( "mc.module_cms_1_id NOT IN('".$string."') AND mc.parent_id !='".$nochilds."' AND mc.module_cms_1_id != '".$nochilds."' AND mc.customer_id = '" . $customer_id . "' AND mcd.language_id =".$language_id );
		} else {
			$select = $select->where ( "mc.customer_id = '".$customer_id."' AND mcd.language_id =".$language_id );
		}
		$select->order(array('mc.parent_id',"mc.order"));
		$data = $moduleCmsMapper->getDbTable ()->fetchAll ( $select );
		return Zend_Json::encode ( $data->toArray () );
	}
	
	private function _getParentIds(){
		$parentIds = array();
		$mapper = new ModuleCms1_Model_Mapper_ModuleCms1();
		$select = $mapper->getDbTable()->select()->setIntegrityCheck(false)
				->from(array('mc'=>'module_cms_1'),'mc.parent_id')
				->distinct('mc.parent_id');
		$parentIdArrays = $mapper->getDbTable()->fetchAll($select);
		foreach ($parentIdArrays as $parentIdArray) {
			foreach ($parentIdArray as $parentId) {
				$parentIds[] = $parentId;
			}
		}
		$parentString = !empty($parentIds)?implode("','", $parentIds):false;
		return $parentString;
	}

	private function _getChilds($childs){
		if(!is_array($childs)){
			$childs = array($childs);
		}
		$blacklisted = array();
		while(count($this->_getBlacklistedIds($childs)) != 0){
			$resultset = $this->_getBlacklistedIds($childs);
			foreach ($resultset as $result) {
				$blacklisted[] = $result;
			}
			$childs = $resultset;
		}
		return $blacklisted;
	}

	private function _getBlacklistedIds(array $ids = array()){
		$moduleCmsMapper = new ModuleCms1_Model_Mapper_ModuleCms1 ();
		$idstack = array();
		foreach ($ids as $id) {
			$result = $moduleCmsMapper->getDbTable()->fetchAll("parent_id =".$id)->toArray();
			if(is_array($result)){
				foreach ($result as $result) {
					$idstack[] = $result['module_cms_1_id'];
				}
			}
		}
		return $idstack;
	}
	
	private function moveUploadFile($source_dir, $dest_dir, $filename) {
		$source_file_name = $filename;
		$expension = array_pop ( explode ( ".", $filename ) );
		try {
			$i = 1;
			while ( file_exists ( $dest_dir . $filename ) ) {
				$filename = str_replace ( "." . $expension, "_" . $i ++ . "." . $expension, $source_file_name );
			}
			if (! is_dir ( $dest_dir )) {
				mkdir ( $dest_dir, 755 );
			}
			while(!file_exists($source_dir . $source_file_name)) {
			}
				
			if (copy ( $source_dir . $source_file_name, $dest_dir . $filename )) {
				
			}
			$thumbname = str_replace ( "." . $expension, "_thumb." . $expension, $filename );
			$this->generateThumb ( $dest_dir . $filename, $dest_dir . $thumbname, 0, 75 );
		} catch ( Exception $ex ) {
				
		}
		return $filename;
	}
	
	public function generateThumb($src, $dest, $destWidth = 0, $destHeight = 0) {
		/* read the source image */
		$stype = array_pop ( explode ( ".", $src ) );
		switch ($stype) {
			case 'gif' :
				$source_image = imagecreatefromgif ( $src );
				break;
			case 'jpg' :
			case 'jpeg' :
				$source_image = imagecreatefromjpeg ( $src );
				break;
			case 'png' :
				$source_image = imagecreatefrompng ( $src );
				break;
		}
	
		$width = imagesx ( $source_image );
		$height = imagesy ( $source_image );
	
		$desired_height = 0;
		$desired_width = 0;
		if ($destWidth == 0) {
			$desired_height = $destHeight;
			$desired_width = floor ( $width * ($destHeight / $height) );
		} else {
			$desired_height = floor ( $destHeight * ($destWidth / $width) );
			$desired_width = $destWidth;
		}
	
		/* create a new, "virtual" image */
		$virtual_image = imagecreatetruecolor ( $desired_width, $desired_height );
		
		imagealphablending($virtual_image, false);
		imagesavealpha($virtual_image, true);
		
		imagealphablending($source_image, true);
		/* copy source image at a resized size */
		imagecopyresampled ( $virtual_image, $source_image, 0, 0, 0, 0, $desired_width, $desired_height, $width, $height );
	
		/* create the physical thumbnail image to its destination */
		
		switch ($stype) {
			case 'gif' :
				imagegif ( $virtual_image, $dest );
				break;
			case 'jpg' :
			case 'jpeg' :
				imagejpeg ( $virtual_image, $dest );
				break;
			case 'png' :
				imagepng ( $virtual_image, $dest,0 );
				break;
		}
	}
}