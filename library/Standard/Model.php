<?php
abstract class Standard_Model {
	protected $_class_vars;
	protected $_reflection_properties;
	protected $_mapperClass = "";
	protected $_dbTableClass = "";
	protected $_updated_vars;
	/**
	 * Constructor
	 *
	 * @param array $options        	
	 */
	public function __construct(array $options = null) {
		$this->_setClassVars ();
		
		if (is_array ( $options )) {
			$this->setOptions ( $options );
		}
		
		// Set the Mapper Class
		if ($this->_mapperClass == "" || $this->_mapperClass === null)
			$this->_mapperClass = str_ireplace ( "_Model_", "_Model_Mapper_", get_class ( $this ) );
		
		if ($this->_dbTableClass == "" || $this->_dbTableClass === null)
			$this->_dbTableClass = str_ireplace ( "_Model_", "_Model_DbTable_", get_class ( $this ) );
	}
	
	/**
	 * Uses reflection to set the and get the value of private variables
	 * of the derived class
	 *
	 * @return Instance of current model
	 */
	final private function _setClassVars() {
		$reflection = new ReflectionClass ( $this );
		$vars = $reflection->getProperties ();
		foreach ( $vars as $reflectionProperty ) {
			$reflectionProperty->setAccessible ( true );
			$this->_reflection_properties [$reflectionProperty->getName ()] = $reflectionProperty;
			$this->_class_vars [] = $reflectionProperty->getName ();
		}
		return $this;
	}
	
	/**
	 * Default set value according to convetion
	 *
	 * @param String $name        	
	 * @param Mixed $value        	
	 * @throws Exception
	 */
	final public function __set($name, $value) {
		$method = 'set' . str_replace ( " ", "", ucwords ( str_replace ( "_", " ", $name ) ) );
		if (('mapper' == $name)) {
			throw new Exception ( 'Invalid User property ' . $name );
		}
		$this->$method ( $value );
	}
	
	/**
	 * Get the value of varible
	 *
	 * @param String $name        	
	 * @throws Exception
	 */
	final public function __get($name) {
		$method = 'get' . str_replace ( " ", "", ucwords ( str_replace ( "_", " ", $name ) ) );
		if (('mapper' == $name)) {
			throw new Exception ( 'Invalid User property ' . $name );
		}
		return $this->$method ();
	}
	
	/**
	 * Set the options provided according to the variables
	 *
	 * @param array $options        	
	 * @return Instance of current model
	 */
	final public function setOptions(array $options) {
		$class_vars = array();
		foreach($this->_class_vars as $class_var){
			$class_vars[] = substr($class_var,1);
		}
		foreach ( $options as $key => $value ) {
			if(in_array($key,$class_vars)){
				$method = 'set' . str_replace ( " ", "", ucwords ( str_replace ( "_", " ", $key ) ) );
				$this->$method ( $value );
			}
		}
		return $this;
	}
	
	/**
	 * Create setters and getters by default
	 *
	 * @param String $method        	
	 * @param Mixed $arguments        	
	 * @throws Zend_Exception
	 * @return Instance of current model
	 */
	final public function __call($method, $arguments) {
		// Automatic Set and Get Methods
		$type = substr ( $method, 0, 3 );
		$classMethod = substr ( $method, 3 );
		$variableName = $this->_createVariable ( $classMethod );
		
		if (in_array ( $variableName, $this->_class_vars )) {
			if ($type == "get") {
				return $this->_reflection_properties [$variableName]->getValue ( $this );
			} elseif ($type == "set") {
				if (isset ( $arguments [0] )) {
					$this->_updated_vars[substr($variableName,1)] = $arguments [0];
					$this->_reflection_properties [$variableName]->setValue ( $this, $arguments [0] );
					return $this;
				} else {
					$this->_reflection_properties [$variableName]->setValue ( $this, "" );
					return $this;
				}
			} else {
				throw new Zend_Exception ( 'Invalid Method: ' . $method . '()' );
			}
		} else {
			throw new Zend_Exception ( 'Invalid Property: ' . $variableName );
		}
	}
	/**
	 * Create variable according to the conventions
	 *
	 * @param string $method        	
	 * @return string
	 */
	private function _createVariable($method) {
		$string = "";
		/*for($i = 0; $i < strlen ( $method ); $i ++) {
			if ($method [$i] == strtoupper ( $method [$i] )) {
				$string .= "_" . strtolower ( $method [$i] );
			} else {
				$string .= $method [$i];
			}
		}
		return $string;*/
		return strtolower (preg_replace('/[A-Z]|[0-9]/', "_$0", $method));
	}
	
	/**
	 * Returns the value of all declared variables in array form
	 *
	 * @return multitype:NULL
	 */
	final public function toArray() {
		$reflection_properties = $this->_reflection_properties;
		$modelArray = array ();
		foreach ( $reflection_properties as $reflection_property ) {
			$modelArray [substr ( $reflection_property->name, 1 )] = $reflection_property->getValue ( $this );
		}
		
		if (isset ( $modelArray ['class_vars'] ))
			unset ( $modelArray ['class_vars'] );
		
		if (isset ( $modelArray ['reflection_properties'] ))
			unset ( $modelArray ['reflection_properties'] );
		
		if (isset($modelArray ['mapperClass'])){
			unset ( $modelArray ['mapperClass'] );
		}
		
		if (isset($modelArray ['dbTableClass'])){
			unset ( $modelArray ['dbTableClass'] );
		}
		
		if (isset($modelArray ['updated_vars'])){
			unset ( $modelArray ['updated_vars'] );
		}
		
		return $modelArray;
	}
	
	/**
	 * Save the model with the help of mapper save method
	 */
	public function save() {
		$mapper = new $this->_mapperClass();
		return $mapper->save($this);
	}
	
	public function delete(){
		$mapper = new $this->_mapperClass();
		$primaryKeyName = $this->_getPrimaryKeyName();
		if($this->get($primaryKeyName)!= "" || $this->get($primaryKeyName)!== null){
			return $mapper->delete($primaryKeyName." = ".$this->get($primaryKeyName));
		}
		else
			return false;
	}
	
	/**
	 * Populate the model on basis of Primary key
	 * 
	 * @param number $id
	 * @throws Zend_Exception
	 */
	public function populate($id = null){
		$primaryKeyName = $this->_getPrimaryKeyName();
		if($id==null && ( $this->get($primaryKeyName)=="" || $this->get($primaryKeyName)=== null)){
			throw new Zend_Exception("Invalid primary key provided to poulate data for the model");
		} else {
			$id = $id==null?$this->get($primaryKeyName):$id;
			$mapper = new $this->_mapperClass();
			if(($foundModel=$mapper->find($id)) == true){
				$this->setOptions($foundModel->toArray());
			} else {
				throw new Zend_Exception("Record for ID: {$id} not found");
			}
		}
	}
	
	/**
	 * While updating we keep track of variables that have changed values and
	 * thus return the list of variables with variable as array-keys and values 
	 * as array-values accordingly 
	 * 
	 * @return Mixed
	 */
	public function getUpdatedVars(){
		return $this->_updated_vars;
	}
	
	/**
	 * After saving the data of the models the updated_vars needs to be reset
	 * 
	 * @param array $_updated_vars
	 * @return Standard_Model
	 */
	public function setUpdatedVars(array $_updated_vars = array()){
		$this->_updated_vars = $_updated_vars;
		return $this;
	}
	
	/**
	 * Get the value of variable asked for 
	 * 
	 * @param string $var
	 */
	public function get($var){
		return $this->{"_".$var};
	}
	
	/**
	 * Set the value of variable
	 * 
	 * @param string $var
	 * @param mixed $value
	 * @return Instance of this model
	 */
	public function set($var,$value){
		$this->{"_".$var} = $value;
		return $this;
	}
	
	/**
	 * Get the computed primary key name according to convetion
	 *
	 * @return string
	 */
	private function _getPrimaryKeyName() {
		$nameArray = explode ( "_", get_class($this));
		$name = array_pop ( $nameArray );
		$name = $this->_createVariable ( $name );
		$name = substr ( $name, 1 );
		$primaryKey = $name . "_id";
	
		unset ( $nameArray );
		unset ( $name );
	
		return $primaryKey;
	}
}