<?php
session_start();
if(!is_dir(__DIR__ .'/caller')){
	mkdir(__DIR__ .'/caller');
}
if(!is_dir(__DIR__ .'/view')){
	mkdir(__DIR__ .'/view');
}
if(!is_dir(__DIR__ .'/apiRemote')){
	mkdir(__DIR__ .'/apiRemote');
}
include_once (__DIR__) .'/lib/yaml/sfYaml.php';
$conf = sfYaml::load((__DIR__) .'/conf/conf.yml');
function generateService($myWSDLlocation){
	require_once (__DIR__).'/lib/wsdlInterpreter/WSDLInterpreter.php';
	$wsdlInterpreter = new WSDLInterpreter($myWSDLlocation);
	$serviceNames = $wsdlInterpreter->savePHP((__DIR__).'/apiRemote/');
	require_once (__DIR__).'/lib/generatorUI/GeneratorUi.php';
	foreach ($serviceNames as $key => $value) {
		$generatorUi = new GeneratorUi($value);
		$generatorUi->generateUi();
	}
}
if(count($conf['services'])>0){
	foreach ($conf['services'] as $key => $value) {
		if(!is_file((__DIR__).'/apiRemote/'. $key .'.php')){
			generateService($value['uri']);
		}	
	}
}

if(!empty($_GET['regenerate'])){
	
	generateService($conf['services'][$_SESSION['service']]['uri']);
}



?>