<?php
class Standard_Form extends Zend_Form {
	public function __construct() {
		$this->addElementPrefixPath('Standard_Decorator', APPLICATION_PATH . "/../library/Standard/Decorator", 'decorator');
		$this->addPrefixPath('Standard_Decorator', APPLICATION_PATH . "/../library/Standard/Decorator", 'decorator');
		parent::__construct();
	}
} 