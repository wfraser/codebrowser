<?php

/*
** Miscellaneous Functions
** for Codewise Manager
**
** by William R. Fraser <wrf@codewise.org> (7/30/2008)
** Copyright (c) 2008 Codewise.org
*/

function vdump($var, $return = FALSE)
{
    $output = "<pre>";
    ob_start();
    var_dump($var);
    $output .= htmlspecialchars(ob_get_clean());
    $output .= "</pre>";
    if ($return)
        return $output;
    else
        echo $output;
}

function url_add_var($url, $varname, $varval)
{
    if (in_array($varname, array_keys($_GET))) {
        return preg_replace(
            "/([&?])$varname=.*([&?]|$)/",
            "\\1$varname=$varval\\2",
            $url);
    }
    else if (!empty($_GET)) {
        return $url . "&amp;$varname=$varval";
    }
    else {
        return $url . "?$varname=$varval";
    }
}

/*
** Tries to elucidate the type of a file based on its name.
** Returns languages of source code, mime types of images, and "plain" for
** all else.
**
** Passing the list of the current directory helps determine whether .h files
** should be parsed as C or C++.
*/
function sourcetype($filename, $ls = array())
{
    $ext = strtolower(substr(strrchr($filename, "."), 1));
    switch ($ext) {
    case "ada":
    case "asm":
    case "awk":
    case "c":
    case "cpp":
    case "diff":
    case "ebuild":
    case "html":
    case "java":
    case "m4":
    case "sh":
    case "sql":
    case "tex":
    case "php": // special case handled by PHP
        // language is same as extension
        return $ext;

    case "l":   // lex
    case "y":   // yacc/bison
        return "c";
    
    case "s":
        return "asm";
    case "cc":
    case "cxx":
    case "c++":
        return "cpp";
    case "js":
        return "javascript";
    case "rtf":
        return "lang_rtf";
    case "phtml":
        return "php";
    case "pl":
        return "perl";
    case "py":
        return "python";
    case "rb":
        return "ruby";
    case "h":
        $base = ereg_replace(quotemeta($ext)."$", "", $filename);
        if (isset($ls[$base."cpp"]) || isset($ls[$base."cxx"]) 
                || isset($ls[$base."c++"]) || isset($ls[$base."cc"]))
            return "cpp";
        else
            return "cpp";

    case "jpg":
    case "jpeg":
        return "image/jpeg";
    case "png":
        return "image/png";
    case "gif":
        return "image/gif";
    case "tif":
    case "tiff":
        return "image/tiff";
    case "ico":
        return "image/vnd.microsoft.icon";
    }

    if (stripos($filename, "makefile") !== FALSE)
        return "makefile";
    
    return "plain";
}

// single-quote escape (for shell)
function sqesc($str)
{
    return str_replace('\'', '\'\\\'\'', $str);
}

// vim: sts=4 expandtab

?>
