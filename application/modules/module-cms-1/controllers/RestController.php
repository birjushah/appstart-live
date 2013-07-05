<?php
class ModuleCms1_RestController extends Standard_Rest_Controller {
	/*
	 * (non-PHPdoc) @see Zend_Rest_Controller::getAction()
	 */
	public function getAction() {
		// TODO Auto-generated method stub
		$service = $this->_request->getParam("service",null);
		if($service==null) {
			$this->_sendError("No service called");
		} else {
			if($service == "sync") {
				$this->_sync();
			} else {
				$this->_sendError("Invalid service");
			}
		}
	}
	
	/*
	 * (non-PHPdoc) @see Zend_Rest_Controller::postAction()
	 */
	public function postAction() {
		// TODO Auto-generated method stub
	}
	
	/*
	 * (non-PHPdoc) @see Zend_Rest_Controller::putAction()
	 */
	public function putAction() {
		// TODO Auto-generated method stub
	}
	
	/*
	 * (non-PHPdoc) @see Zend_Rest_Controller::deleteAction()
	 */
	public function deleteAction() {
		// TODO Auto-generated method stub
	}
	
	protected function _sync() {
		$customer_id = $this->_request->getParam("customer_id",null);
		if($customer_id===null) {
			$this->_sendError("Invalid request");
		} else {
			try{
				$mapper = new Admin_Model_Mapper_Customer();
				$customer = $mapper->find($customer_id);
				if($customer) {
					$response = array();
					$moduleCmsMapper = new ModuleCms1_Model_Mapper_ModuleCms1();
					$moduleCmsModel = $moduleCmsMapper->fetchAll("customer_id=".$customer_id);
					if($moduleCmsModel) {
						foreach($moduleCmsModel as $models) {
							$cmsDetails = array();
							$moduleCmsDetailMapper = new ModuleCms1_Model_Mapper_ModuleCmsDetail1();
							$moduleCmsDetailModel = $moduleCmsDetailMapper->fetchAll("module_cms_1_id=".$models->getModuleCms1Id());
							if($moduleCmsDetailModel) {
								foreach($moduleCmsDetailModel as $detail_model) {
									$details = $detail_model->toArray();
									$details["content"] = ($details["content"]!="") ? $this->getNativeContent($details["content"]) : "";
								    if(isset($details["thumb"]) && $details["thumb"] != null){
									    if(count(explode("/", $details["thumb"])) > 1){
                                            $details["thumb"] = "resource/module-cms-1/".$details["thumb"];
									    }else{
									        $details["thumb"] = "resource/module-cms-1/preset-icons/".$details["thumb"];
									    }
									}
									$cmsDetails[] = $details;
								}
							}
							
							$response["data"][] = array("tbl_module_cms_1"=>$models->toArray(),"tbl_module_cms_detail_1"=>$cmsDetails);
						}
					}else{
						$response["data"][] = array("tbl_module_cms_1"=>array(),"tbl_module_cms_detail_1"=>array());
					}
					$data["status"] = "success";
					$data["data"] = $response;
					$this->_sendData($data);
				} else {
					$this->_sendError("Invalid customer ID");
				}
			} catch (Exception $ex) {
				$this->_sendError($ex->getMessage());
			}
		}
	}
	public function getNativeContent($content) {
	    $dom = new DOMDocument;
	    libxml_use_internal_errors(true);
	
	    $dom->loadHTML( "<html>".$content ."</html>" );
	    $xpath = new DOMXPath( $dom );
	    libxml_clear_errors();
	
	    $doc = $dom->getElementsByTagName("html")->item(0);
	    $src = $xpath->query(".//@src");
	
	    foreach ( $src as $s ) {
	        if(stripos($s->nodeValue, "/resource/")===0) {
	            $s->nodeValue = ".".$s->nodeValue;
	        }
	        $s->nodeValue = 'data:image/' . filetype($s->nodeValue) . ';base64,' . base64_encode(file_get_contents($s->nodeValue));
	    }
	
	    $output = $dom->saveXML( $doc );
	    return $output;
	}
	/* (non-PHPdoc)
	 * @see Zend_Rest_Controller::headAction()
	 */public function headAction() {
		// TODO Auto-generated method stub
		}

}