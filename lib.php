<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Library file for local_hzgrading.
 *
 * LET OP: de legacy callback local_hzgrading_before_footer() is bewust
 * verwijderd. Sinds de Hooks API (Moodle 4.4+) wordt de legacy
 * before_footer-callback automatisch genegeerd zodra er een hook-callback
 * bestaat voor core\hook\output\before_footer_html_generation (zie
 * db/hooks.php en classes/hook_callbacks.php). Beide laten staan levert
 * geen bug op, maar wel verwarrende dubbele code.
 *
 * @package    local_hzgrading
 * @copyright  internetlab.nl
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
