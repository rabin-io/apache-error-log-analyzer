<?php

/**
 * Helper function for  apache-error-log-analyzer.php
 *
 **/

function cmp($a, $b)
{
    return $a['count'] < $b['count'] ;
}

/**
 * Write to STDERR
 *
 * @return void
 */
function stderr($mix)
{
    if (is_string($mix)) {
        fwrite(STDERR, $mix);
    } else {
        fwrite(STDERR, print_r($mix, true));
    }
}

/**
 * Convert an Array to XML
 *
 * @return string
 **/
function array2xml($array, $xml = false)
{
    if ($xml === false) {
        $xml = new SimpleXMLElement('<apache_errors/>');
    }

    foreach ($array as $key => $value) {
        if (is_array($value)) {
            if (preg_match('/id_([A-Za-z0-9]+)/', $key, $matches)) {
                array2xml(array_merge($value, array('id' => $matches[1])), $xml->addChild("item"));
            } else {
                array2xml($value, $xml->addChild($key));
            }
        } else {
            /* only childs */
            // $xml->addChild($key, str_replace('&', '~', htmlentities($value))); // XML can't handle & on childe
            // last level are keys
            $xml->addAttribute($key, str_replace('&', '~', htmlentities($value)));
        }
    }

    return $xml->asXML();
}
