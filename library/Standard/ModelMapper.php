<?php
abstract class Standard_ModelMapper implements Standard_MapperStandards {
	protected $_dbTableClass = "";
	protected $_modelClass = "";
	protected $_dbTable;
	final public function __construct() {
		// Set DbTable Class
		if ($this->_dbTableClass == "" || $this->_dbTableClass === null)
			$this->_dbTableClass = str_ireplace ( "_Mapper_", "_DbTable_", get_class ( $this ) );
			
			// Set Model Class
		if ($this->_modelClass == "" || $this->_modelClass === null)
			$this->_modelClass = str_ireplace ( "_Mapper", "", get_class ( $this ) );
	}
	final public function setDbTable($dbTable) {
		if (is_string ( $dbTable )) {
			$dbTable = new $dbTable ();
		}
		if (! $dbTable instanceof Zend_Db_Table_Abstract) {
			throw new Exception ( 'Invalid table data gateway provided for ContentsetMapper classs' );
		}
		$this->_dbTable = $dbTable;
		return $this;
	}
	public function getDbTable() {
		if ($this->_dbTable == null || $this->_dbTable === null) {
			$this->setDbTable ( $this->_dbTableClass );
		}
		return $this->_dbTable;
	}
	final public function __call($method, $arguments) {
		$db = $this->getDbTable ();
		$methods = get_class_methods ( get_class ( $db ) );
		if (in_array ( $method, $methods )) {
			return call_user_func_array ( array (
					$db,
					$method 
			), $arguments );
		} else {
			throw new Zend_Exception ( 'Invalid Method: ' . $method . '()' );
		}
	}
	
	/**
	 * Overriding the default find functionality to return models
	 *
	 * @return Ambigous <boolean, unknown, multitype:unknown >
	 */
	public function find() {
		
		// Return false if not output is found
		$models = false;
		
		// Call the original Find function of the Zend_DbTable
		$args = func_get_args ();
		$originalFindOutput = call_user_func_array ( array (
				$this->getDbTable (),
				__FUNCTION__ 
		), $args );
		
		$originalFindOutputArray = $originalFindOutput->toArray ();
		
		if (is_array ( current ( $originalFindOutputArray ) ) && isset ( $originalFindOutputArray [0] ) && ! empty ( $originalFindOutputArray [0] )) {
			$models = array ();
			// For more than one result for single primary key
			foreach ( $originalFindOutputArray as $findOutput ) {
				$model = new $this->_modelClass ( $findOutput );
				$models [] = $model;
			}
			if (count ( $originalFindOutputArray ) == 1) {
				$models = $models [0];
			}
		}
		return $models;
	}
	
	/**
	 * Fetches all Models.
	 *
	 * Honors the Zend_Db_Adapter fetch mode.
	 *
	 * @param string|array|Zend_Db_Table_Select $where
	 *        	OPTIONAL An SQL WHERE clause or Zend_Db_Table_Select object.
	 * @param string|array $order
	 *        	OPTIONAL An SQL ORDER clause.
	 * @param int $count
	 *        	OPTIONAL An SQL LIMIT count.
	 * @param int $offset
	 *        	OPTIONAL An SQL LIMIT offset.
	 * @return Ambigous <boolean, unknown, multitype:unknown >
	 */
	public function fetchAll($where = null, $order = null, $count = null, $offset = null) {
		$models = false;
		$originalFetchAllOutput = $this->getDbTable ()->fetchAll ( $where, $order, $count, $offset );
		
		$originalFetchAllOutputArray = $originalFetchAllOutput->toArray ();
		
		if (is_array ( current ( $originalFetchAllOutputArray ) ) && isset ( $originalFetchAllOutputArray [0] ) && ! empty ( $originalFetchAllOutputArray [0] )) {
			
			$models = array ();
			
			// For more than one result for single primary key
			foreach ( $originalFetchAllOutputArray as $findOutput ) {
				$model = new $this->_modelClass ( $findOutput );
				$models [] = $model;
			}
		}
		return $models;
	}
	public function save(Standard_Model $model) {
		// public function save(array $model) {
		if (! ($model instanceof $this->_modelClass)) {
			$classProvided = get_class ( $model );
			throw new Zend_Exception ( "Wrong modelClass [{$classProvided}] given to mapper of model [{$this->_modelClass}]" );
		}
		
		// Get PrimaryKey by conventions
		$primaryKey = $this->_getPrimaryKeyName ();
		
		$modelData = $model->toArray ();
		if ($modelData [$primaryKey] != null && $modelData [$primaryKey] !== null) {
			// Update the existing Record
			$updatedVars = $model->getUpdatedVars ();
			unset ( $updatedVars [$primaryKey] );
			$this->getDbTable ()->update ( $updatedVars, " " . $primaryKey . " = " . $modelData [$primaryKey] );
			return $this->find ( $modelData [$primaryKey] );
		} else {
			// Insert the new record
			unset ( $modelData [$primaryKey] );
			$insert_id = $this->getDbTable ()->insert ( $modelData );
			$model->set ( $primaryKey, $insert_id );
		}
		// Reset the updated vars
		$model->setUpdatedVars ( array () );
		return $model;
	}
	
	/**
	 * Deletes existing rows.
	 *
	 * @param array|string $where
	 *        	SQL WHERE clause(s).
	 * @return int The number of rows deleted.
	 */
	public function delete($where) {
		return $this->getDbTable ()->delete ( $where );
	}
	/**
	 * Count according to the criteria specified
	 *
	 * @param string $filter        	
	 * @return number
	 */
	public function countAll($filter = null) {
		return $this->getDbTable ()->fetchAll ( $filter )->count ();
	}
	
	/**
	 * Create variable according to the conventions
	 *
	 * @param string $method        	
	 * @return string
	 */
	private function _createVariable($method) {
		$string = "";
		for($i = 0; $i < strlen ( $method ); $i ++) {
			if ($method [$i] == strtoupper ( $method [$i] )) {
				$string .= "_" . strtolower ( $method [$i] );
			} else {
				$string .= $method [$i];
			}
		}
		return $string;
	}
	
	/**
	 * Get the computed primary key name according to convetion
	 *
	 * @return string
	 */
	private function _getPrimaryKeyName() {
		$nameArray = explode ( "_", $this->_modelClass );
		$name = array_pop ( $nameArray );
		$name = $this->_createVariable ( $name );
		$name = substr ( $name, 1 );
		$primaryKey = $name . "_id";
		
		unset ( $nameArray );
		unset ( $name );
		
		return $primaryKey;
	}
	
	/**
	 * Get Grid Data-Table List
	 *
	 * @param array $columns        	
	 * @param string $where        	
	 * @return boolean multitype:multitype:string
	 */
	public function getDataTableList(array $options = array(), $where = null) {
		
		// Get the current request object
		$request = Zend_Controller_Front::getInstance ()->getRequest ();
		
		// Calculate Columns required
		$columns = $request->getParam ( 'sColumns' );
		$columns = explode ( ",", $columns );
		$columns = array_filter ( $columns, function ($value) {
			return ($value != "");
		} );
		
		// Applying Sorting
		$order = "";
		$iSortingCols = $request->getParam ( 'iSortingCols' );
		for($i = 0; $i < intval ( $iSortingCols ); $i ++) {
			if ($request->getParam ( "bSortable_" . $request->getParam ( 'iSortCol_' . $i ), false )) {
				$order .= $columns [$request->getParam ( 'iSortCol_' . $i )] . " " . $request->getParam ( 'sSortDir_' . $i ) . ", ";
			}
		}
		// Change sOrder back to null
		$order = $order == "" ? null : $order;
		
		// Extract Searching Fields
		$allParams = $request->getParams ();
		$searchParams = array_filter ( $allParams, function ($key) use(&$allParams) {
			if (strpos ( key ( $allParams ), "search_" ) !== false && $allParams [key ( $allParams )] != "") {
				next ( $allParams );
				return true;
			} else {
				next ( $allParams );
				return false;
			}
		} );
		
		// Check for replace columns bbefore setting data to data grid
		$replaceColumns = false;
		if (isset ( $options ["column"] ) && isset ( $options ["column"] ["replace"] )) {
			$replaceColumns = array_keys ( $options ["column"] ["replace"] );
		}
		
		// Searching
		if (! empty ( $searchParams )) {
			if ($where == "") {
				$where .= " (";
			} else {
				$where .= " AND ";
			}
			
			foreach ( $searchParams as $searchColumn => $searchValue ) {
				$searchColumn = substr ( $searchColumn, strlen ( "search_" ) );
				
				// Creating custom search for replacement properties
				if ($replaceColumns && in_array ( $searchColumn, $replaceColumns )) {
					$filterReplaceColumns = $options ['column'] ['replace'] [$searchColumn];
					$searchArray = array_filter ( $filterReplaceColumns, function ($data) use(&$filterReplaceColumns, $searchValue) {
						if (strpos ( strtolower ( current ( $filterReplaceColumns ) ), strtolower ( $searchValue ) ) !== false) {
							next ( $filterReplaceColumns );
							return true;
						}
						next ( $filterReplaceColumns );
						return false;
					} );
					if (! empty ( $searchArray )) {
						$where .= "( ( ";
						foreach ( $searchArray as $key => $value ) {
							$where .= $searchColumn . " LIKE '%" . $key . "%' OR ";
						}
						$where = substr_replace ( $where, "", - 3 );
						$where .= " ) OR " . $searchColumn . " LIKE '%" . $searchValue . "%' ) AND ";
					} else {
						$where .= $searchColumn . " LIKE '%" . $searchValue . "%' AND ";
					}
				} else {
					$where .= $searchColumn . " LIKE '%" . $searchValue . "%' AND ";
				}
			}
			
			$where = substr_replace ( $where, "", - 4 );
			$where .= ") ";
		}
		
		// Get the data from database
		// Set Offset and Limit/Count
		$count = $request->getParam ( "iDisplayLength", 10 );
		$offset = $request->getParam ( "iDisplayStart", 0 );
		
		$models = $this->fetchAll ( $where, $order, $count, $offset );
		$gridData = array ();
		if ($models) {
			
			foreach ( $models as $model ) {
				
				$record = array ();
				foreach ( $columns as $column ) {
					if (isset ( $options ["column"] ) && isset ( $options ["column"] ["id"] ) && in_array ( $column, $options ["column"] ["id"] )) {
						$record [] = $model->toArray ();
					} else if (isset ( $options ["column"] ) && isset ( $options ["column"] ["ignore"] ) && in_array ( $column, $options ["column"] ["ignore"] )) {
						$record [] = "";
					} else {
						$columnValue = $model->get ( $column );
						if ($replaceColumns && in_array ( $column, $replaceColumns ) && isset ( $options ["column"] ["replace"] [$column] [$columnValue] )) {
							$record [] = $options ["column"] ["replace"] [$column] [$columnValue];
						} else {
							$record [] = $columnValue;
						}
					}
				}
				$gridData [] = $record;
			}
		}
		$finalGridData ["sEcho"] = $request->getParam ( "sEcho", 1 );
		
		$finalGridData ["iTotalRecords"] = $this->countAll ();
		$finalGridData ["iTotalDisplayRecords"] = $this->countAll ( $where );
		$finalGridData ["aaData"] = $gridData;
		
		return $finalGridData;
	}
	
	/**
	 * Get Grid Data
	 *
	 * @param array $columns        	
	 * @param string $where        	
	 * @return boolean multitype:multitype:string
	 */
	public function getGridData(array $options = array(), $where = null, $select = null) {
		
		// Get the current request object
		$request = Zend_Controller_Front::getInstance ()->getRequest ();
		// Calculate Columns required
		$columns = $request->getParam ( 'sColumns' );
		$columns = explode ( ",", $columns );
		$columns = array_filter ( $columns, function ($value) {
			return ($value != "");
		} );
		
		// Applying Sorting
		$order = "";
		$iSortingCols = $request->getParam ( 'iSortingCols' );
		for($i = 0; $i < intval ( $iSortingCols ); $i ++) {
			if ($request->getParam ( "bSortable_" . $request->getParam ( 'iSortCol_' . $i ), false )) {
				$order .= $columns [$request->getParam ( 'iSortCol_' . $i )] . " " . $request->getParam ( 'sSortDir_' . $i ) . ", ";
			}
		}
		// Change sOrder back to null
		$order = $order == "" ? null : $order;
		
		// Extract Searching Fields
		$allParams = $request->getParams ();
		$searchParams = array_filter ( $allParams, function ($key) use(&$allParams) {
			if (strpos ( key ( $allParams ), "search_" ) !== false && $allParams [key ( $allParams )] != "") {
				$returnValue = true; 
				if(is_array($allParams [key ( $allParams )]) ){
					$searchArrayTemp = $allParams [key ( $allParams )];
					foreach($searchArrayTemp as $searchTemp){
						if($searchTemp == ""){
							$returnValue = false;
						}
					}
				}
				next ( $allParams );
				return $returnValue;
			} else {
				next ( $allParams );
				return false;
			}
		} );
		
		// Check for replace columns bbefore setting data to data grid
		$replaceColumns = false;
		if (isset ( $options ["column"] ) && isset ( $options ["column"] ["replace"] )) {
			$replaceColumns = array_keys ( $options ["column"] ["replace"] );
		}
		// Check for replace Search type before setting data to data grid
		$searchTypeColumns = false;
		if (isset ( $options ["search_type"] )) {
			$searchTypeColumns = array_keys ( $options ["search_type"] );

		}
		// var_dump($replaceColumns);
		// Searching
		if (! empty ( $searchParams )) {
			if ($where == "") {
				$where .= " (";
			} else {
				$where .= " AND (";
			}
			// Before Search Params
			foreach ( $searchParams as $searchColumn => $searchValue ) {
				if (is_array ( $searchValue )) {
					foreach ( $searchValue as $key => $value ) {
						//if($value != ""){
							$searchParams [$searchColumn . "." . $key] = $value;
						//}
					}
					unset ( $searchParams [$searchColumn] );
				}
			}
			foreach ( $searchParams as $searchColumn => $searchValue ) {
				$searchColumn = substr ( $searchColumn, strlen ( "search_" ) );
				
				// Creating custom search for replacement properties
				if ($replaceColumns && in_array ( $searchColumn, $replaceColumns )) {
					$filterReplaceColumns = $options ['column'] ['replace'] [$searchColumn];
					$searchArray = array_filter ( $filterReplaceColumns, function ($data) use(&$filterReplaceColumns, $searchValue) {
						if (strpos ( strtolower ( current ( $filterReplaceColumns ) ), strtolower ( $searchValue ) ) !== false) {
							next ( $filterReplaceColumns );
							return true;
						}
						next ( $filterReplaceColumns );
						return false;
					} );					
					if (! empty ( $searchArray )) {
						$where .= "( ( ";						
						foreach ( $searchArray as $key => $value ) {							
							if (is_array($searchTypeColumns) && in_array ( $searchColumn, $searchTypeColumns )) {
								if ($options ['search_type'] [$searchColumn] == "=") {
									$where .= $searchColumn . " = '" . $searchValue . "' AND ";
								} else if ($options ['search_type'] [$searchColumn] == "LIKE") {
									$where .= $searchColumn . " LIKE '%" . $searchValue . "%' AND ";
								}
							} else {
								$where .= $searchColumn . " LIKE '%" . $searchValue . "%' AND ";
							}
						}
						$where = substr_replace ( $where, "", - 3 );
						$where .= " ) OR " . $searchColumn . " LIKE '%" . $searchValue . "%' ) AND ";
					} else {
						$where .= $searchColumn . " LIKE '%" . $searchValue . "%' AND ";
					}
				} else {	
					/**
					 *@todo: need to fix by TB urf (Tirth Bodawala)
					 */
					if (is_array($searchTypeColumns) && in_array ( $searchColumn, $searchTypeColumns )) {
						if ($options ['search_type'] [$searchColumn] == "=") {
							$where .= $searchColumn . " = '" . $searchValue . "' AND ";
						} else if ($options ['search_type'] [$searchColumn] == "LIKE") {
							$where .= $searchColumn . " LIKE '%" . $searchValue . "%' AND ";
						}
					} else {
						$where .= $searchColumn . " LIKE '%" . $searchValue . "%' AND ";
					}
				}
			}
			
			$where = substr_replace ( $where, "", - 4 );
			$where .= ") ";
		}
		$where = $where == "" ? "1=1" : $where;
		// Get the data from database
		// Set Offset and Limit/Count
		$count = $request->getParam ( "iDisplayLength", 10 );
		$offset = $request->getParam ( "iDisplayStart", 0 );
		//echo $where;
		//die;
		// $models = $this->fetchAll ( $where, $order , $count , $offset);
		$total = 0;
		$totalFiltered = 0;
		if ($select === null) {
			$models = $this->fetchAll ( $where, $order, $count, $offset );
			$total = $this->countAll ();
			$totalFiltered = $this->countAll ( $where );
		} else {
			$total = $this->countAll ();
			
			$totalFiltered = $this->getDbTable ()->fetchAll ( $select->where ( $where ) )->count ();

			
			$select = $select->where ( $where )->order ( $order )->limit ( $count, $offset );

			$models = $this->getDbTable ()->fetchAll ( $select )->toArray ();
		}
		
		$gridData = array ();
		if ($models) {
			foreach ( $models as $model ) {
				$record = array ();
				$model = $model instanceof Standard_Model ? $model->toArray () : $model;
				foreach ( $columns as $column ) {
					if (isset ( $options ["column"] ) && isset ( $options ["column"] ["id"] ) && in_array ( $column, $options ["column"] ["id"] )) {
						$record [] = $model;
					} else if (isset ( $options ["column"] ) && isset ( $options ["column"] ["ignore"] ) && in_array ( $column, $options ["column"] ["ignore"] )) {
						$record [] = "";
					} else {
						$columnValue = $model [$column];
						
						if ($replaceColumns && in_array ( $column, $replaceColumns ) && isset ( $options ["column"] ["replace"] [$column] [$columnValue] )) {
							$record [] = $options ["column"] ["replace"] [$column] [$columnValue];
						} else {
							$record [] = $columnValue;
						}
					}
				}
				$gridData [] = $record;
			}
		}
		
		$finalGridData ["sEcho"] = $request->getParam ( "sEcho", 1 );
		
		$finalGridData ["iTotalRecords"] = $total;
		$finalGridData ["iTotalDisplayRecords"] = $totalFiltered;
		$finalGridData ["aaData"] = $gridData;
		//$finalGridData["debug"]= $select->__toString();
		//$finalGridData["debug1"]= $tmpSql;

		return $finalGridData;
	}
}