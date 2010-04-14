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

if ($_SERVER['PATH_INFO'] == "/" || $_SERVER['PATH_INFO'] == "") {
    $UI['index'] = array(
        //WRFDEV
    );
    $UI['main']['main'] = template("index");
    die(template("main"));
}

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
        $lang = sourcetype($fspath);
        $result = source_highlight($file, $lang,
            (in_array('width', array_keys($_GET)) ? $_GET['width'] : FALSE));

        $type = $result[1];
        switch ($type) {
        case "code":
            $UI['sourcecode'] = array(
                "name" => basename($fspath),
                "bytes" => strlen($file),
                "num_lines" => count($result[0]),
                "language" => $lang,
                "lines" => $result[0],
                "more" => $result[2],
                "morelink" => "XXX",
            );
            $UI['main']['main'] = template("sourcecode");
            break;

        case "image":
            $UI['imagefile'] = array(
                //WRFDEV
            );
            $UI['main']['main'] = template("imagefile");
            break;

        case "binary":
            $UI['binfile'] = array(
                //WRFDEV
            );
            $UI['main']['main'] = template("binfile");
            break;
        } // switch type

        break;
    } // if path match
} // foreach $ROOT

if ($fspath == "") {
    // path didn't match any of $ROOT
    $UI['main']['main'] = "invalid project specified";
}

echo template("main");

?>
