<?php
//
ini_set("soap.wsdl_cache_enabled", "0");
session_start();

function unregister_GLOBALS()
{
    if (!ini_get('register_globals')) {
        return;
    }

    // Vous pouvez vouloir modifier cela pour avoir une erreur plus jolie
    if (isset($_REQUEST['GLOBALS']) || isset($_FILES['GLOBALS'])) {
    die('Tentative d\'effacement des GLOBALS détectée');
    }

    // Les variables à ne jamais effacer
    $noUnset = array('GLOBALS',  '_GET',
    '_POST',    '_COOKIE',
    '_REQUEST', '_SERVER',
    '_ENV',     '_FILES');

    $input = array_merge($_GET,    $_POST,
    $_COOKIE, $_SERVER,
    $_ENV,    $_FILES,
    isset($_SESSION) && is_array($_SESSION) ? $_SESSION : array());

    foreach ($input as $k => $v) {
        if (!in_array($k, $noUnset) && isset($GLOBALS[$k])) {
            unset($GLOBALS[$k]);
        }
    }
}

unregister_GLOBALS();
include_once (__DIR__). '/auth.php';
require_once (__DIR__) .'/lib/generatorUI/GeneratorUi.php';
include_once (__DIR__). '/gene.php';
include_once (__DIR__) .'/lib/yaml/sfYaml.php';

$title = $conf['config']['title'];
$logo = $conf['config']['logo'];
$favicon = $conf['config']['favicon'];
if(!empty($_SESSION['service'])){
	$generatorUi = new GeneratorUi($_SESSION['service']);
    $listFunctionalities = $generatorUi->getSoapMethodAvailable();
    asort($listFunctionalities);
}
$board = sfYaml::load((__DIR__) .'/conf/board.yml');
function makeList($wanted, $listFunctionalities, $selected="false"){
	if(!is_array($listFunctionalities)){
		$listFunctionalities = array($listFunctionalities);
	}
	if(!is_array($wanted)){
		$wanted = array($wanted);
	}
	?>
	<div data-dojo-type="dijit/layout/AccordionPane" title="<?php echo ucfirst(implode(" & ", $wanted)); ?>" selected="<?php echo $selected; ?>">
		<ul class="panel">
	<?php
		foreach ($listFunctionalities as $key => $value) {
    		foreach ($wanted as $valueWanted) {
    			if(stristr($value, $valueWanted)){
    				echo '<li><a href="#" rel="'. $key .'" title="'. ucfirst($value) .'" onClick="return false;">'. ucfirst($value) .'</a></li>'."\n";
    				break;
    			}
    		}
        }
	?>
		</ul>
	</div>
	<?php
}
?>
<!DOCTYPE html>
<html>
	<head>
		<link rel="icon" type="image/x-icon" href="<?php echo $favicon; ?>" />
		<meta content="text/html; charset=UTF-8" http-equiv="content-type"/>
		<title><?php echo $title; ?> - <?php echo $_SERVER['SERVER_NAME']; ?></title>
		<style  type="text/css">
			@import "./js/dojo/dijit/themes/tundra/tundra.css";
			@import "./js/dojo/dojo/resources/dojo.css";
			body, html { width:100%; height:100%; margin:0; padding:0; overflow:hidden; }
			#borderContainerTwo {
			    width: 100%;
			    height: 100%;
			}
			a, a:link {
			    color: #0092C9;
			    text-decoration: none;
			}
			a:active, a:visited {
			    color: #140586;
			}
			a:hover, a:focus {
			    text-decoration: underline;
			}
			ul.panel {
				padding: 0;
				margin: 0;
			}
			ul.panel li{
				list-style: none;
				padding: 0;
				margin: 0;

			}
			ul li a{
				display: block;
				
			}
			fieldset{
				padding-left: 15px;
				width: 300px;

				height: auto;
				border: 1px solid black;
			}
			fieldset legend{
				font-weight: bold;
			}
			.dijitDialogCloseIcon{
				display:none;
			}
		</style>
	</head>
	<body class="tundra">
		<script type="text/javascript" src="./js/dojo/dojo/dojo.js" djConfig="isDebug: true, parseOnLoad: true"></script>
		
		<?php
		if(empty($_SESSION['service']) || (empty($conf['config']['loginNeeded']) xor empty($_SESSION['login']))){
			?>
			<script type="text/javascript">
				require(["dojo/parser", "dijit/Dialog", "dijit/form/Button", "dijit/form/TextBox", "dijit/form/DateTextBox", "dijit/form/TimeTextBox"]);
			</script>
			<div data-dojo-type="dijit/Dialog" data-dojo-id="myFormDialog" title="Connection" style="min-width: 30%;height: 50%;">
				<form method="post" action="index.php">
				    <div class="dijitDialogPaneContentArea">
				    	<span style="color: red;"><?php echo $textError;?>
				        <table>
				        	<tr>
				                <td><label for="login">Services: </label></td>
				                <td>
				                	<select name="service" >
									   <?php
									   if(!empty($conf['services'])){
										   	foreach ($conf['services'] as $key => $value) {
										   		echo '<option value="'. $key .'">'. $key .'</option>';
										   	}
									   }
									   	
									   ?>
									</select>
				                </td>
				            </tr>
				            <?php if(!empty($conf['config']['loginNeeded'])){?>
				            <tr>
				                <td><label for="login">Login: </label></td>
				                <td><input  type="text" name="login" id="login"></td>
				            </tr>
				            <tr>
				                <td><label for="password">Password: </label></td>
				                <td><input  type="password" name="password" id="password"></td>
				            </tr>
				            <?php } ?>
				        </table>
				    </div>

				    <div class="dijitDialogPaneActionBar">
				        <button type="submit" >
				            Connect
				        </button>
				    </div>
				</form>
			</div>
			<script type="text/javascript">
				dojo.addOnLoad(function(){
				  myFormDialog.show();
				  myFormDialog._onKey = function(){};
				  
			  	}); 
			</script>
			<?php
			}
			?>
		<div data-dojo-type="dijit/layout/BorderContainer" data-dojo-props="gutters:true, liveSplitters:false" id="borderContainerTwo">
		    <div data-dojo-type="dijit/layout/ContentPane" data-dojo-props="region:'top', splitter:false">
		        <div style="float: left;"><img src="<?php echo $logo; ?>" width="25%"/></div> 
		        <div style="float: right;">
		        	<a href="?logout=1">Logout</a><br/>
		        	<a href="?regenerate=1">Regenerate</a>
		        </div>
		        <h1><?php echo $title; ?></h1>
		    </div>
		    <div data-dojo-type="dijit/layout/AccordionContainer" data-dojo-props="minSize:20, region:'leading', splitter:true" style="width: 300px;" id="leftAccordion">
		        <?php
		        	$i=0;
		        	foreach ($board['menu'] as $value) {
		        		if($i==0){
		        			$selected = "true";
		        		}else{
		        			$selected = "false";
		        		}
		        		makeList($value, $listFunctionalities, $selected);
		        		$i++;
		        	}
		        ?>
		        <div data-dojo-type="dijit/layout/AccordionPane" title="Database Information" selected="<?php echo $selected; ?>">
					<ul class="panel">
						<li><a href="#" rel="getMaxDatabases" title="Get Max Databases" onClick="return false;">Get Max Databases</a></li>
						<li><a href="#" rel="getDatabasesLeft" title="Get Databases Left" onClick="return false;">Get Databases Left</a></li>
					</ul>
				</div>
                        <div data-dojo-type="dijit/layout/AccordionPane" title="List of functionalities">
                            <ul class="panel">
                                <?php
                                	if(!empty($_SESSION['service'])){
	                                    foreach ($listFunctionalities as $key => $value) {
                                        	echo '<li><a href="#" rel="'. $key .'" title="'. ucfirst($value) .'" onClick="return false;">'. ucfirst($value) .'</a></li>'."\n";
	                                    }
                                	}
                                    
                                ?>
                            </ul>
                        </div>
		    </div><!-- end AccordionContainer -->
		    <div data-dojo-type="dijit/layout/TabContainer" data-dojo-props="region:'center', tabStrip:true" id="tabContainer">
		    	<div data-dojo-type="dijit/layout/ContentPane" title="Welcome" selected="true">
		        	
		        </div>
		    </div><!-- end TabContainer -->
		</div><!-- end BorderContainer -->
                
                <script>
                    require(["dojo/dom-attr"]);
                    require(["dojo/query", "dojo/domReady!"], function(query) {
                    	require(["dojo/parser", "dijit/layout/ContentPane", "dijit/layout/BorderContainer", "dijit/layout/TabContainer", "dijit/layout/AccordionContainer", "dijit/layout/AccordionPane"]);
                        //require("dojox/layout/ExpandoPane");
                        query(".panel a").on("click", function(evt){
                           var node = evt.target;
                           var functionality = dojo.getAttr(node, "rel");
                           if(dijit.byId('tab'+ functionality) != undefined){
                               dijit.byId('tabContainer').selectChild('tab'+ functionality);
                               return;
                           }
                           dijit.byId('tabContainer').addChild(
                                new dijit.layout.ContentPane({ 
                                	functionalitycontent: functionality,
                                    title: dojo.getAttr(node, "title"), 
                                    content:'',
                                    id: 'tab'+ functionality,
                                    closable:true,
                                    _self: this,
                                    onShow: function(){
                                    	var functionalityajax = this.functionalitycontent;
                                    	dijit.byId('tab'+ functionalityajax).set("content", "Loading...");
                                    	dojo.xhrGet({
			                                url: "view/<?php echo $_SESSION['service']; ?>/"+ functionalityajax+".php",
			                                load: function(newContent) {
			                                    dijit.byId('tab'+ functionalityajax).set("content", newContent);
			                                },
			                                error: function(response, ioArgs) {
			                                	alert("HTTP status code: ", ioArgs.xhr.status); 
			                     				alert(response.message); 
			                                }
			                            });
                                    }
                                })
                            );

                            
                            dijit.byId('tabContainer').selectChild('tab'+ functionality);
                        });
                    });
                </script>
	</body>
</html>