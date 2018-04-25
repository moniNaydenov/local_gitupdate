<?php

require_once(__DIR__ . '/../../config.php');

require_login();
if(!is_siteadmin()) {
    header('Location: ' . $CFG->wwwroot . '/index.php');
    exit;
}

define('HELPTEXT', "Possible actions (call as <cite>mcm_dev.php?action=CHOSENACTION</cite>):<br />
<ul>
    <li><b>clusteroff</b> deactivates all other nodes</li>
    <li><b>status</b> outputs status information about all nodes</li>
    <li><b>sync</b> copies data from current node to all others</li>
    <li><b>clusteron</b> activates all nodes again</li>
</ul>");

header( 'Content-type: text/html; charset=utf-8' );

// AJAX call handled here
if (isset($_GET['action'])) {
    switch ($_GET['action']) {
        case 'clusteroff':
            //$return = shell_exec('/opt/moodle/mcm-dev.sh -o0 other');
            break;
        case 'status':
            $return = shell_exec('/opt/moodle/mcm-dev.sh -l');
            break;
        case 'sync':
            //$return = shell_exec('/opt/moodle/mcm-dev.sh -s other');
            break;
        case 'clusteron':
            //$return = shell_exec('/opt/moodle/mcm-dev.sh -o1 all');
            break;
        default:
            $return = HELPTEXT;
    }
    echo convertToHTML($return);
    die;
} else {
    $return = shell_exec('/opt/moodle/mcm-dev.sh -l');
    echo "<section class='status'><h1>Current status:</h1><div>".convertToHTML($return)."</div></section>";
    echo "<section class='status'><h1>HELP</h1><div>".convertToHTML(HELPTEXT)."</div></section>";
}


function convertToHTML($input) {
    return str_replace(array(" ","\n", "\r", "\t"), array( '&nbsp;', '<br />', '<br />', '&nbsp;&nbsp;&nbsp;&nbsp;'), $input);//. '<br />';
}
