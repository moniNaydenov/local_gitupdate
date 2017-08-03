<?php

/**
 *
 * @package       moodle33
 * @author        Simeon Naydenov (moniNaydenov@gmail.com)
 * @copyright     2017
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


function local_gitupdate_extend_navigation(global_navigation $nav) {
    global $PAGE, $OUTPUT;

    if (is_siteadmin() !== true) {
        return;
    }
    $title = get_string('opengitupdate', 'local_gitupdate');
    $url = new \moodle_url('/local/gitupdate/gitupdate.php');

    $node = $nav->add(
        $title,
        $url,
        navigation_node::TYPE_CUSTOM,
        'gitupdate',
        'gitupdate',
        null
    );

    $node->nodetype = navigation_node::NODETYPE_LEAF;
    $node->showinflatnavigation = true;
    $node->collapse = true;
}