<?php
/**
* 
*/
class SoapView
{
	private $value;
	const OPERATION_SUCCESS = "Operation successful.";
	function __construct($value)
	{
		$this->value = $value;
	}
	public function getMessage($value=null, $jump=false){
		if(empty($value) && !$jump){
			$value = $this->value;
		}
		if($value instanceof SoapFault){
			return $this->getMessageSoapFault($value);
		}elseif(is_array($value)){
			return $this->getMessageArray($value);
		}elseif(is_object($value)){
			return $this->getMessageClass($value);
		}else if(empty($value)){
			return '<span style="font-weight: bold;">Nothing to show.</span>';
		}else{
			return $value;
		}
	}
	private function getMessageArray($value){
		$text = null;
		$text .= "<br/><ul>\n";
		foreach ($value as $key => $valuetab) {
			$text .= "\t<li>";
			if(!is_numeric($key)){
				$text .= '<span style="font-weight:bold;">'.$key ."</span>: ";
			}
			$text .= $this->getMessage($valuetab, true) ."</li>\n";
		}
		$text .= "</ul>\n";
		return $text;
	}
	private function getMessageClass($value){
		$class = new ReflectionClass($value);
		$classProperties = $class->getProperties();

		if($value instanceof stdClass){
			return $this->getMessage((array)$value, true);
		}
		if(empty($classProperties)){
			return SoapView::OPERATION_SUCCESS;
		}
		$text = null;
		foreach ($classProperties as $key => $propertie) {
			$propertyName = $propertie->getName();
			$text .= '<span style="font-weight:bold;">'. $this->formatName($propertyName) ."</span>: ";
			$newValue = $value->$propertyName;
			$text .= $this->getMessage($newValue, true) ."<br/>\n";
			
		}
		$text .= '<br/>';
		return $text;
	}
	private function getMessageSoapFault($value){
		return '<span style="color: red;font-weight: bold;">'. $value->getMessage() .'</span>';
	}
	public function __toString(){
		return $this->getMessage();
	}
	private function formatName($string){
       $stringSplit = str_split($string);
       $stringSplitCopy = $stringSplit;
       $capitalize = str_split("ABCDEFGHIJKLMNOPQRSTUVWXYZ");
       $change = 0;
       for($i=0;$i<count($stringSplit);$i++){
           if(in_array($stringSplit[$i], $capitalize)){
               $stringSplitCopy = $this->pushToIndex($stringSplitCopy, $i+$change, " ");
               $change ++;
           }
       }
       $string = null;
       foreach ($stringSplitCopy as $value){
           $string .= $value;
       }
       return ucfirst($string);
   }
   private function pushToIndex($array, $index, $value){
       $text = null;
       for($i=0; $i<count($array);$i++){
           if($i==$index){
               $text .= $value;
           }
           $text .= $array[$i];
       }
       return str_split($text);
   }
}