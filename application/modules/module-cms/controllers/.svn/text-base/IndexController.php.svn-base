<?php
class ModuleCms_IndexController extends Zend_Controller_Action {
	var $_module_id;
	public function init() {
		/* Initialize Action Controller Here.. */
		$modulesMapper = new Admin_Model_Mapper_Module ();
		$module = $modulesMapper->fetchAll ( "name ='module-cms'" );
		if (is_array ( $module )) {
			$this->_module_id = $module [0]->getModuleId ();
		}
	}
	public function indexAction() {
		$active_lang_id = Standard_Functions::getCurrentUser ()->active_language_id;
		$this->view->addlink = $this->view->url ( array (
				"module" => "module-cms",
				"controller" => "index",
				"action" => "add" 
		), "default", true );
		$this->view->reorderlink = $this->view->url ( array (
				"module" => "module-cms",
				"controller" => "index",
				"action" => "reorder"
		), "default", true );
		$customer_id = Standard_Functions::getCurrentUser ()->customer_id;
		$language_id = Standard_Functions::getCurrentUser ()->active_language_id;
		$this->view->parentCategories = $this->_getModuleCmsTree ( $customer_id, $language_id, true);
	}
	
	public function reorderAction() {
		$customer_id = Standard_Functions::getCurrentUser ()->customer_id;
		$language_id = Standard_Functions::getCurrentUser ()->active_language_id;
		$this->view->parentCategories = $this->_getModuleCmsTree ( $customer_id, $language_id, true);
		$request = $this->getRequest();
		$parent_id = $request->getParam("id",0);
		$active_lang_id = Standard_Functions::getCurrentUser ()->active_language_id;
		$default_lang_id = Standard_Functions::getCurrentUser ()->default_language_id;
		$detailMapper = new ModuleCms_Model_Mapper_ModuleCmsDetail();
		if($parent_id != 0){
			$details = $detailMapper->getDbTable()->fetchAll("language_id = ".$language_id." AND module_cms_id =" .$parent_id)->toArray();
			$this->view->parentTitle = $details[0]['title'];
		}else{$this->view->parentTitle = 'Root Parents';}	
		if ($this->_request->isPost ()) {
			$this->_helper->layout ()->disableLayout ();
			$this->_helper->viewRenderer->setNoRender ();
	
			$user_id = Standard_Functions::getCurrentUser ()->user_id;
			$date_time = Standard_Functions::getCurrentDateTime ();
			$response = array();
	
			$order = $this->_request->getParam ("order");
	
			$mapper = new ModuleCms_Model_Mapper_ModuleCms();
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
				if($model && $model->getModuleCmsId()!="") {
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
		$mapper = new ModuleCms_Model_Mapper_ModuleCms();
		$select = $mapper->getDbTable ()->
		select ( false )->
		setIntegrityCheck ( false )->
		from ( array (
				"mc" => "module_cms"
		), array (
				"mc.module_cms_id" => "module_cms_id",
				"mc.status" => "status",
				"mc.order" => "order",
				"mc.parent_id" => "parent_id"
		) )->joinLeft ( array (
				"mcd" => "module_cms_detail"
		), "mcd.module_cms_id = mc.module_cms_id AND mcd.language_id=" . $active_lang_id, array (
				"mcd.module_cms_detail_id" => "module_cms_detail_id",
				"mcd.title" => "title",
				"mcd.content" => "content",
				"mcd.thumb" => "thumb"
		) )->where ( "mc.parent_id =".$parent_id." AND mc.customer_id=" . $customer_id )->order("mc.order");
		$response = $mapper->getDbTable()->fetchAll($select)->toArray();
		foreach($response as $key=>$thread){
			$image_path = $thread['mcd.thumb'];
			$image_uri = "resource/module-cms/thumb/";
			$ext_image_path = array_pop ( explode ( ".", $image_path ) );
			if ($image_path!="" && file_exists ( $image_uri . str_replace ( "." . $ext_image_path, "_thumb." . $ext_image_path, $image_path ) )) {
				$image_path = str_replace ( "." . $ext_image_path, "_thumb." . $ext_image_path, $image_path );
			}
			$response[$key]['mcd.thumb'] = "<img src='" .$this->view->baseurl($image_uri.$image_path). "' />";
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
		$form = new ModuleCms_Form_ModuleCms ();
		$action = $this->view->url ( array (
				"module" => "module-cms",
				"controller" => "index",
				"action" => "save" 
		), "default", true );
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
		$form = new ModuleCms_Form_ModuleCms ();
		$request = $this->getRequest ();
		$response = array ();
		if ($this->_request->isPost ()) {
			if ($request->getParam ( "upload", "" ) != "") {
				$adapter = new Zend_File_Transfer_Adapter_Http ();
				$adapter->setDestination ( Standard_Functions::getResourcePath () . "module-cms/images" );
				$adapter->receive ();
				if ($adapter->getFileName ( "thumb" ) != "") {
					$response = array (
							"success" => array_pop ( explode ( '\\', $adapter->getFileName ( "thumb" ) ) ) 
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
					$source_dir = Standard_Functions::getResourcePath () . "module-cms/images/";
					$upload_dir = Standard_Functions::getResourcePath () . "module-cms/thumb/";
					$mapper = new ModuleCms_Model_Mapper_ModuleCms ();
					$mapper->getDbTable ()->getAdapter ()->beginTransaction ();
					$model = new ModuleCms_Model_ModuleCms ( $allFormValues );
					if ($request->getParam ( "module_cms_id", "" ) == "") {
						// Add new cms
						$maxOrder = $mapper->getNextOrder ( $parent_id );
						$model->setOrder ( $maxOrder + 1 );
						$model->setCustomerId ( $customer_id );
						$model->setCreatedBy ( $user_id );
						$model->setCreatedAt ( $date_time );
						$model->setLastUpdatedBy ( $user_id );
						$model->setLastUpdatedAt ( $date_time );
						$model = $model->save ();
						// Save Cms Details
						$module_cms_id = $model->get ( "module_cms_id" );
						$mapperLanguage = new Admin_Model_Mapper_CustomerLanguage ();
						$modelLanguages = $mapperLanguage->fetchAll ( "customer_id = " . $customer_id );
						if (is_array ( $modelLanguages )) {
							$is_uploaded_image = false;
							foreach ( $modelLanguages as $languages ) {
								$modelDetails = new ModuleCms_Model_ModuleCmsDetail ( $allFormValues );
								$modelDetails->setModuleCmsId ( $module_cms_id );
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
						$source_dir = Standard_Functions::getResourcePath () . "module-cms/images/";
						$upload_dir = Standard_Functions::getResourcePath () . "module-cms/thumb/";
						$modelDetails = new ModuleCms_Model_ModuleCmsDetail ( $allFormValues );
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
		$mapper = new ModuleCms_Model_Mapper_ModuleCms ();
		$parent_id = $request->getParam ( "id", 0 );
		$select = $mapper->getDbTable ()->select ( false )->setIntegrityCheck ( false )->from ( array (
				"c" => "module_cms" 
		), array (
				"c.module_cms_id" => "module_cms_id",
				"c.status" => "status",
				"c.order" => "order",
				"c.parent_id" => "parent_id"
		) )->joinLeft ( array (
				"cd" => "module_cms_detail" 
		), "cd.module_cms_id = c.module_cms_id AND cd.language_id = " . $active_lang_id, array (
				"cd.module_cms_detail_id" => "module_cms_detail_id",
				"cd.title" => "title",
				"cd.thumb" => "thumb"
		) )->where ( "c.parent_id = '".$parent_id."' AND c.customer_id=" . Standard_Functions::getCurrentUser ()->customer_id );
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
			if ($row [4] ["cd.module_cms_detail_id"] == "") {
				$mapper = new ModuleCms_Model_Mapper_ModuleCmsDetail ();
				$details = $mapper->fetchAll ( "module_cms_id=" . $row [4] ["c.module_cms_id"] . " AND language_id=" . $default_lang_id );
				if (is_array ( $details )) {
					$details = $details [0];
					$row [4] ["cd.title"] = $row [0] = $details->getTitle ();
					$row [4] ["cd.thumb"] = $row [1] = $details->getThumb ();
				}
			}
			$response ['aaData'] [$rowId] = $row;
			if ($languages) {
				foreach ( $languages as $lang ) {
					$editUrl = $this->view->url ( array (
							"module" => "module-cms",
							"controller" => "index",
							"action" => "edit",
							"id" => $row [4] ["c.module_cms_id"],
							"lang" => $lang ["l.language_id"] 
					), "default", true );
					$edit [] = '<a href="' . $editUrl . '"><img src="images/lang/' . $lang ["logo"] . '" alt="' . $lang ["l.title"] . '" /></a>';
				}
			}
			$deleteUrl = $this->view->url ( array (
					"module" => "module-cms",
					"controller" => "index",
					"action" => "delete",
					"id" => $row [4] ["c.module_cms_id"],
					"p_id" => $row[4] ["c.parent_id"] 
			), "default", true );
			
			$defaultEdit = '<div id="editLanguage">&nbsp;<div class="flag-list">' . implode ( "", $edit ) . '</div></div>';
			$delete = '<a href="' . $deleteUrl . '" class="grid_delete" >' . $this->view->translate ( 'Delete' ) . '</a>';
			$sap = '';
			
			$image_path = $row[4]["cd.thumb"];
			$image_uri = "resource/module-cms/thumb/";
			$ext_image_path = array_pop ( explode ( ".", $image_path ) );
			if ($image_path!="" && file_exists ( $image_uri . str_replace ( "." . $ext_image_path, "_thumb." . $ext_image_path, $image_path ) )) {
				$image_path = str_replace ( "." . $ext_image_path, "_thumb." . $ext_image_path, $image_path );
			}	
			$response['aaData'][$rowId][1] = "<img src='" .$this->view->baseurl($image_uri.$image_path). "' />";
			$response ['aaData'] [$rowId] [4] = $defaultEdit . $sap . $delete;
		}
		$jsonGrid = Zend_Json::encode ( $response );
		$this->_response->appendBody ( $jsonGrid );
	}
	public function editAction() {
		$form = new ModuleCms_Form_ModuleCms ();
		$request = $this->getRequest ();
		if ($request->getParam ( "id", "" ) != "" && $request->getParam ( "lang", "" ) != "") {
			$moduleCmsMapper = new ModuleCms_Model_Mapper_ModuleCms ();
			$module_cms_id = $request->getParam ( "id", "" );
			$language_id = $request->getParam ( "lang", "" );
			$customer_id = Standard_Functions::getCurrentUser ()->customer_id;
			$languageMapper = new Admin_Model_Mapper_Language ();
			$languageData = $languageMapper->find ( $language_id );
			$this->view->language = $languageData->getTitle ();
			$this->view->moduleCmsTree = $this->_getModuleCmsTree ( $customer_id, $language_id );
			$default_lang_id = Standard_Functions::getCurrentUser ()->default_language_id;
			$data = $moduleCmsMapper->find($module_cms_id)->toArray ();
			$form->populate ( $data );
			$datadetails = array ();
			$moduleCmsDetailMapper = new ModuleCms_Model_Mapper_ModuleCmsDetail ();
			if ($moduleCmsDetailMapper->countAll ( "module_cms_id = " . $module_cms_id . " AND language_id = " . $language_id ) > 0) {
				// Record For Language Found
				$dataDetails = $moduleCmsDetailMapper->getDbTable ()->fetchAll ( "module_cms_id = " . $module_cms_id . " AND language_id = " . $language_id )->toArray ();
			} else {
				// Record For Language Not Found
				$dataDetails = $moduleCmsDetailMapper->getDbTable ()->fetchAll ( "module_cms_id = " . $module_cms_id . " AND language_id = " . $default_lang_id )->toArray ();
				$dataDetails [0] ["module_cms_detail_id"] = "";
				$dataDetails [0] ["language_id"] = $language_id;
				//$this->view->category = $dataDetails[0]['title'];
			}
			if (isset ( $dataDetails [0] ) && is_array ( $dataDetails [0] )) {
				$form->populate ( $dataDetails [0] );
				$image_path = $dataDetails[0]['thumb'];
				$image_uri = "resource/module-cms/thumb/";
				$ext_image_path = array_pop ( explode ( ".", $image_path ) );
				if ($image_path!="" && file_exists ( $image_uri . str_replace ( "." . $ext_image_path, "_thumb." . $ext_image_path, $image_path ) )) {
					$image_path = str_replace ( "." . $ext_image_path, "_thumb." . $ext_image_path, $image_path );
				}
				$this->view->image_thumb = $this->view->baseUrl($image_uri ."/" . $image_path);
				
				//sending the parent category title to view
				$parentDetails = $moduleCmsMapper->getDbTable()->fetchAll("module_cms_id = " . $dataDetails[0]['module_cms_id'])->toArray();
				$parentLabel = $moduleCmsDetailMapper->getDbTable ()->fetchAll ( "module_cms_id = " . $parentDetails[0]['parent_id']. " AND language_id = " . $language_id )->toArray();
				if(empty($parentLabel)){
					$this->view->parentCategory = $dataDetails[0]['title'];
				} else {$this->view->parentCategory = $parentLabel[0]['title'];}
			}
			$action = $this->view->url ( array (
					"module" => "module-cms",
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
	public function deleteAction() {
		$this->_helper->layout ()->disableLayout ();
		$this->_helper->viewRenderer->setNoRender ();
		$request = $this->getRequest ();
		
		if (($module_cms_id = $request->getParam ( "id", "" )) != "") {
			$parent_id = $request->getParam("p_id");
			$model = new ModuleCms_Model_ModuleCms ();
			if ($model) {
				try {
					$modelMapper = new ModuleCms_Model_Mapper_ModuleCms ();
					$modelMapper->getDbTable ()->getAdapter ()->beginTransaction ();
					$modelDetailMapper = new ModuleCms_Model_Mapper_ModuleCmsDetail ();
					$data = $modelMapper->fetchAll ( "parent_id =".$module_cms_id." OR module_cms_id = " . $module_cms_id );
					if ($data) {
						foreach ( $data as $moduleCms) {
							$dataDetails = $modelDetailMapper->fetchAll("module_cms_id =" .$moduleCms->getModuleCmsId());
							foreach($dataDetails as $dataDetail){
								$deletedRows = $dataDetail->delete();
							}
							$moduleCms->delete ();
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
	private function _getModuleCmsTree($customer_id = null, $language_id = null, $getOnlyParents = false) {
		// Get customer_id, module_cms_id ,title, parent_id
		$moduleCmsMapper = new ModuleCms_Model_Mapper_ModuleCms ();
		$select = $moduleCmsMapper->getDbTable ()->select ()->setIntegrityCheck ( false )->from ( array (
				'mc' => 'module_cms' 
		), array (
				'id' => 'mc.module_cms_id',
				'parentId' => 'mc.parent_id' 
		) )->joinLeft ( array (
				'mcd' => 'module_cms_detail' 
		), "mcd.module_cms_id  = mc.module_cms_id AND mcd.language_id = " . $language_id, array (
				'text' => 'mcd.title' 
		) );
		if($getOnlyParents){
			$select = $select->where("mc.module_cms_id IN (SELECT distinct(parent_id) FROM module_cms )OR parent_id = 0  AND mc.customer_id=" . $customer_id);
		} else {
			$select = $select->where ( 'mc.customer_id = ' . $customer_id );
		}
		$data = $moduleCmsMapper->getDbTable ()->fetchAll ( $select );
		return Zend_Json::encode ( $data->toArray () );
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
	
		/* copy source image at a resized size */
		imagecopyresampled ( $virtual_image, $source_image, 0, 0, 0, 0, $desired_width, $desired_height, $width, $height );
	
		/* create the physical thumbnail image to its destination */
		imagejpeg ( $virtual_image, $dest );
	}
}