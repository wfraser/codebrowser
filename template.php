<?php

/**
 * @file
 * UI Template Functions.
 */

/**
 * Run a template, returning its output.
 * Uses the $UI global variable.
 *
 * @param $name Name of the template file in the \p ui/ directory to run.
 * @return String result of the template.
 */
function template($__TEMPLATE_NAME)
{
    global $UI;

    if (!file_exists("ui/$__TEMPLATE_NAME.phtml")) {
        die("template error -- section not found: \"$__TEMPLATE_NAME\"");
    }

    extract($UI[$__TEMPLATE_NAME], EXTR_SKIP);
    ob_start();
    include("ui/$__TEMPLATE_NAME.phtml");
    return ob_get_clean();
}

function message($title, $msg)
{
    die("<html><body><h1>$title</h1><p>$msg</p></body></html>");
}

?>
