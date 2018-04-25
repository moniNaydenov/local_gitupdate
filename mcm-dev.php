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
            echo "<h1>Turning cluster off...";
            $return = shell_exec('/opt/moodle/mcm-dev.sh -o0 other');
            echo "finished</h1>";
            break;
        case 'status':
            echo "<h1>Fetching cluster status...</h1>";
            $return = shell_exec('/opt/moodle/mcm-dev.sh -l');
            echo "finished</h1>";
            break;
        case 'sync':
            echo "<h1>Syncing files (this could take a while)...</h1>";
            $return = shell_exec('/opt/moodle/mcm-dev.sh -s other');
            echo "finished</h1>";
            break;
        case 'clusteron':
            echo "<h1>Turning cluster on...";
            $return = shell_exec('/opt/moodle/mcm-dev.sh -o1 all');
            echo "finished</h1>";
            break;
        default:
            $return = HELPTEXT;
    }
    echo "<div>".convertToHTML($return)."</div>";
    die;
} else {
    $return = shell_exec('/opt/moodle/mcm-dev.sh -l');
    echo "<section class='status'><h1>Current status:</h1><div>".convertToHTML($return)."</div></section>";
    echo "<section class='status'><h1>HELP</h1><div>".convertToHTML(HELPTEXT)."</div></section>";
}


function convertToHTML($input) {
    return str_replace(array(" ","\n", "\r", "\t"), array( '&nbsp;', '<br />', '<br />', '&nbsp;&nbsp;&nbsp;&nbsp;'), $input);//. '<br />';
}
