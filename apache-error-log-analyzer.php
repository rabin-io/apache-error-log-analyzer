<?php

/**
 * A simple script so parse Apache error log
 **/

// open a file or read from stdin
$fp = ($argc === 1) ? fopen('php://stdin', 'r') : fopen($argv[1], "r");

$i          = 0;
$skip_lines = 0;
$errors     = array(); // hold the error logs messages, count and ref
$regexs     = array();

/* the regex must be in 2 parts
   part1 will be the message header,
   part2 will be the the error details.
 */

$regexs[] = '/(PHP Notice:  Undefined variable): (.+)/';
$regexs[] = '/(PHP Notice:  Undefined index): (.+)/';
$regexs[] = '/(PHP Notice:  Undefined offset): (.+)/';
//$regexs[] = '/(PHP Warning:  include\(.*\)): (.+)/';
$regexs[] = '/(PHP Warning): (.+)/';
$regexs[] = '/(PHP Fatal error):  (.+)/';
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

// for XML compatibility there are some char we need to replace
$xml_replace = array(' ', ':', '(', ')');

if ($fp) {
    while (($buffer = fgets($fp, 8192)) !== false) {
        ++$i;

        $line = explode('] ', $buffer); // will need only from part 3 and on
        if (!isset($line[3])) {
            fwrite(STDERR, "break on line: $i\n");
            continue;
        }
        $line = explode(', referer: ', $line[3]); // break on ref
        //print_r($line); stderr(var_dump($line));
        $m = 0;
        foreach ($regexs as $key => $reg) {
            if (preg_match($reg, $line[0], $matches)) { //stderr(var_dump($matches));
                // to know if a line was not matched against any rule
                $m += count($matches);
                if (!isset($matches[2])) { // debug
                    //var_dump($i, $line, $reg, $matches); exit;
                    $hash = 'id_' . hash('crc32', $line[1]);
                    $matches[2] = $line[1];
                } else {
                    // only for XML compatibility
                    $hash = 'id_' . hash('crc32', $matches[2]);
                }

                // only for XML compatibility
                $error_name = str_replace($xml_replace, '_', $matches[1]);

                if (isset($errors[$error_name][$hash])) {
                    $errors[$error_name][$hash]['count']++;
                } else {
                    $errors[$error_name][$hash]['count'] = 1;
                    $errors[$error_name][$hash]['msg']   = trim($matches[2], "\n ");
                }

                if (isset($line[1])) { // if we have a ref
                    $hash_ref = 'id_' . hash('crc32', $line[1]);

                    // we have this ref
                    if (isset($errors[$error_name][$hash]['ref'][$hash_ref])) {
                        $errors[$error_name][$hash]['ref'][$hash_ref]['count']++ ;
                    } else {
                        $errors[$error_name][$hash]['ref'][$hash_ref]['count'] = 1;
                        $errors[$error_name][$hash]['ref'][$hash_ref]['msg'] = trim($line[1], "\n ");
                    }
                }
            }
        }
        if (!$m) { // not in the regex array
            $hash = 'id_' . hash('crc32', $line[0]);
            if (isset($errors['other'][$hash])) {
                $errors['other'][$hash]['count']++;
            } else {
                $errors['other'][$hash]['count'] = 1;
                $errors['other'][$hash]['line']  = $i;
                $errors['other'][$hash]['msg']   = trim($line[0], "\n ");
            }
        }
    }

    if (!feof($fp)) {
        fwrite(STDERR, "Error: unexpected fgets() fail on line $i\n");
    }

    fclose($fp);

    // sort the errors by there counters
    foreach ($errors as $key => &$items) {
        uasort($items, "cmp");
    }

    echo array2xml($errors);
} // if ($fp) {
