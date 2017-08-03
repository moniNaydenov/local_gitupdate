<?php
// This file is part of local_settingsplus for Moodle - http://moodle.org/
//
// It is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// It is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// If not, see <http://www.gnu.org/licenses/>.

/**
 * Version file
 *
 * @package       local_gitupdate
 * @author        Simeon Naydenov (moniNaydenov@gmail.com)
 * @copyright     2014 Academic Moodle Cooperation {@link http://www.academic-moodle-cooperation.org}
 * @license       http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$plugin->version   = 2017080300;
$plugin->release   = "v3.3.0";       // User-friendly version number
$plugin->requires  = 2014041100;      // Requires this Moodle version!
$plugin->component = 'local_gitupdate';    // To check on upgrade, that module sits in correct place.
