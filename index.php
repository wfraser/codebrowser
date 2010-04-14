<?php

if (!isset($_GET['debug'])) {
    die("nothing here yet :)");
}

/*
 * Source Code Browser
 */

define("START_TIME", microtime(true));

ini_set('display_errors', true);
error_reporting(E_ALL);

define("SOURCE_ENSCRIPT", true);
define("ENSCRIPT_BINARY", "/home/wfraser/bin/enscript");

$ROOT = array("gasmiles" => "/home/wfraser/gasmiles");

require 'source.php';
require 'template.php';
require 'enscript.php';
require 'misc.php';

$UI = array("main" => array(
    "main" => "",
    "title" => "WRF's Code Browser",
    "css" => dirname($_SERVER['SCRIPT_NAME'])."/res/style.css",
));

$uriparts = preg_split("#/#", $_SERVER['PATH_INFO'], -1, PREG_SPLIT_NO_EMPTY);
$fspath = "";
foreach ($ROOT as $check_uripath => $check_fspath) {
    if ($check_uripath == $uriparts[0]) {
        array_shift($uriparts);
        $fspath = $check_fspath . "/" . implode($uriparts);

        //WRFDEV
        $UI['main']['main'] = $fspath;

        if (!file_exists($fspath)) {
            echo "404!";
            die(template("404"));
        }

        $file = file_get_contents($fspath);
        $type = sourcetype($fspath);
        $result = source_highlight($file, $type,
            (in_array('width', array_keys($_GET)) ? $_GET['width'] : FALSE));

        $UI['sourcecode'] = array(
            "name" => basename($fspath),
            "bytes" => strlen($file),
            "num_lines" => count($result[0]),
            "language" => $type,
            "lines" => $result[0],
            "more" => $result[1],
            "morelink" => "XXX",
        );

        $UI['main']['main'] = template("sourcecode");
    }
}

if ($fspath == "") {
    $UI['main']['main'] = "do main index here";
}

echo template("main");

?>
