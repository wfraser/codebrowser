<?php

/*
 * Source Code Browser
 */

define("START_TIME", microtime(true));

ini_set('display_errors', true);
error_reporting(E_ALL);

define("INLINE_IMG", false);
define("REBUILD_PATH_INFO", true);
define("SOURCE_ENSCRIPT", true);
define("ENSCRIPT_BINARY", "/home/wfraser/bin/enscript");

$ROOT = array(
    "this"      => "/home/wfraser/public_html/code",
    "gasmiles"  => "/home/wfraser/gasmiles",
    "test"      => "/home/wfraser/test",
);

require 'source.php';
require 'template.php';
require 'enscript.php';
require 'misc.php';

//WRFDEV
if (in_array("dump", array_keys($_GET))) {
    dump($_SERVER);
}

$UI = array("main" => array(
    "main" => "",
    "title" => "WRF's Code Browser",
    "css" => dirname($_SERVER['SCRIPT_NAME'])."/res/style.css",
));

if (REBUILD_PATH_INFO) {
    $_SERVER['PATH_INFO'] = str_replace(dirname($_SERVER['SCRIPT_NAME']), "", $_SERVER['REDIRECT_URL']);
    $_SERVER['PATH_INFO'] = substr($_SERVER['PATH_INFO'], 1);
}

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
        $fspath = $check_fspath . "/" . implode("/", $uriparts);

        //WRFDEV
        $UI['main']['main'] = $fspath;

        if (!file_exists($fspath)) {
            echo "404! $fspath not found";
            $UI['404'] = array(
                //WRFDEV
            );
            $UI['main']['main'] = template("404");
        }
        else if (is_dir($fspath)) {
            // list directory
            $UI['dirlist'] = array(
                //WRFDEV
            );
            $UI['main']['main'] = template("dirlist");
        }
        else {
            $file = file_get_contents($fspath);
            
            if (isset($_GET['lang'])) {
                $lang = $_GET['lang'];
            }
            else {
                $lang = sourcetype($fspath);
            }

            if ($lang == "raw") {
                // extra special case
                $lang = sourcetype($fspath);
                if (strpos($lang, "image/")) {
                    header("Content-Type: $lang");
                }
                else {
                    header("Content-Type: text/plain");
                }

                readfile($fspath);
                exit;
            }

            $result = source_highlight($file, $lang,
                (in_array('width', array_keys($_GET)) ? $_GET['width'] : FALSE));

            $type = $result[1];
            $more = $result[2];
            $labels = $result[3];
            $uidata = array(
                "name" => implode("/", $uriparts),
                "bytes" => strlen($file),
                "num_lines" => count($result[0]),
                "language" => $lang,
                "lines" => $result[0],
                "more" => $more,
                "morelink" => url_add_var($_SERVER['REQUEST_URI'], "start", $more),
                "type" => $type,
                "labels" => $labels,
                "rawlink" => url_add_var($_SERVER['REQUEST_URI'], "lang", "raw"),
                "plainlink" => url_add_var($_SERVER['REQUEST_URI'], "lang", "plain"),
                "hexlink" => url_add_var($_SERVER['REQUEST_URI'], "lang", "hex"),
            );

            if ($type == "image") {
                if (INLINE_IMG) {
                    $uidata['inline_img'] = $lines[0];
                    $uidata['img_href'] = FALSE;
                }
                else {
                    $uidata['inline_img'] = FALSE;
                    $uidata['img_href'] = url_add_var($_SERVER['REQUEST_URI'], 
                        "lang", "raw");
                }
            }

            $UI['filedisplay'] = $uidata;
            $UI['main']['main'] = template("filedisplay");

        } // if file

        break; // end loop
    } // if path match
} // foreach $ROOT

if ($fspath == "") {
    // path didn't match any of $ROOT
    $UI['main']['main'] = "invalid project specified";
}

echo template("main");

?>
