<?php

/*
** Source Code Formatter
**
** original version by William R. Fraser <wrf@codewise.org> 8/21/2008
** updated 4/16/2010
** Copyright (c) 2008-2010 WRF
*/

// process 20KB at a time
define("MANAGER_HEXDUMP_LIMIT", 0x5000);

/*
** Highlights the given source code string as the language given.
** $lang may be an Enscript-supported language, a MIME-type starting with
** "image/", or "plain" to do no formatting.
** If the language is not an image mime type and the string contains NULL
** characters, a hexdump of the first 20KB is done instead.
** If Enscript is not enabled, it is formatted as "plain".
*/
function source_highlight($source, $lang, $max_len)
{
    $type = "code";
    $lines = array();
    $labels = array(); // used by binaries
    if (strpos($lang, "image/") === 0) {
        $lines = array(base64_encode($source));
        $type = "image";
        return array($lines, $type, null, null);
    } else if ($lang == "hex" || strpos($source, "\000") !== FALSE) {
        // file contains NULs = we have a binary
        // awesome hex dump mode
        $type = "binary";
        $l = strlen($source);
        if (isset($_GET['start']))
            $start = $_GET['start'];
        else
            $start = 0;
        // 16 bytes per line
        for ($i = $start, $n = 0; $i-1 < $l && $i < $start + MANAGER_HEXDUMP_LIMIT; $i += 16, $n++) {
            $labels[$n] = sprintf("0x%04x", $n*16+$start);
            $lines[$n] = "";
            // two columns of 8 hex values each
            for ($j = 0; $j < 16; $j++) {
                if ($l <= $i + $j) {
                    $lines[$n] .= "   ";
                } else {
                    $x = base_convert(ord($source[$i + $j]),
                            10, 16);
                    $x = str_pad($x, 2, 0, STR_PAD_LEFT);
                    $lines[$n] .= "$x ";
                    if ($j == 7)
                        $lines[$n] .= "  ";
                }
            }
            $lines[$n] .= "  ";
            // ease the suffering of browsers by stringing
            // adjacent grays together in one <span>
            $gray = FALSE;
            // ASCII display, with gray dots in place of
            // unprintable chars.
            for ($j = 0; $j < 16 && $l > $i + $j; $j++) {
                if ($l <= $i + $j) {
                    $lines[$n] .= " ";
                } else {
                    $c = $source[$i + $j];
                    if (ord($c) < 32 || ord($c) > 126) {
                        if (!$gray) {
                            $gray = TRUE;
                            $c = "<span style=\"color:gray\">.";
                        } else {
                            $c = ".";
                        }
                    } else {
                        if ($gray) {
                            $gray = FALSE;
                            $c = "</span>".str_replace(" ", "&nbsp;",
                                    htmlspecialchars($c));
                        } else {
                            $c = str_replace(" ", "&nbsp;",
                                    htmlspecialchars($c));
                        }
                    }
                    $lines[$n] .= $c;
                }
            }
            if ($gray)
                $lines[$n] .= "</span>";
        }
        if ($i < $l)
            $more = $i;
        else
            $more = FALSE;
    } else if (SOURCE_ENSCRIPT && ($lang != "plain")) {
        $lines = enscript($source, $lang);
        $more = FALSE;
    } else {
        $lines = explode("\n", htmlspecialchars($source));
        $more = FALSE;
    }

    // return values:
    // array of the HTML-formatted lines
    // the type of file: either "plain", "image", or "binary"
    // whether there's more to the file. Either FALSE (no more), or a byte
    //   offset to start the next page at
    // formatted line labels (only used by the "binary" format
    return array(source_highlight_fixup($lines, $max_len), $type, $more, $labels);
}

/*
** Simple pushdown automation for making sure that each line is self-complete
** (i.e. leaves no tags open and doesn't depend on earlier open tags). It does
** this by closing open tags at the end of each line and re-opening said tags
** at the beginning of the next line.
**
** This allows lines of source code to be completely independent of each other.
**
** Pass it an array of formatted lines, and it returns an array of formatted,
** self-complete lines.
*/
function source_highlight_fixup($lines, $max_len = 80)
{
    for ($n = 0; $n < count($lines); $n++) {
        $line = $lines[$n];
        $stack = array();
        $buffer = NULL;
        $len = 0;
        $linebreak = "&#x23ce;<br />";
        $linebreaklen = strlen($linebreak);
        $in_entity = FALSE;
        for ($i = 0; $i < strlen($line); $i++) {
            if ($in_entity) {
                if ($line[$i] == ";") {
                    $in_entity = FALSE;
                    $len++;
                }
                continue;
            }
            if ($line[$i] == "&") {
                $in_entity = TRUE;
                continue;
            }
            if ($buffer === NULL) {
                // skip forward until we get a tag
                if ($line[$i] != "<") {
                    if ($line[$i] == "\t")
                        $len += 8;
                    else
                        $len++;
                    if ($max_len != FALSE && $len > $max_len) {
                        $line = substr($line, 0, $i) . $linebreak . substr($line, $i);
                        $lines[$n] = $line;
                        $len = 0;
                        $i += $linebreaklen;
                    }
                    continue;
                }
                $buffer = "";
            } else {
                if ($line[$i] == ">") {     // finished tag
                    // Garbage in, garbage out. Assume the input is correct and
                    // tags match. To actually check, use the following:
                    //$last = $stack[count($stack) - 1];
                    //$tname = substr($last, 0, strpos($last, " "));
                    //if ($tname == "")
                    //    $tname = $last;
                    //if ($buffer == "/".$tname)

                    if ($buffer[0] == "/")
                        // close tag, pop off the last one
                        array_pop($stack);
                    else
                        // new tag for the stack
                        array_push($stack, $buffer);

                    // start seaching again
                    $buffer = NULL;
                } else {
                    $buffer .= $line[$i];
                }
            }
        }
        if (!empty($stack)) {
            $rev_stack = array();
            while ($tag = array_pop($stack)) {
                // first, close all open tags at the end of the line
                $tname = substr($tag, 0, strpos($tag, " "));
                if ($tname == "")
                    $tname = $tag;
                $lines[$n] .= "</$tname>";
                array_unshift($rev_stack, $tag);
            }
            while ($tag = array_pop($rev_stack)) {
                // second, re-open them at the start of the next line
                $lines[$n+1] = "<$tag>".$lines[$n+1];
            }
        }
        //$lines[$n] = "<div style=\"white-space:pre\">".$lines[$n]."</div>";
    }

    return $lines;
}



// vim: sts=4 expandtab

?>
