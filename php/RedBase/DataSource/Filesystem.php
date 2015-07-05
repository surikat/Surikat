<?php
namespace RedBase\DataSource;
use RedBase\DataSource;
use RedBase\RedBase;
class Filesystem extends DataSource{
	private $directory;
	function construct(array $config=[]){
		if(isset($config[0]))
			$this->directory = rtrim($config[0],'/');
		else
			$this->directory = isset($config['directory'])?rtrim($config['directory'],'/'):'.';
	}
	function getDirectory(){
		return $this->directory;
	}
	function readId($type,$id,$primaryKey='id',$uniqTextKey='uniq'){
		return file_exists($this->directory.'/'.$type.'/'.$id)?$id:false;
	}
	function createRow($type,$obj,$primaryKey='id'){
		
	}
	function readRow($type,$id,$primaryKey='id'){
		
	}
	function updateRow($type,$obj,$id=null,$primaryKey='id'){
		
	}
	function deleteRow($type,$id,$primaryKey='id'){
		
	}
	function debug($enable=true){
		
	}
}