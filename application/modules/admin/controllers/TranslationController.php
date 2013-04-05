<?php

class Admin_TranslationController extends Zend_Controller_Action
{

    public function init()
    {
		/* Initialize action controller here */
    }

    public function indexAction()
    {
        // action body
    	$this->view->addlink = $this->view->url ( array (
    			"module" => "admin",
    			"controller" => "translation",
    			"action" => "add"
    	), "default", true );
        $modules = array();
        $iterator = new DirectoryIterator(APPLICATION_PATH . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR);
        foreach ($iterator as $fileinfo) {
        	if($fileinfo->isDir() && $fileinfo->getFilename()!=".." && $fileinfo->getFilename()!= "." && file_exists($fileinfo->getPath(). DIRECTORY_SEPARATOR . $fileinfo->getFilename() . DIRECTORY_SEPARATOR . 'lang')) {
        		$modules[$fileinfo->getFilename()] = ucwords(str_replace("-", " ", $fileinfo->getFilename()));
        	}
        }
        $this->view->modules = $modules;
        $this->view->languages = Standard_Functions::getAllLanguages();
    }

    public function addAction()
    {
    	if ($this->getRequest()->isPost()) {
    		$this->_helper->layout ()->disableLayout ();
    		$this->_helper->viewRenderer->setNoRender ();
    		$request = $this->getRequest();
    		$error = true;
    		$msg = "Unable to save translation";
    		 
    		$module_name = $request->getParam("module_name","");
    		if($module_name!="") {
    			$langPath = "";
    			$languages = Standard_Functions::getAllLanguages();
    			$key = $request->getParam("lang_en","");
    			foreach($languages as $lang) {
	    			if($module_name=="global") {
	    				$langPath = APPLICATION_PATH . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR.$lang["lang"].'.php';
	    			} else {
	    				$langPath = APPLICATION_PATH . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR. $module_name . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $lang["lang"] . '.php';
	    			}
	    			$data = array();
	    			if(file_exists($langPath)) {
	    				$data = @include($langPath);
	    			}
	    			$value = $request->getParam("lang_".$lang["lang"],"");
	    			$key=htmlentities(utf8_decode($key),ENT_QUOTES);
	    			
	    			$data[$key] = ($value=="")? $key : htmlentities(utf8_decode($value),ENT_QUOTES);
	    			$this->serialize($langPath,$data);
    			}
    			$error = false;
    			$msg="";
    		}
    		$response["error"] = $error;
    		$response["message"] = $msg;
    		$jsonResponse = Zend_Json::encode($response);
    		$this->_response->appendBody($jsonResponse);
    		return;
    	}
    	$modules = array();
    	$iterator = new DirectoryIterator(APPLICATION_PATH . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR);
    	foreach ($iterator as $fileinfo) {
    		if($fileinfo->isDir() && $fileinfo->getFilename()!=".." && $fileinfo->getFilename()!= "." && file_exists($fileinfo->getPath(). DIRECTORY_SEPARATOR . $fileinfo->getFilename() . DIRECTORY_SEPARATOR . 'lang')) {
    			$modules[$fileinfo->getFilename()] = ucwords(str_replace("-", " ", $fileinfo->getFilename()));
    		}
    	}
    	$this->view->modules = $modules;
    	$this->view->languages = Standard_Functions::getAllLanguages();
    }
    
    public function saveAction()
    {
    	$this->_helper->layout ()->disableLayout ();
    	$this->_helper->viewRenderer->setNoRender ();
    	$request = $this->getRequest();
    	$error = true;
    	$msg = "Unable to save translation";
    	
    	$module_name = $request->getParam("module_name","");
    	$lang = $request->getParam("lang","");
    	$translations = $request->getParam("translation",null);
    	if($translations!=null) {
    		if($module_name!="" && $lang!="") {
    			$langPath = "";
    			if($module_name=="global") {
    				$langPath = APPLICATION_PATH . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR.$lang.'.php';
    			} else {
    				$langPath = APPLICATION_PATH . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR. $module_name . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $lang . '.php';
    			}
    			if(file_exists($langPath)) {
    				$data = @include($langPath);
    				foreach($translations as $value) {
    					$trans = $request->getParam("translation_".$value,null);
    					$key = $request->getParam("original_".$value,null);
    					$data[htmlentities(utf8_decode($key),ENT_QUOTES)] = htmlentities(utf8_decode($trans),ENT_QUOTES);
    				}
    				
    				$this->serialize($langPath,$data);
    				$error = false;
    				$msg="";
    			}
    		}
    	}
    	$response["error"] = $error;
    	$response["message"] = $msg;
    	$jsonResponse = Zend_Json::encode($response);
    	$this->_response->appendBody($jsonResponse);
    }
    
    protected  function serialize($path,$data)
    {
    	$content = "<?php\nreturn array(";
    	foreach($data as $key=>$value) {
    		$content .= "\n\t\t'".$key."'=>'".$value."',";
    	}
    	$content .= "\n);";
    	file_put_contents($path, sprintf($content));
    }
    
    public function gridAction()
    {
    	$this->_helper->layout ()->disableLayout ();
    	$this->_helper->viewRenderer->setNoRender ();
    	$request = $this->getRequest();
    	
    	$module_name = $request->getParam("search_module_name","");
    	$lang = $request->getParam("search_language","");
    	$gridData = array();
    	$total = 0;
    	$totalFiltered = 0;
    	if($module_name!="" && $lang!="") {
    		$langPath = "";
    		if($module_name=="global") {
    			$langPath = APPLICATION_PATH . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR.$lang.'.php';
    		} else {
    			$langPath = APPLICATION_PATH . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR. $module_name . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . $lang . '.php';
    		}
    		if(file_exists($langPath)) {
	    		$translations = @include($langPath);
	    		$index=1;
	    		foreach ($translations as $key => $value) {
	    			$value = "<input type='text' style='width:90%' name='translation_".$index."' value='".$value."' />";
	    			$value .= "<input type='hidden' name='original_".$index."' value='".$key."' />";
	    			$value .= "<input type='hidden' name='translation[]' value='".$index."' />";
	    			$gridData[] = array($key,$value);
	    			$index++;
	    		}
	    		$total = count($gridData);
	    		$totalFiltered = count($gridData);
    		}
    	}
    	$finalGridData ["sEcho"] = $request->getParam ( "sEcho", 1 );
    	$finalGridData ["iTotalRecords"] = $total;
    	$finalGridData ["iTotalDisplayRecords"] = $totalFiltered;
    	$finalGridData ["aaData"] = array_slice($gridData,$request->getParam("iDisplayStart",0),$request->getParam("iDisplayLength",10));
    	
    	$jsonGrid = Zend_Json::encode ( $finalGridData );
    	$this->_response->appendBody ( $jsonGrid );
    }
}