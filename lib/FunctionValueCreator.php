<?php 
function valueCreator($value, $parts, $index){
	var_dump($parts);
	if(is_object($value)){

		$order = $parts[$index];
		if(count($parts) == 0){
			return $value->$order;
		}
		unset($parts[$index]);
		return valueCreator($value->$order, $parts, $index++);
	}
	return $value;
}