<?php
require_once __DIR__ .'/FunctionValueCreator.php';

$splitOrders = explode('.', $order);
$order = $splitOrders[0];
unset($splitOrders[0]);
$service = $_SESSION['service'];
require_once __DIR__ .'/../apiRemote/'. $service .'.php';
$soap = new $service(array("login"=> $_SESSION['login'], "password" => $_SESSION['password'], "trace"=>1, "exceptions"=>0));
$objet = new $order();
$return = $soap->$order($objet);
$reflectionClass = new reflectionClass($return);

$properties = $reflectionClass->getProperties(ReflectionProperty::IS_PUBLIC);
$propertie = $properties[0]->getName();

echo "\t\t".'<option value=""></option>' ."\n";
if(is_object($return->$propertie)){
	$return->$propertie = array($return->$propertie);
}
var_dump($return->$propertie);
foreach ($return->$propertie as $value) {
	$value = valueCreator($value, $splitOrders, 1);
	echo "\t\t".'<option value="'. $value .'">'. $value .'</option>' ."\n";
}