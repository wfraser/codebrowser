<?php

/*
** Enscript Functions
** for Codewise Source Code Browser
**
** original version by William R. Fraser <wrf@codewise.org> (7/30/2008)
** Copyright (c) 2008-2010 William R. Fraser
*/

/*
** Uses Enscript to highlight the passed source code in the given language.
** Must be able to do a proc_open on the path in ENSCRIPT_BINARY constant.
*/
function enscript($code, $lang)
{
    $lang = str_replace("../", "", $lang);
    
    if ($lang == "php") {
        $highlighted = highlight_string($code, TRUE);
        $lines = explode("<br />", $highlighted);
        // chop off the unneccessary <code> and black color
        $lines[0] = str_replace("<code><span style=\"color: #000000\">\n", "", $lines[0]);
        $lines[count($lines)-1] = str_replace("\n</span>\n</code>", "", $lines[count($lines)-1]);
        return $lines;
    }

    $fd = array(
        0 => array("pipe", "r"),
        1 => array("pipe", "w"),
        2 => array("pipe", "w"),
    );

    $p = proc_open(ENSCRIPT_BINARY." -q -p - --pretty-print=".escapeshellarg($lang)." --language=html --color", $fd, $pipes);

    if (is_resource($p)) {
        fwrite($pipes[0], $code);
        fclose($pipes[0]);
        $output = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $retval = proc_close($p);
    } else {
        message("Enscript Error", "Couldn't execute enscript");
    }

    if ($error != "") {
        message("Enscript Error", "Enscript said: ".$error);
    }

    $lines = explode("\n", $output);

    $code_lines = array();
    $n = FALSE; // don't count lines until we get the <PRE>
    for ($i = 0; $i <= count($lines); $i++) {
        if ($lines[$i] == "<PRE>") {
            $n = 1; // start counting lines now
        } else if (strpos($lines[$i], "</PRE>") !== FALSE) {
            list($line) = preg_split("/<\/PRE>/", $lines[$i]);
            $code_lines[] = $line;
            return $code_lines; // all done!
        } else if ($n !== FALSE) {
            // don't go until we're counting lines
            $line = $lines[$i];

            // clean up Enscript's nasty HTML
            $line = preg_replace("/<FONT COLOR=\"(.*?)\">/",
                    "<span style=\"color: \\1\">", $line);
            $line = str_replace(
                array(
                    "</FONT>",
                    "<B>",
                    "</B>",
                    "<I>",
                    "</I>",
                ),
                array(
                    "</span>",
                    "<b>",
                    "</b>",
                    "<i>",
                    "</i>",
                ),
                $line
            );

            if (strpos($line, "</PRE>") !== FALSE) {
                $line = substr($line, 0, strpos($line, "</PRE>"));
                $code_lines[] = $line;
                return $code_lines;
            }

            $code_lines[] = $line;
            $n++;
        }
    }
}

// vim: sts=4 expandtab

?>
