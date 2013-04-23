<?php
class Document_ExplorerController extends Zend_Controller_Action
{
	var $_module_id;

	public function init()
	{
		/* Initialize action controller here */
		$modulesMapper = new Admin_Model_Mapper_Module();
		$module = $modulesMapper->fetchAll("name ='document'");
		if(is_array($module)) {
			$this->_module_id = $module[0]->getModuleId();
		}
		$image_dir = Standard_Functions::getResourcePath(). "document/preset-icons";
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
		$form = new Document_Form_DocumentCategory();
		$form->getElement("parent_id")->setValue(0);
		$action = $this->view->url ( array (
				"module" => "document",
				"controller" => "category",
				"action" => "save"
		), "default", true );
		$form->setMethod ( 'POST' );
		$form->setAction ( $action );
		$this->view->formCategory = $form;
		$form = new Document_Form_Document();
		foreach ( $form->getElements () as $element ) {
			if ($element->getDecorator ( 'Label' ))
				$element->getDecorator ( 'Label' )->setTag ( null );
		}
		$action = $this->view->url ( array (
				"module" => "document",
				"controller" => "index",
				"action" => "save"
		), "default", true );
		$form->setAction($action);
		$this->view->form = $form;
		$this->view->languages = Standard_Functions::getCustomerLanguages();
	}
	public function getTreeAction()
	{
		$this->_helper->layout ()->disableLayout ();
		$this->_helper->viewRenderer->setNoRender ();
		// action body
		$tree = array( 
					array(
						"data"=>"Root",
						"attr"=> array(
							"id"=>"node_0",
							"rel"=>"drive"
						),
						"children" => array($this->getChildren(0))
					)
				);
		echo Zend_Json::encode ( $tree );
	}
	public function getChildren($parent_id,$recursive=true) {
		$active_lang_id = Standard_Functions::getCurrentUser ()->active_language_id;
		$condition = "dc.customer_id = ".Standard_Functions::getCurrentUser ()->customer_id;
		$condition .= " AND dc.parent_id=".$parent_id;
		$child = array();
		$mapper = new Document_Model_Mapper_ModuleDocumentCategory();
		$select = $mapper->getDbTable ()->
							select ( false )->
							setIntegrityCheck ( false )->from(array ("dc" => "module_document_category"),
									array (
											"dc.module_document_category_id" => "module_document_category_id",
											"dc.status" => "status",
											"dc.order" => "order"
							))->joinLeft(array ("dcd" => "module_document_category_detail"),
										"dcd.module_document_category_id = dc.module_document_category_id AND dcd.language_id = ".$active_lang_id,
										array ("dcd.module_document_category_detail_id" => "module_document_category_detail_id",
												"dcd.title" => "title"
							))->where($condition)->order("dc.order");
		$categories = $mapper->getDbTable()->fetchAll($select)->toArray();
		foreach ($categories as $cat) {
			$child[] = array(
					"data"=>$cat["dcd.title"],
					"attr"=> array(
							"id"=>"node_".$cat["dc.module_document_category_id"],
							"rel"=>"folder"
					),
					"children" => ($recursive)? $this->getChildren($cat["dc.module_document_category_id"]) : array()
			);
		}
		$condition = "d.module_document_category_id=".$parent_id;
		$condition .= " AND d.customer_id = ".Standard_Functions::getCurrentUser ()->customer_id;
		$mapper = new Document_Model_Mapper_ModuleDocument();
		$select = $mapper->getDbTable ()->
						select ( false )->
						setIntegrityCheck ( false )->from(array ("d" => "module_document"),
						array (
								"d.module_document_id" => "module_document_id",
								"d.status" => "status",
								"d.order" => "order"
						))->joinLeft(array ("dd" => "module_document_detail"),
								"dd.module_document_id = d.module_document_id AND dd.language_id = ".$active_lang_id,
								array ("dd.module_document_detail_id" => "module_document_detail_id",
										"dd.title" => "title"
						))->where($condition)->order("d.order");
		$categories = $mapper->getDbTable()->fetchAll($select)->toArray();
		foreach ($categories as $cat) {
			$child[] = array(
					"data"=>$cat["dd.title"],
					"attr"=> array(
							"id"=>"file_".$cat["d.module_document_id"],
							"rel"=>"default"
					),
					"children" => array()
			);
		}
		return $child;
	}
	
	public function getContentAction()
	{
		$this->_helper->layout ()->disableLayout ();
		$this->_helper->viewRenderer->setNoRender ();
		$response = array();
		$parent_id = $this->_request->getParam("parent_id",null);
		if($parent_id!=null) {
			$response["success"] = true;
			$response["data"] = $this->getChildren($parent_id,false);
		} else {
			$response["errors"] = "Unable to fetch content";
		}
		echo Zend_Json::encode ( $response );
	}
	public function editAction()
	{
		$this->_helper->layout ()->disableLayout ();
		$this->_helper->viewRenderer->setNoRender ();
		$image_dir = $this->view->baseUrl("resource/document/preset-icons");
		$resource_dir = $this->view->baseUrl("resource/document/uploaded-icons");
		$response = array();
		$id = $this->_request->getParam("id",null);
		$language_id = $this->_request->getParam("language_id",null);
		if($id!=null && $language_id != null) {
			$data = array();
			$node = explode("_",$id);
			if($node[0]=="node") {
				$mapper = new Document_Model_Mapper_ModuleDocumentCategory();
				$select = $mapper->getDbTable ()->
				select ( false )->
				setIntegrityCheck ( false )->from(array ("dc" => "module_document_category"),
						array (
								"dc.module_document_category_id" => "module_document_category_id",
								"dc.status",
								"dc.parent_id",
								"dc.order" => "order"
						))->joinLeft(array ("dcd" => "module_document_category_detail"),
								"dcd.module_document_category_id = dc.module_document_category_id AND dcd.language_id = ".$language_id,
								array ("dcd.module_document_category_detail_id" => "module_document_category_detail_id",
										"dcd.title" => "title",
								        "dcd.icon" => "icon",
								))->where("dc.module_document_category_id=".$node[1])->order("dc.order");
				$categories = $mapper->getDbTable()->fetchAll($select)->toArray();
				if(is_array($categories) && isset($categories[0])) {
					$data["title"] = $categories[0]["dcd.title"];
					$data["status"] = $categories[0]["status"];
					$data["parent_id"] = $categories[0]["parent_id"];
					$data["language_id"] = $language_id;
					$data["module_document_category_detail_id"] = $categories[0]["dcd.module_document_category_detail_id"];
					$data["module_document_category_id"] = $categories[0]["dc.module_document_category_id"];
					$data["icon"] = $categories[0]["dcd.icon"];
					if($data['icon'] != null){
					    if(count(explode('/',$data['icon'])) > 1){
					        $data["caticonpath"] = $resource_dir.$data['icon'];
					    }else{
					        $data["caticonpath"] = $image_dir."/".$data['icon'];
					    }
					}
				}
			} else if($node[0]=="file") {
				$mapper = new Document_Model_Mapper_ModuleDocument();
				$select = $mapper->getDbTable ()->
									select ( false )->
									setIntegrityCheck ( false )->
									from ( array ("d" => "module_document"),
									array (
											"d.module_document_id" => "module_document_id",
											"d.module_document_category_id"=>"module_document_category_id",
											"d.status" => "status",
											"d.order" => "order"))->
									joinLeft ( array ("dd" => "module_document_detail"),
											"dd.module_document_id = d.module_document_id AND dd.language_id = ".$language_id,
											array (
													"dd.module_document_detail_id" => "module_document_detail_id",
													"dd.title" => "title",
													"dd.description" => "description",
													"dd.document_path" => "document_path",
													"dd.keywords" => "keywords"))->
									where("d.module_document_id=".$node[1])->order("d.order");
				$document = $mapper->getDbTable()->fetchAll($select)->toArray();
				if(is_array($document) && isset($document[0])) {
					$data["module_document_id"] = $document[0]["d.module_document_id"];
					$data["module_document_category_id"] = $document[0]["d.module_document_category_id"];
					$data["status"] = $document[0]["d.status"];
					$data["language_id"] = $language_id;
					$data["module_document_detail_id"] = $document[0]["dd.module_document_detail_id"];
					$data["title"] = $document[0]["dd.title"];
					$data["description"] = $document[0]["dd.description"];
					$data["document_path"] = $document[0]["dd.document_path"];
					$data["keywords"] = $document[0]["dd.keywords"];
					if($data[0]['icon'] != null){
					    if(count(explode('/',$data[0]['icon'])) > 1){
					        $data["dociconpath"] = $resource_dir.$data[0]['icon'];
					    }else{
					        $data["dociconpath"] = $image_dir."/".$data[0]['icon'];
					    }
					}
				}
			}
			$data["iconpack"] = $this->_iconpack;
			$data["resourceurl"] = $image_dir;
			$response["success"] = true;
			$response["data"] = $data;
		} else {
			$response["errors"] = "Unable to fetch content";
		}
		echo Zend_Json::encode ( $response );
	}
	public function copyAction()
	{
		$this->_helper->layout ()->disableLayout ();
		$this->_helper->viewRenderer->setNoRender ();
		$response = array();
		$ids = $this->_request->getParam("id",null);
		$parent_id = $this->_request->getParam("parent_id",null);
		if($parent_id!=null && $ids!=null) {
			$mapper = new Document_Model_Mapper_ModuleDocumentCategory();
			$mapper->getDbTable()->getAdapter()->beginTransaction();
			try {
				$arr["node"] = array();
				$arr["file"] = array();
				$arrIds = explode(",",$ids);
				foreach($arrIds as $value) {
					$node = explode("_",$value);
					$arr[$node[0]][] = $node[1];
				}
				
				if(count($arr["file"])>0) {
					$mapper = new Document_Model_Mapper_ModuleDocument();
					$model = $mapper->fetchAll("module_document_id in (".implode(",", $arr["file"]).")");
					foreach($model as $document) {
						$source_id = $document->getModuleDocumentId();
						$document->setModuleDocumentId(null);
						$document->setModuleDocumentCategoryId($parent_id);
						$document = $document->save();
						$mapperDetails = new Document_Model_Mapper_ModuleDocumentDetail();
						$modelDetails = $mapperDetails->fetchAll("module_document_id = ".$source_id);
						foreach($modelDetails as $details) {
							$details->setModuleDocumentDetailId(null);
							$details->setModuleDocumentId($document->getModuleDocumentId());
							$details->save();
						}
					}
				}
				
				if(count($arr["node"])>0) {
					$mapper = new Document_Model_Mapper_ModuleDocumentCategory();
					$model = $mapper->fetchAll("module_document_category_id in (".implode(",", $arr["node"]).")");
					foreach($model as $category) {
						$source_id = $category->getModuleDocumentCategoryId();
						$category->setModuleDocumentCategoryId(null);
						$category->setParentId($parent_id);
						$category = $category->save();
						$mapperDetails = new Document_Model_Mapper_ModuleDocumentCategoryDetail();
						$modelDetails = $mapperDetails->fetchAll("module_document_category_id = ".$source_id);
						foreach($modelDetails as $details) {
							$details->setModuleDocumentCategoryDetailId(null);
							$details->setModuleDocumentCategoryId($category->getModuleDocumentCategoryId());
							$details->save();
						}
						$this->copy($source_id,$category->getModuleDocumentCategoryId());
					}
				}
				
				$mapper->getDbTable()->getAdapter()->commit();
				$response["success"] = true;
				$response["data"] = $parent_id;
			} catch(Exception $ex) {
				$mapper->getDbTable()->getAdapter()->rollBack();
				$response["errors"] = "Unable to move content";
			}
		} else {
			$response["errors"] = "Unable to copy content";
		}
		echo Zend_Json::encode ( $response );
	}
	public function copy($source_parent,$dest_parent)
	{
		$mapper = new Document_Model_Mapper_ModuleDocument();
		$model = $mapper->fetchAll("module_document_category_id = ".$source_parent);
		if(is_array($model)) {
			foreach($model as $document) {
				$source_id = $document->getModuleDocumentId();
				$document->setModuleDocumentId(null);
				$document->setModuleDocumentCategoryId($dest_parent);
				$document = $document->save();
				$mapperDetails = new Document_Model_Mapper_ModuleDocumentDetail();
				$modelDetails = $mapperDetails->fetchAll("module_document_id = ".$source_id);
				foreach($modelDetails as $details) {
					$details->setModuleDocumentDetailId(null);
					$details->setModuleDocumentId($document->getModuleDocumentId());
					$details->save();
				}
			}
		}
		$mapper = new Document_Model_Mapper_ModuleDocumentCategory();
		$model = $mapper->fetchAll("parent_id =".$source_parent);
		if(is_array($model)) {
			foreach($model as $category) {
				$source_id = $category->getModuleDocumentCategoryId();
				$category->setModuleDocumentCategoryId(null);
				$category->setParentId($dest_parent);
				$category = $category->save();
				$mapperDetails = new Document_Model_Mapper_ModuleDocumentCategoryDetail();
				$modelDetails = $mapperDetails->fetchAll("module_document_category_id = ".$source_id);
				foreach($modelDetails as $details) {
					$details->setModuleDocumentCategoryDetailId(null);
					$details->setModuleDocumentCategoryId($category->getModuleDocumentCategoryId());
					$details->save();
				}
				$this->copy($source_id,$category->getModuleDocumentCategoryId());
			}
		}
	}
	public function cutAction()
	{
		$this->_helper->layout ()->disableLayout ();
		$this->_helper->viewRenderer->setNoRender ();
		$response = array();
		$ids = $this->_request->getParam("id",null);
		$parent_id = $this->_request->getParam("parent_id",null);
		if($parent_id!=null && $ids!=null) {
			$mapper = new Document_Model_Mapper_ModuleDocumentCategory();
			$mapper->getDbTable()->getAdapter()->beginTransaction();
			try {
				$arr["node"] = array();
				$arr["file"] = array();
				$arrIds = explode(",",$ids);
				foreach($arrIds as $value) {
					$node = explode("_",$value);
					$arr[$node[0]][] = $node[1];
				}
				
				if(count($arr["node"])>0) {
					$model = $mapper->fetchAll("module_document_category_id in (".implode(",", $arr["node"]).")");
					foreach($model as $category) {
						$category->setParentId($parent_id);
						$category->save();
					}
				}
				if(count($arr["file"])>0) {
					$mapper = new Document_Model_Mapper_ModuleDocument();
					$model = $mapper->fetchAll("module_document_id in (".implode(",", $arr["file"]).")");
					foreach($model as $document) {
						$document->setModuleDocumentCategoryId($parent_id);
						$document->save();
					}
				}
				$mapper->getDbTable()->getAdapter()->commit();
				$response["success"] = true;
				$response["data"] = $parent_id;
			} catch(Exception $ex) {
				$mapper->getDbTable()->getAdapter()->rollBack();
				$response["errors"] = "Unable to move content";
			}
		} else {
			$response["errors"] = "Unable to move content";
		}
		echo Zend_Json::encode ( $response );
	}
}