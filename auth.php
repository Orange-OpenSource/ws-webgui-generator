<?php
session_start();
include_once (__DIR__) .'/lib/yaml/sfYaml.php';
$conf = sfYaml::load((__DIR__) .'/conf/conf.yml');
if(!empty($_GET['logout'])){
	session_unset();
}
$textError = null;
if(!empty($_POST['service'])){
	$_SESSION['service'] = $_POST['service'];
	if(!is_file((__DIR__).'/apiRemote/'. $_SESSION['service'] .'.php')){
		$textError .= 'Service does not exist<br/>';
	}else{
		include_once((__DIR__).'/apiRemote/'. $_SESSION['service'] .'.php');
	}
}
if(!empty($_POST['login']) && !empty($_POST['password']) && !empty($conf['config']['loginNeeded'])){
	if(empty($textError)){
		$reflection = new ReflectionClass($_SESSION['service']);
		$_SESSION['login'] = $_POST['login'];
		$_SESSION['password'] = $_POST['password'];
		$methods = $reflection->getMethods();
		foreach ($methods as $key => $value) {
			try{
				$reflectionMethod = new ReflectionClass($value->getName());
				$props = $reflectionMethod->getProperties();
				if(count($props)==0){
					$tester = $value->getName();
					break;
				}
			}catch(Exception $e){}
			
		}
		$objet = $reflection->newInstance(array("login"=> $_SESSION['login'], "password" => $_SESSION['password'], "trace"=>1, "exceptions"=>0));
		$testerObjet = new ReflectionClass($tester);
		$testerObjet = $testerObjet->newInstance();
		$request = $objet->$tester($testerObjet);
		if(($request instanceof soapFault) && stristr($conf['config']['notLoggedErrorText'],$request->getMessage())){
			$textError .= 'Login or password incorrect<br/>';
			unset($_SESSION);
		} 
	}
}
