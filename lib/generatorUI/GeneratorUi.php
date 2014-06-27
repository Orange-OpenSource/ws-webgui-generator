<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of GeneratorUi
 *
 * @author xpbp8114
 */
require_once (__DIR__) .'/../yaml/sfYaml.php';

class GeneratorUi {
   private $parametersList = array();
   private $methodList = array();
   private $listConf;
   public $nbLoopArrayMethod = 5;
   private $serviceName;
   private $propertiesList;
   private $propertiesUsed;
   /**
     * Array of method allowed for whitelist.yml
     * @var array
     * @access private
     */
    private $yamlWhitelist = array();

   public function __construct($serviceName){
    $this->serviceName = $serviceName;
    require_once (__DIR__) .'/../../apiRemote/'. $this->serviceName .'.php';
    $ref = new ReflectionClass($this->serviceName);
    $this->listConf = sfYaml::load((__DIR__) .'/../../apiRemote/'. $this->serviceName .'.yml');
    $class_methods = $ref->getMethods();
    foreach ($class_methods as $value){
        if(strstr($value->getName(), "_")===false && $value->getName()!='SoapClient'){
            $param = new ReflectionClass($value->getName());
            $this->parametersList[$value->getName()] = $param->getProperties();
            $this->methodList[] = $value->getName();
        }
    }
   }
   public function generateUi(){
       $this->doCaller();
        $this->doViewer();
    }
   public function getSoapMethodAvailable(){
      $newMethodList = array();
      foreach ($this->methodList as $value) {
        $newMethodList[$value] = $this->formatName($value);
      }
       return $newMethodList;
   }
   private function doCaller(){
       foreach($this->methodList as $nameMethod){
            $this->propertiesList = null;
            $this->propertiesUsed = null;
            $text= null;
            $params = $this->parametersList[$nameMethod];
            if(count($params)>0){
              $text .= 'session_start();'. "\n";
            }
           $text .= 'require_once (__DIR__) .\'/../../apiRemote/'. $this->serviceName .'.php\';'."\n";
           $text .= 'require_once (__DIR__) .\'/../../lib/SoapView.php\';'."\n";
           $text .= '$soap = new '. $this->serviceName .'(array("login"=> $_SESSION[\'login\'], "password" => $_SESSION[\'password\'], "trace"=>1, "exceptions"=>0));'."\n";
           $params = $this->parametersList[$nameMethod];
           $text .= '$value = json_decode($_POST["data"], true);'."\n";
           $text .= '$objet = new '. $nameMethod .'();'."\n";
           if(count($params)>0){
                foreach ($params as $key => $property) {
                  $this->fillPropertiesList($property);
                }
               foreach ($params as $key => $property){
                    $text .= $this->createPhpVerifForMethod($property);
                }
                foreach ($params as $key => $property){
                    $text .= $this->createPhpForMethod($property);
                }
           }
           $text .= '$message = new SoapView($soap->'. $nameMethod.'($objet));'."\n";
           $text .= 'echo $message;'."\n";
           if(!is_dir((__DIR__) .'/../../caller/'. $this->serviceName)){
            mkdir((__DIR__) .'/../../caller/'. $this->serviceName);
           }
            file_put_contents((__DIR__) .'/../../caller/'. $this->serviceName .'/'. $nameMethod .'.php', "<?php\n\n".$text."\n\n?>");
            $this->yamlWhitelist[$nameMethod] = false;
       }
       ksort($this->yamlWhitelist);
   }
   private function fillPropertiesList($property){
      if(empty($this->propertiesList[$property->getName()])){
        $this->propertiesList[$property->getName()] = 1;
      }else{
        $this->propertiesList[$property->getName()]++;
      }
      $var = $this->getVar($property);
      if($this->isClass($var)){
        $param = new ReflectionClass($var);
        $params = $param->getProperties();
        foreach ($params as $key => $value) {
          $this->fillPropertiesList($value);
        }
      }
   }
   private function createPhpVerifForMethod($property, $text=null){
    /*$var = $this->getVar($property);
    if(!$this->isClass($var) || ($this->isClass($var) && $this->isEnumeration(new ReflectionClass($var)))){
      $text .= 'if(empty($_POST[\''. $property->getName() .'\'])) {'."\n";
      $text .= "\t". 'die("Missing value: '. $property->getName() .'");' ."\n";
      $text .= "}\n";
    }else{
      $param = new ReflectionClass($var);
      $params = $param->getProperties();
      foreach ($params as $key => $value) {
        $text .= $this->createPhpVerifForMethod($value);
      }
    }*/
    return $text;
   }
   private function createPhpForMethod($property, $objectName='$objet', $text=null, $propertyOrig=null, $index=-1, $noTable = false){
    $var = $this->getVar($property);
    if(!$this->isClass($var) || ($this->isClass($var) && $this->isEnumeration(new ReflectionClass($var)))){
      if($index>=0 && !$noTable)
        $text .= $objectName.'['. $index .']->'. $property->getName() .' = $value[\''. $property->getDeclaringClass()->getName() .  ucfirst($property->getName()) . '\']['. $index .'];' ."\n";
      else if ($index>=0 && $noTable)
        $text .= $objectName .'->'. $property->getName() .' = $value[\''. $property->getDeclaringClass()->getName() .  ucfirst($property->getName()) . '\']['. $index .'];' ."\n";
      else
        $text .= $objectName.'->'. $property->getName() .' = $value[\''. $property->getDeclaringClass()->getName() .  ucfirst($property->getName()) .'\'];' ."\n";
    }else{
      $param = new ReflectionClass($var);
      $params = $param->getProperties();
      if(!$this->isArrayProperty($property)){
        $text .= '$'. $var .' = new '. $var .'();'."\n";
        $table = null;
        foreach ($params as $key => $value) {
          if($this->propertiesList[$value->getName()] > 1){
            if(empty($this->propertiesUsed[$value->getName()])){
              $this->propertiesUsed[$value->getName()]=1;
            }else{
              $this->propertiesUsed[$value->getName()]++;
            }
            $text .= $this->createPhpForMethod($value, '$'. $var, null, $property, $this->propertiesUsed[$value->getName()]-1, true);
          }else{
            $text .= $this->createPhpForMethod($value, '$'. $var, null, $property);
          }
          
        }
        
      }else{
        $text .= '$'. $var .' = null;'. "\n";
        for ($i=0; $i < $this->nbLoopArrayMethod; $i++) { 
          $text .= '$'. $var .'[] = new '. $var .'();'."\n";
          foreach ($params as $key => $value) {
            $text .= $this->createPhpForMethod($value, '$'. $var, null, $property, $i);
          }
        }
        $text .='$newArray = null;'."\n";
        $text .='for($i=0;$i<'. $this->nbLoopArrayMethod .';$i++){'."\n\t";
        $p=0;
        $text .=  'if(';
        foreach ($params as $key => $value) {
          if($p!=0){
            $text .= ' || ';
          }
          $text .=  '$'. $var.'[$i]->'. $value->getName() .' != null';
          $p++;
        }

        $text .= '){'."\n\t\t";
        $text .=    '$newArray[] = $'. $var .'[$i];'."\n\t";
        $text .=  '}'."\n";
        $text .='}'."\n";
        $text .= '$'. $var .' = $newArray;'."\n";
        
      }
      $text .= $objectName .'->'. $property->getName() .'=$'. $var .';' ."\n";
    }
    return $text;
   }
   public function getAnnotation($property){
    $s = $property->getDocComment();       
    $s = str_replace('/*', '', $s);
    $s = str_replace('*/', '', $s);
    $s = str_replace('*', '', trim($s));
    $aTags = explode('@', trim($s));
    $aTagsTrim = array();
    foreach ($aTags as $key => $value) {
      if(!empty($value)){
        $value = explode(' ', $value);
        $aTagsTrim[$value[0]] = $value[1];
      }
    }
    return $aTagsTrim;
   }
   public function getValAnnotation($property, $type){
    $annotation = $this->getAnnotation($property);
    $val = $annotation[$type];
    $val = str_replace('[]', '', $val);
    return trim($val);
   }
   public function getVar($property){
    return $this->getValAnnotation($property, 'var');
   }

   public function getReturn($property){
    return $this->getValAnnotation($property, 'return');
   }
   public function isArrayProperty($property, $type='var'){
    $annotation = $this->getAnnotation($property);
    $var = $annotation[$type];
    if(stristr($var, '[]') === FALSE){
      return false;
    }
    return true;
   }
   private function isClass($text){
    try{
      new ReflectionClass($text);
      return true;
    }catch(Exception $e){
      return false;
    }
   }
   private function getConnector($property){
    $connector = $this->listConf[$property->getName()]['connector'];
    if(empty($connector)){
      return null;
    }
    if(in_array($connector, $this->methodList)){
      $reflection = new ReflectionClass($connector);
      try{
        $objet = $reflection->newInstance();
      }catch(Exception $e){
        return array($connector=>$connector);
      }
      return null;
    }

   }
   private function createInput($property, $isTable=false){
    $connector = $this->getConnector($property);
    $value = null;
    if(!empty($connector)){
      $value = ' value="'. current($connector) .'" ';
    }
    $makeArray = null;
    if($this->isArrayProperty($property) || $isTable){
      $makeArray = '[]';
    }
    return "\t".$this->formatName($property->getName()). ' :<br/>'."\n".
    "\t". '<input type="text" name="'. $property->getDeclaringClass()->getName() .  ucfirst($property->getName()) . $makeArray .'"'. $value .'/><br/><br/>'."\n";
   }
   private function createTextarea($property, $isTable=false){
    $connector = $this->listConf[$property->getName()]['connector'];
    $value = null;
    $makeArray = null;
    if($this->isArrayProperty($property) || $isTable){
      $makeArray = '[]';
    }
    return "\t".$this->formatName($property->getName()). ' :<br/>'."\n".
    "\t". '<textarea name="'. $property->getDeclaringClass()->getName() .  ucfirst($property->getName()) . $makeArray .'">'. $value .'</textarea><br/><br/>'."\n";
   }
   private function createSelect($property, $array=array(), $isTable=false){
    $typeForm = $this->listConf[$property->getName()];
    $connector = $this->getConnector($property);

    if(!empty($connector)){
      $array = $connector;
    }
    $text=null;
    $text .= "\t".$this->formatName($property->getName()). ' : '."\n";
    $makeArray = null;
    if($this->isArrayProperty($property) || $isTable){
      $makeArray = '[]';
    }
    $text .= "\t". '<select name="'. $property->getDeclaringClass()->getName() .  ucfirst($property->getName()) . $makeArray . '"/>'."\n";
    if(empty($connector) && !empty($this->listConf[$property->getName()]['connector'])){
      $text .="<?php 
        ". '$order' ." = '". $this->listConf[$property->getName()]['connector'] ."';
        include __DIR__ .'/../../lib/SelectCreator.php';
      ?>";
    }else{
       $text .= "\t\t". '<option value=""></option>' ."\n";
      foreach ($array as $key => $value) {
        if($value == 'false'){
          $key = '0';
        }
        if($value == 'true'){
          $key = '1';
        }
        $text .= "\t\t". '<option value="'. $key .'">'. $value .'</option>' ."\n";
      }
    }
   
    $text .= "\t". '</select><br/><br/>' ."\n";
    return $text;
   }
   private function createTypeForm($property, $isTable=false){
    $typeForm = $this->listConf[$property->getName()];
    switch(strtolower($typeForm['type_form'])){
      case "input":
      return $this->createInput($property, $isTable);
      case "textarea":
      return $this->createTextarea($property, $isTable);
      case "select":
      return $this->createSelect($property, array(), $isTable);
    }

   }
   private function createHtmlForm($property, $text=null, $propertyOrig=null, $isTable=false){
    $var = $this->getVar($property);
    if(!$this->isClass($var) && $var=='enum'){

      $objet = $property->getDeclaringClass()->newInstance();
      $prop = $property->getName();
      $valueEnum = $objet->$prop;
      if(!$this->isEnumeration($property->getDeclaringClass())){
        $text .= $this->createSelect($propertyOrig, $valueEnum, $isTable);
      }else{
        $text .= $this->createSelect($propertyOrig, $valueEnum, $isTable);
      }

    }else if(!$this->isClass($var)){
      $text .= $this->createTypeForm($property, $isTable);
    }else{
      $param = new ReflectionClass($var);
      $params = $param->getProperties();
      if(!$this->isArrayProperty($property)){
        if(!$this->isEnumeration($param)){
          $text .= '<fieldset>'."\n";
          $text .= "\t<legend>". $this->formatName($var) .'</legend>'. "\n";
        }
        if($this->isArrayProperty($param)){
          $isTable = true;
        }else{
            $isTable = false;
          }
        foreach ($params as $key => $value) {
          $text .= $this->createHtmlForm($value, null, $property, $isTable);
        }
        if(!$this->isEnumeration($param)){
          $text .= '</fieldset>'."\n";
        }
      }else{
        for($i=0;$i<$this->nbLoopArrayMethod;$i++){
          if(!$this->isEnumeration($param)){
            $text .= '<fieldset>'."\n";
            $text .= "\t<legend>". $this->formatName($var) .' '. ($i+1) .'</legend>'. "\n";
          }
          if($this->isArrayProperty($param)){
            $isTable = true;
          }else{
            $isTable = false;
          }
          foreach ($params as $key => $value) {
            $text .= $this->createHtmlForm($value, null, $property, $isTable);
          }
          if(!$this->isEnumeration($param)){
            $text .= '</fieldset>'."\n";
          }
        }
      }
      
    }
    return $text;
   }

   private function isEnumeration($class){
    $params = $class->getProperties();
    if(count($params)<=0){
      return false;
    }
    foreach ($params as $key => $value) {
      if($this->getVar($value)!='enum'){
        return false;
      }
    }
    return true;
   }
   private function createJs($property, $text=null, $propertyOrig=null){
    $var = $this->getVar($property);
    if(!$this->isClass($var)){
      if($this->isEnumeration($property->getDeclaringClass())){
        $text .= "\n\t\t\t\t\t\t\t\t\t" . $propertyOrig->getName() .': query(\'#soap'. ucfirst($propertyOrig->getName()) .'\').attr("value")';
      }else{
        $text .= "\n\t\t\t\t\t\t\t\t\t" . $property->getName() .': query(\'#soap'. ucfirst($property->getName()) .'\').attr("value")';
      }
      
    }else{
      $param = new ReflectionClass($var);
      $params = $param->getProperties();
      $i = 0;
      foreach ($params as $key => $value) {
        if($i==0){
          $text .= $this->createJs($value, null, $property);
        }else{
          $text .= ', '. $this->createJs($value, null, $property);
        }
        $i++;
      }
    }
    return $text;
   }
   private function doViewer(){
       foreach($this->methodList as $nameMethod){
           $text = "<?php 
           session_start();
           require_once (__DIR__) .'/../../lib/SoapView.php'; 
           ?>\n";
           $params = $this->parametersList[$nameMethod];
           if(count($params)>0){
               $text .= '<h2>'. $this->formatName($nameMethod) .'</h2>'."\n";
               $text .= '<div class="param" style="position: relative">';
               $text .= '<form method="post" id="soap'. ucfirst($nameMethod) .'">'."\n";
               foreach ($params as $key => $property){
                    $text .= $this->createHtmlForm($property);
                }
                $text .="\t". '<button data-dojo-type="dijit/form/Button" type="button">Send request
            <script type="dojo/on" data-dojo-event="click" data-dojo-args="evt">
                require(["dojo/dom-attr"]);
                require(["dojo/query", "dojo/domReady!", "dojo/_base/xhr", "dojo/dom", "dojo/NodeList-manipulate"], function(query) {
                    require(["dojo/request"], function(request){
                        var formJson;
                        require(["dojo/dom-form"], function(domForm){
                          formJson = domForm.toJson("soap'. ucfirst($nameMethod) .'");
                        });
                        request.post("caller/'. $this->serviceName .'/'. $nameMethod .'.php", {
                            data: {
                              "data": formJson                        
                            }
                        }).then(function(text){
                            if(text == \'<?php echo SoapView::OPERATION_SUCCESS; ?>\'){
                              alert(text);
                            }else{
                              query(\'.answer'. ucfirst($nameMethod) .'\').innerHTML(text);
                            }
                            
                        });
                    });
                });
            </script>
        </button>'."\n";
               $text .= '</form>'."\n";
               $text .= '</div>'."\n";
               $text .= '<div class="answer'. ucfirst($nameMethod) .'"></div>';
           }else{
               $text .= '<?php session_start();?>'. "\n".
                       '<h2>'. $this->formatName($nameMethod) .'</h2>'."\n".
                       '<?php include_once (__DIR__) .\'/../../caller/'. $this->serviceName .'/'. $nameMethod .'.php\'; ?>';
           }
           if(!is_dir((__DIR__) .'/../../view/'. $this->serviceName)){
            mkdir((__DIR__) .'/../../view/'. $this->serviceName);
           }
           file_put_contents((__DIR__) .'/../../view/'. $this->serviceName .'/'. $nameMethod .'.php', $text);
       }
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

?>
