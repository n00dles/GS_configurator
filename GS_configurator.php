<?php 

# get correct id for plugin

$thisfile=basename(__FILE__, ".php");



# register plugin

register_plugin(
  $thisfile,
  'GSCONFIG settings',
  '1.0',
  'Mike Swan',
  'http://www.digimute.com/',
  'GSCONFIG Settings',
  'plugins',
  'getGsconfig'
  );



$defines=array();


add_action('plugins-sidebar','createSideMenu',array($thisfile,'GSCONFIG Info'));  	// add menu entry

$liveDefines=array();

function getGsconfig(){
	global $liveDefines; 
	$my_file = file_get_contents("../gsconfig.php");
	$convert = explode("\n", $my_file); //create array separate by new line
	$ln=1;
	
	foreach ($convert as $line){	
		if(substr($line,0,6)=='define'){		
			getToken("<?php ".$line,$ln,'true',$lastline);
		} else if (substr($line,0,7)=='#define'){
			getToken("<?php ".substr($line,1),$ln,'false',$lastline);
		} else {
			$lastline=trim(substr($line,1));
		}
		$ln++;
	}
	echo "<h3>Configuration Options</h3>";
	echo '<div style="" id="metadata_window">';
	echo "<form method='post' action='load.php?id=dm_gsconfig' >"; 
	echo '<table>';
	foreach ($liveDefines as $k=>$define){
		echo '<tr>';
		echo "<td>";
		
		if ($define['live']=='true' or isset($_POST['input_'.$k])){
			echo "<input type='checkbox' class='enabler' style='width:30px;'   id='check_".$k."' value='1' checked>".$define['define'];
			$live=true;
		} else {
			echo "<input type='checkbox' class='enabler'  style='width:30px;' id='check_".$k."' value='1' >".$define['define'];
			$live=false;
		}
		echo '</td>';
		//echo "<strong style='width:200px;'>".$define['define']."</strong>";
		echo "<td>";
		echo "<input type='text' class='text short' style='float:right;width:450px;' name='input_".$k."' id='input_".$k."' value='".$define['value']."'";
		if ($live!=true) echo ' disabled '; 
		echo  ">";
		//echo "<p>".$define['desc']."</p>";
		echo "<input type='hidden' value='".$define['define']."' disabled>";
		
		echo "<td>";
		echo "</tr>";
	}
	echo "</table>";
	echo "</div>";
	echo "<input type='submit' value='Submit Changes' />";
	echo "</form>";
	echo '<script type="text/javascript">
	$(".enabler").live("click", function ($e) {
		currID = $(this).attr("id").substr(6);
		//alert("input_"+currID);
		$("#input_"+currID).prop("disabled",!$("#input_"+currID).prop("disabled"))
	})
	
	
	</script>';
}

function getToken($tokend,$line,$live,$desc){
		
	global $liveDefines; 
	
	$defines=array();
	$state = 0;
	$key = '';
	$value = '';
	//echo $tokend;
	$tokens = token_get_all($tokend);
	
	$token = reset($tokens);	
	
	while ($token) {
	    if (is_array($token)) {
	        if ($token[0] == T_WHITESPACE || $token[0] == T_COMMENT || $token[0] == T_DOC_COMMENT) {
	            // do nothing
	        } else if ($token[0] == T_STRING && strtolower($token[1]) == 'define') {
	            $state = 1;
	        } else if ($state == 2 && dm_is_constant($token[0])) {
	            $key = $token[1];
	            $state = 3;
	        } else if ($state == 4 && dm_is_constant($token[0])) {
	            $value = $token[1];
	            $state = 5;
	        }
	    } else {
	        $symbol = trim($token);
	        if ($symbol == '(' && $state == 1) {
	            $state = 2;
	        } else if ($symbol == ',' && $state == 3) {
	            $state = 4;
	        } else if ($symbol == ')' && $state == 5) {
	            $defines[dm_strip($key)] = dm_strip($value);
	            $state = 0;
	        }
	    }
	    $token = next($tokens);
	}
	
	
	foreach ($defines as $k => $v) {
		//echo $k." = ".$v;
	    $liveDefines[$line]['define']=$k;
		$liveDefines[$line]['value']=$v;
		$liveDefines[$line]['live']=$live;
		$liveDefines[$line]['desc']=$desc;
	}
}

function dm_is_constant($token) {
    return $token == T_CONSTANT_ENCAPSED_STRING || $token == T_STRING ||
        $token == T_LNUMBER || $token == T_DNUMBER;
}

function dm_strip($value) {
    return preg_replace('!^([\'"])(.*)\1$!', '$2', $value);
}
?>