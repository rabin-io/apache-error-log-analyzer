#!/usr/bin/php
<?php

//var_dump($argc, $argv); exit;

$fp = ($argc === 1)  ? fopen('php://stdin', 'r') : @fopen($argv[1], "r");

$i=0;
$skip_lines = 0;
$errors = array();
$regexs = array();
#$regexs[] = '/(PHP [A-Za-z]+): (.+)/';
$regexs[] = '/(PHP Notice:  Undefined variable): (.+)/';
$regexs[] = '/(PHP Notice:  Undefined index): (.+)/';
$regexs[] = '/(PHP Notice:  Undefined offset): (.+)/';
$regexs[] = '/(PHP Warning:  include\(.*\)): (.+)/';
$regexs[] = '/(Permission denied): (.+)/';
$regexs[] = '/(Symbolic link not allowed or link target not accessible): (.+)/';
$regexs[] = '/(Directory index forbidden by Options directive): (.+)/';
$regexs[] = '/(Invalid URI in request GET) (.+)/';
$regexs[] = '/(Invalid method in request) (.+)/';
$regexs[] = "/(Invalid command) ('[A-Za-z0-9]+')/";
$regexs[] = '/(File does not exist): (.+)/';
$regexs[] = '/(We referred to the non-existing Page) (.+)/';
$regexs[] = '/(script not found or unable to stat): (.+)/';
$regexs[] = '/(attempt to invoke directory as script): (.+)/';
$regexs[] = '/^(referer): (.+)/';
$regexs[] = '/^(url): (.+)/';
$regexs[] = '/^(access to proxy):(.+)/';
$regexs[] = '/(Tried accessing us):/';
$regexs[] = '/(script not found or unable to stat): ([-_A-Za-z0-9\.]+)/';
$regexs[] = "/(script) ('[-_A-Za-z0-9\.\/]+' not found or unable to stat)/";
$regexs[] = "/(File name too long): (.{30})/";

$xml_replace = array(' ', ':', '(', ')');

if ($fp) {
    while (($buffer = fgets($fp, 8192)) !== false) { ++$i;
		
        $line = explode('] ', $buffer ); // will need only from part 3 and on
	if (!isset($line[3])) { fwrite(STDERR, "break on line: $i\n"); continue;}
        $line = explode(', referer: ' , $line[3]); // break on ref
        //print_r($line); stderr(var_dump($line));
		$m = 0;
		foreach ($regexs as $key=> $reg ) {

			if ( preg_match($reg, $line[0], $matches) ) { //stderr(var_dump($matches));

				//$matches[0] - all the string == line[0]
				//$matches[1] - 1st part of the match == PHP Notice:  Undefined variable
				//$matches[2] - 2nd part              == name in /xy/z.php on line 46

				$m += count($matches);     // to know if a line was not mached aginst any rule
				if ( !isset($matches[2]) ) // debug
				{
					//var_dump($i, $line, $reg, $matches); exit;
					$hash = 'id_' . hash('crc32', $line[1]);
					$matches[2] = $line[1];
				}
				else {
					$hash = 'id_' . hash('crc32', $matches[2]);         // only for XML compatbility
				}
				
				$error_name = str_replace($xml_replace, '_', $matches[1]); // only for XML compatbility
				//$matches[1] = str_replace($xml_replace, '_', $matches[1]); // only for XML compatbility

				//stderr(var_dump($matches[1]));
				
				if ( isset ($errors[$error_name][$hash] ) ) {
					$errors[$error_name][$hash]['count']++;
				}
				else {
					$errors[$error_name][$hash]['count'] = 1;
					$errors[$error_name][$hash]['msg']   = trim($matches[2],"\n ");
				}
				
				if ( isset($line[1]) ) { // if we have a ref
					$hash_ref = 'id_' . hash('crc32', $line[1]);
					if ( isset($errors[$error_name][$hash]['ref'][$hash_ref]) ) { // we have this ref
						$errors[$error_name][$hash]['ref'][$hash_ref]['count']++ ;
					}
					else {
						$errors[$error_name][$hash]['ref'][$hash_ref]['msg']   = trim($line[1],"\n ");
						$errors[$error_name][$hash]['ref'][$hash_ref]['count'] = 1;
					}
				}
				//stderr($errors);
			}
		}
		if (!$m) { // not in the regex array
			$errors['other']['line_' . $i]['msg'] = $line[0];
		}

        // limit
        //if ($i>25) {break;} else {continue;};
    }
    
    
    
    if (!feof($fp)) {
        //echo "Error: unexpected fgets() fail\n";
        fwrite(STDERR, "Error: unexpected fgets() fail on line $i\n");
    }
    
    fclose($fp);
    
    
    //print_r($errors);
    echo array2xml($errors);
}
?>

<?php

function stderr($mix) {
	if ( is_string($mix) ) 	{
		fwrite(STDERR, $mix);
	}
	else {
		fwrite(STDERR, print_r($mix,true));
	}
}

function array2xml($array, $xml = false){

    if($xml === false){
        $xml = new SimpleXMLElement('<apache_errors/>');
    }
    foreach($array as $key => $value){
        if(is_array($value)){
            array2xml($value, $xml->addChild($key));
        }else{
			//var_dump($key, $value);
            //$xml->addChild($key, str_replace('&', '~', htmlentities($value))); // XML can handle & on childe
			$xml->addAttribute($key,str_replace('&', '~', htmlentities($value)));
        }
    }
    return $xml->asXML();
}

?>

