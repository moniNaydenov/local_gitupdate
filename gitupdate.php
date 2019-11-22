<?php
$is_in_git = strpos(__DIR__, '.git') !== false;
if ($is_in_git) {
    require_once(__DIR__ . '/../config.php');
} else {
    require_once(__DIR__ . '/../../config.php');
}
require_login();
if(!is_siteadmin()) {
    header('Location: ' . $CFG->wwwroot . '/index.php');
	exit;
}

header( 'Content-type: text/html; charset=utf-8' );


$repos = array();
exec('find \'' . $CFG->dirroot . '\' -name .git -print', $repos);

$repos2 = array();
exec('find \'' . $CFG->dataroot . '/lang\' -name .git -print', $repos2);

$repos = array_merge($repos, $repos2);


// AJAX call handled here
if (isset($_GET['path']) && isset($_GET['branch']) && isset($_GET['type'])) {
    $path = base64_decode($_GET['path']);
    if (!in_array($path . '/.git', $repos)) {
        die('Invalid path supplied!');
    }

    $branch = $_GET['branch'];
    chdir($path);

    $branches_temp = explode("\n", shell_exec('git branch -a'));
    $branchfound = false;
    array_walk($branches_temp, function($elem) use ($branch, &$branchfound) {
        if (strpos($elem, $branch) !== false) {
            $branchfound = true;
        }
    });

    if (!$branchfound) {
        die('Invalid branch specified!');
    }

    if ($_GET['type'] == md5('current')) {
        echo $branch . '<br />';
        echo convertToHTML(shell_exec('git log -n 1 ' . $branch));
    } else if ($_GET['type'] == md5('next')) {
        echo 'origin/' . $branch . '<br />';
        $current_hash = shell_exec('git log -n 1 --format=format:%H ' . $branch);
        echo convertToHTML(shell_exec('git log ' . $current_hash . '..HEAD origin/' . $branch));
    }
    die;
}

// Gitupdate finished

if (isset($_SESSION['gitupdatefinished'])) {
    if (!$is_in_git) {
        unlink($CFG->dirroot . '/.git/gitupdate.php');
        unset($_SESSION['gitupdate']);
        unset($_SESSION['gitupdatefinished']);
    }
}

// Default page with list of repositories and branches
ob_implicit_flush(true);
if (empty($_POST) && !isset($_SESSION['gitupdate'])) {

    $repo_det = array();
    if(!empty($repos)) {
        foreach($repos as $repo_path) {
            $sub_path = substr($repo_path, 0, -5); //remove the  /.git from the path

            chdir($sub_path);

	        $fetch_res = shell_exec('git fetch -a') != ''?1:0; //retrieve all changes
            
            
                
            //get the name of the origin
            $origin_raw = shell_exec('git remote -v');

            $origin_raw = explode("\n", $origin_raw);
            $origin_raw = $origin_raw[0];
            $origin_raw = substr($origin_raw, strrpos($origin_raw, '/'));
            $origin_raw = substr($origin_raw, strrpos($origin_raw, ':')+1);
            $origin_name = trim(str_replace(array('(fetch)', '(push)', '.git'), '', $origin_raw));


            if (empty($origin_name)) {
                continue;
            }

            $repo_det[$origin_name]['url'] = $sub_path;
            //get the names of the remote branches
            $branches_temp = explode("\n", shell_exec('git branch -a'));
            $branches = array();
            foreach($branches_temp as $branch) {
                if(strpos($branch, 'remotes/origin/') !== false && strpos($branch, 'HEAD') === false) 
                    $branches[] = substr($branch, 17); //just clear remote branch name
                if(strpos($branch, '*') !== false) {
                    $repo_det[$origin_name]['currentbranch'] = substr($branch, 2);
                }
                
            }       
            $repo_det[$origin_name]['current'] = array('hash' => shell_exec('git log -n 1 --format=format:%H'), 'text' => convertToHTML(shell_exec('git log -n 1')));
            $repo_det[$origin_name]['author'] = shell_exec('git log -n 1 --format=format:%an');
            $repo_det[$origin_name]['origindiff'] = convertToHTML(shell_exec('git log ' . $repo_det[$origin_name]['current']['hash'] . '..HEAD origin/' . $repo_det[$origin_name]['currentbranch']));
           // $current_commit_ = shell_exec('git log -n 1 --format=format:%H');
                        
            $repo_det[$origin_name]['branches'] = $branches;

        }
        ksort($repo_det);
        echo <<<'HTMLPAGE'
        <html>
                <head>
            <title>git update</title>
            </head>
            <script type="text/javascript" language="javascript">
                function toggleCheckboxes(on) {
                    var checkboxes = document.getElementsByTagName("input");
                    for(var i = 0; i < checkboxes.length; i++) {
                        if (checkboxes[i].name == "selectedrepos[]") {
                            checkboxes[i].checked = on;
                        }
                    }
                }
                var repoCount = 0;
                function fixColors(branch) {
                    for (var i = 0; i < repoCount; i++) {
                        var elem = document.getElementById("branchtd" + i);
                        //repocheck, branch
                        if (branch == "please choose...") {
                            document.getElementById("repocheck" + i).checked = false;
                            elem.style.backgroundColor = "#FFFFFF";
                        } else {
                            var branches = document.getElementById("branch" + i);                        
                            var incl = false;
                            var j = 0;
                            for (j = 0; j < branches.options.length; j++) {
                                if (branches.options[j].text == branch) {
                                    incl = true;
                                    break;
                                }
                            }
                            if (incl) {
                                elem.style.backgroundColor = elem.innerHTML == branch ? "#33CC33":"#6699FF";
                                branches.selectedIndex = j;
                                branches.onchange();
                                document.getElementById("repocheck" + i).checked = true;
                            } else {
                                document.getElementById("repocheck" + i).checked = false;
                                elem.style.backgroundColor = "#FF6600";
                            }
                        }
                    }
                }
                
                function fillBranches(branches) {
                    var sel = document.getElementById("all_branches");
                    for (i = 0; i < branches.length; i++) {
                        var branch = new Option();
                        branch.value = branches[i];
                        branch.text = branches[i];
                        sel.options.add(branch);
                    }
                }
                
                function toggleFullSize(elem, link) {
                    elem.className = elem.className == "compact" ? "full" : "compact";
                    link.innerHTML = elem.className == "compact" ? "show all" : "hide";
                }
                
                function getStatus(divid, path, branch, type) {
                    var xmlhttp;
                    if (window.XMLHttpRequest) {
                        // code for IE7+, Firefox, Chrome, Opera, Safari
                        xmlhttp=new XMLHttpRequest();
                    } else {
                        // code for IE6, IE5
                        xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
                    }
                    xmlhttp.onreadystatechange=function() {
                        if (xmlhttp.readyState==4 && xmlhttp.status==200) {
                            document.getElementById(divid).innerHTML=xmlhttp.responseText;
                        }
                    }
                    document.getElementById(divid).innerHTML = "loading...";
                    xmlhttp.open("GET","gitupdate.php?path=" + path + "&branch=" + branch + "&type=" + type ,true);                    
                    xmlhttp.send();                    
                }

                function validateRepositories() {
                    var inputs = document.getElementsByTagName("input");
                    var repos = "";
                    for(var i = 0; i < inputs.length; i++) {
                        var input = inputs[i];
                        if (input.type != "checkbox") {
                            continue;
                        }
                        if (input.checked) {
                            repos += input.getAttribute("data-title") + "\n";
                        }
                    }
                    return confirm("Are you sure you want to update these repositories:\n"+repos);
                }
            </script>
            <style type="text/css">
                html, body, table, td, tr, th {
                    font-family: Helvetica, Arial, sans-serif;
                    font-size: 11px;
                }
                a {
                    text-decoration: none;
                    font-size: 11px;
                }
                td>div {
                    font-family: "Courier New", Courier, monospace;
                    font-size: 12px;
                }
                .compact {                    
                    overflow: hidden;
                    width: 300px;
                    height: 68px;
                }                
                .full {                    
                    overflow: visible;
                    min-width: 300px;
                    min-height: 68px;
                    width: auto;
                    height: auto;                    
                }
                table {
                    border: 1px solid #555;
                    border-collapse: collapse;
                    margin-left: auto;
                    margin-right: auto;
                }
            </style>
            <body>
            <form method="POST" enctype="multipart/form-data" onsubmit="return validateRepositories();">
            <table border="0" cellspacing="0" cellpadding="4">
                <tr>
                    <th colspan="6">git update</th>
                </tr>
                <tr>
                    <td>Check branch consistency:</td><td colspan="5"><select id="all_branches" onchange="fixColors(this.value);"><option selected="selected">please choose...</option></select>
                </tr>
                <tr>
                    <td colspan="6">&nbsp;</td>
                </tr>
                <tr style="border: 1px solid #555; border-collapse: collapse;">
                    <td style="border: 1px solid #555; border-collapse: collapse;"><input type="checkbox" value="" onchange="javascript: toggleCheckboxes(this.checked);" name="toggle_all"/></td>
                    <th style="border: 1px solid #555; border-collapse: collapse;">repository</th>
                    <th style="border: 1px solid #555; border-collapse: collapse;">branch to update</th>
                    <th style="border: 1px solid #555; border-collapse: collapse;">current branch</th>
                    <th style="border: 1px solid #555; border-collapse: collapse;">last changes by</th>
                    <th style="border: 1px solid #555; border-collapse: collapse;">current status</th>
                    <th style="border: 1px solid #555; border-collapse: collapse;">next</th>
                </tr>
HTMLPAGE;
        $i = 0;
        $unique_branches = array();
        foreach($repo_det as $repo_name => $repo_dets) { 
            echo '<tr style="border: 1px solid #555; border-collapse: collapse;">';
            echo '<td style="border: 1px solid #555; border-collapse: collapse;"><input type="checkbox" data-title="' . $repo_name . '" value="' . $i . '" name="selectedrepos[]" id="repocheck' . $i . '"/></td>';
            echo '<td style="border: 1px solid #555; border-collapse: collapse;">' . $repo_name . '<input type="hidden" value="' . $repo_dets['url'] . '" name="url' . $i . '" /><input type="hidden" value="' . $repo_name . '" name="name' . $i . '" /></td>';
            echo '<td style="border: 1px solid #555; border-collapse: collapse;">
            <select name="branch' . $i . '" id="branch' . $i . '" onchange="javascript: getStatus(\'current' . $i . '\', \'' . base64_encode($repo_dets['url']) . '\', this.value, \'' . md5('current') . '\');getStatus(\'next' . $i . '\', \'' . base64_encode($repo_dets['url']) . '\', this.value, \'' . md5('next') . '\');">';
            foreach ($repo_dets['branches'] as $branch) {
                echo '<option value="' . $branch . '" ' . ($repo_dets['currentbranch'] == $branch ? 'selected="selected"':'') . '>' . $branch . '</option>';
                if (!in_array($branch, $unique_branches)) {
                    array_push($unique_branches, $branch);
                }
            }
            echo '</select>';
            echo '</td>';
            echo '<td style="border: 1px solid #555; border-collapse: collapse;" id="branchtd' . $i . '">' . $repo_dets['currentbranch'] . '</td>';
            echo '<td style="border: 1px solid #555; border-collapse: collapse;">' . $repo_dets['author'] . '</td>';
            echo '<td style="border: 1px solid #555; border-collapse: collapse;"><div class="compact" id="current' . $i . '">' . $repo_dets['currentbranch'] . '<br />'. $repo_dets['current']['text'] . '</div><a href="javascript: void(0);" onclick="javascript: toggleFullSize(this.previousSibling , this);">show all</a></td>';
            echo '<td style="border: 1px solid #555; border-collapse: collapse;"><div class="compact" id="next' . $i . '">origin/' . $repo_dets['currentbranch'] . '<br />'. $repo_dets['origindiff'] .  '</div><a href="javascript: void(0);" onclick="javascript: toggleFullSize(this.previousSibling , this);">show all</a></td>';
            echo '</tr>';
            $i++;
        }
        echo '
                <tr>
                    <td colspan="4">&nbsp;</td>
                </tr>
                <tr>
                    <th colspan="4">
                        <input type="submit" value="Update selected repositories" />
                    </th>
                </tr>
            </table>
            </form>';
        sort($unique_branches);
        echo '<script type="text/javascript">repoCount = ' . $i . '; fillBranches(["' . implode('", "', $unique_branches) . '"])</script>';
        echo '</body>
            </html>';
    }
} else {
    // data has been sent to POST or stored in session
    if (isset($_SESSION['gitupdate'])) {
        $_POST = $_SESSION['gitupdate'];
        echo '
        <html>
        <head>
            <title>git update: completed</title>
        </head>
        <body style="font-family: \'Courier New\', Courier, monospace;">';
        if (empty($_POST['selectedrepos'])) {
            echo 'Please, select repositories to update!';
        } else {
            foreach($_POST['selectedrepos'] as $repoid) {

                echo '<span style="font-weight: bold;">' . $_POST['name'.$repoid] . ':</span><br /><br />';

                $path = $_POST['url'.$repoid];
                if (!in_array($path . '/.git', $repos)) {
                    echo 'Invalid path supplied!';
                    continue;
                }

                $branch = $_POST['branch'.$repoid];
                chdir($path);

                $branches_temp = explode("\n", shell_exec('git branch -a'));
                $branchfound = false;
                array_walk($branches_temp, function($elem) use ($branch, &$branchfound) {
                    if (strpos($elem, $branch) !== false) {
                        $branchfound = true;
                    }
                });

                if (!$branchfound) {
                    echo 'Invalid branch specified!';
                    continue;
                }

                //resets all files
                echo 'Performing git reset...<br />';
                echo convertToHTML(shell_exec('git reset --hard ' . shell_exec('git log -n 1 --format=format:%H')));
                echo 'done. <br />';
                //fetches new info
                echo 'Fetching new data from server...<br />';
                echo convertToHTML(shell_exec('git fetch -a'));
                echo 'done. <br />';
                //checkout a new branch with a custom name
                echo 'Checking out a temporary random branch...<br />';
                echo convertToHTML(shell_exec('git checkout -b some_random_branch_name_tu'));
                echo 'done. <br />';
                //delete the selected branch if it exists
                echo 'Deleting old branch...<br />';
                echo convertToHTML(shell_exec('git branch -D ' . $_POST['branch'.$repoid]));
                echo 'done.<br />';
                //create the selected branch again, this time using the new fetched version
                echo 'Checking out the newly fetched branch...<br />';
                echo convertToHTML(shell_exec('git checkout ' .  $_POST['branch'.$repoid]));
                //delete the custom branch
                
                echo convertToHTML(shell_exec('git branch -D some_random_branch_name_tu'));

                echo 'Current:<br /> ';
                echo '<em>' . convertToHTML(shell_exec('git log -n 1 --format=medium')) . '</em><br />';

                echo 'done.<br />';

            }
        }
        echo '<input type="button" value="back" onclick="javascript: window.location = \'' . $CFG->wwwroot . '/local/gitupdate/gitupdate.php\'" style="width: 100px;"/>';
        echo '<input type="button" value="to admin page" onclick="javascript: window.location = \'' . $CFG->wwwroot . '/admin\'" style="width: 100px;"/>';
        echo '</body>
        </html>';
        $_SESSION['gitupdatefinished'] = true;
    } else {
        $_SESSION['gitupdate'] = $_POST; 
        $gitupdate = file_get_contents(__FILE__);
        file_put_contents($CFG->dirroot . '/.git/gitupdate.php', $gitupdate);
        header('location: ' . $CFG->wwwroot . '/.git/gitupdate.php');
    }
}


function convertToHTML($input) {
	return str_replace(array(" ","\n", "\r", "\t"), array( '&nbsp;', '<br />', '<br />', '&nbsp;&nbsp;&nbsp;&nbsp;'), $input);//. '<br />';
}
