<?php namespace Templix\MarkupHtml5;
class Link extends \Templix\Markup{
	protected $selfClosed = true;#http://www.w3.org/TR/html5/syntax.html#void-elements
	function load(){
		if($this->templix&&$this->templix->devCss&&$this->href&&strpos($this->href,'://')===false&&strpos($this->href,'_t=')===false&&pathinfo($this->href,PATHINFO_EXTENSION)=='css'){
			if(strpos($this->href,'?')===false)
				$this->href .= '?';
			else
				$this->href .= '&';
			$this->href .= '_t='.time();
		}
	}
}