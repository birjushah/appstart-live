<?php 

class Admin_Plugin_Layout extends Zend_Controller_Plugin_Abstract {
   
    /**
     * Array of layout paths associating modules with layouts
     
    protected $_moduleLayouts;
   */
    /**
     * Registers a module layout.
     * This layout will be rendered when the specified module is called.
     * If there is no layout registered for the current module, the default layout as specified
     * in Zend_Layout will be rendered
     *
     * @param String $module        The name of the module
     * @param String $layoutPath    The path to the layout
     * @param String $layout        The name of the layout to render
     
    public function registerModuleLayout($module, $layoutPath, $layout=null){
        $this->_moduleLayouts[$module] = array(
            'layoutPath' => $layoutPath,
            'layout' => $layout
        );
    }*/
   
    public function preDispatch(Zend_Controller_Request_Abstract $request) {
    	if($request->getControllerName() == "login" || $request->getControllerName() == "forgot" || $request->getControllerName() == "error")
        {
        	$layout = Zend_Layout::getMvcInstance();
            $layout->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/");
            $layout->setLayout("admin_login");
        }
        else if($request->getModuleName()=="admin")
        {
            $layout = Zend_Layout::getMvcInstance();
            $layout->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/");
            $layout->setLayout("admin");
        }
        else
        {
        	$layout = Zend_Layout::getMvcInstance();
        	$layout->setLayoutPath(APPLICATION_PATH . "/layouts/scripts/");
        	$layout->setLayout("layout");
        }
    }
}

?>