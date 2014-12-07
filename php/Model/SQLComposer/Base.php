<?php namespace Surikat\Model\SQLComposer;
/* SQLComposerBase - The base class that all query classes should extend.
 * @package SQLComposer
 * @author Shane Smith
 * @enhanced by surikat for unsettings queries fragments and params
 */
use \Surikat\Model\PHPSQL\PHPSQLParser;
use \Surikat\Model\PHPSQL\PHPSQLCreator;
abstract class Base {

	private function _remove_params($clause,$i=null,$params=null){
		if($clause=='columns')
			$clause = 'select';
		if(isset($this->params[$clause])){
			if(!isset($i))
				$i = count($this->params[$clause])-1;
			if(isset($this->params[$clause][$i])&&(!isset($params)||$params==$this->params[$clause][$i])){
				if(isset($this->mysqli_types[$clause]))
					$this->mysqli_types[$clause] = substr($this->mysqli_types[$clause],count($this->params[$clause][$i])*-1);
				unset($this->params[$clause][$i]);
				array_merge($this->params[$clause]); //reorder
				return true;
			}
		}
	}
	function remove_property($k,$v=null,$params=null,$once=null){
		if($params===false){
			$params = null;
			$once = true;
		}
		foreach(array_keys($this->$k) as $i)
			if(!isset($v)||$this->{$k}[$i]==$v){
				$found = $this->_remove_params($k,$i,$params);
				if(!isset($params)||$found)
					unset($this->{$k}[$i]);
				if((isset($params)&&$found)||(!isset($params)&&$once))
					break;
			}
		return $this;
	}
	function unJoin($table=null,$params=null){
		return $this->remove_property('tables',$table,$params);
	}
	function unFrom($table=null,$params=null){
		return $this->remove_property('tables',$table,$params);
	}
	private static $__apiProp = [
		'select'=>'columns',
		'join'=>'tables',
		'from'=>'tables',
	];
	function __get($k){
		if(isset(self::$__apiProp[$k]))
			$k = self::$__apiProp[$k];
		if(property_exists($this,$k))
			return $this->$k;
	}
	
	protected $columns = [];
	protected $tables = [];
	protected $params = [];
	protected $mysqli_types = [ ];
	function add_table($table,  array $params = null, $mysqli_types = "") {
		if(!empty($params)||!in_array($table,$this->tables))
			$this->tables[] = $table;
		$this->_add_params('tables', $params, $mysqli_types);
		return $this;
	}
	function join($table,  array $params = null, $mysqli_types = "") {
		return $this->add_table($table, $params, $mysqli_types);
	}
	function from($table,  array $params = null, $mysqli_types = "") {
		return $this->add_table($table, $params, $mysqli_types);
	}
	protected function _add_params($clause,  array $params = null, $mysqli_types = "") {
		if (isset($params)){
			if (!isset($this->params[$clause]))
				$this->params[$clause] = [];
			if (!empty($mysqli_types))
				$this->mysqli_types[$clause] .= $mysqli_types;
			$this->params[$clause][] = $params;
		}
		return $this;
	}
	protected function _get_params($order) {
		if (!is_array($order))
			$order = func_get_args();
		$params = [ ];
		$mysqli_types = "";
		foreach ($order as $clause) {
			if(empty($this->params[$clause]))
				continue;
			foreach($this->params[$clause] as $p)
				$params = array_merge($params, $p);
			if(isset($this->mysqli_types[$clause]))
				$mysqli_types .= $this->mysqli_types[$clause];
		}
		if(!empty($this->mysqli_types))
			array_unshift($params, $mysqli_types);
		return $params;
	}
	
	static function recomposePrefixed($q,$p){
		
		$parser = new PHPSQLParser($q);
		$creator = new PHPSQLCreator($parser->parsed);
		
		//echo '<pre>';
		//echo htmlentities($q)."\n";
		//echo htmlentities($creator->created)."\n";
		//echo '</pre>';
		//exit;
		//loop to add prefix on tables
		
		return $creator->created;
	}
	
	abstract function getParams();
	function getQuery() {
		$q = $this->render();
		if($this->prefix)
			$q = self::recomposePrefixed($q,$this->prefix);
		return $q;
	}
	abstract function render();
	function debug() {
		return $this->getQuery() . "\n\n" . print_r($this->getParams(), true);
	}
	protected static function _render_bool_expr( array $expression) {
		$str = "";
		$stack = [ ];
		$op = "AND";
		$first = true;
		foreach ($expression as $expr) {
			if (is_array($expr)) {
				if ($expr[0] == '(') {
					array_push($stack, $op);
					if (!$first)
						$str .= " " . $op;
					if ($expr[1] == "NOT") {
						$str .= " NOT";
					} else {
						$str .= " (";
						$op = $expr[1];
					}
					$first = true;
					continue;
				}
				elseif ($expr[0] == ')') {
					$op = array_pop($stack);
					$str .= " )";
				}
			}
			else {
				if (!$first)
					$str .= " " . $op;
				$str .= " (" . $expr . ")";
			}
			$first = false;
		}
		$str .= str_repeat(" )", count($stack));
		return $str;
	}
	protected $prefix;
	function setPrefix($prefix){
		$this->prefix = $prefix;
	}
}