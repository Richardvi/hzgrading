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

namespace local_hzgrading;

use core\hook\output\before_footer_html_generation;

/**
 * Hook callbacks for local_hzgrading.
 *
 * @package    local_hzgrading
 * @copyright  internetlab.nl
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {
    /**
     * Add the javascript to the footer.
     *
     * @param before_footer_html_generation $hook
     */
    public static function before_footer_html_generation(before_footer_html_generation $hook): void {
        global $PAGE;

        // Beperk de executie strikt tot de één-op-één beoordelingsinterface.
        if ($PAGE->pagetype === 'mod-assign-grader') {

            // Cmid is essentieel: de JS-module heeft dit nodig om de override
            // via de webservice (local_hzgrading_set_override) op te slaan,
            // los van welke student er op dat moment beoordeeld wordt.
            $cmid = $PAGE->cm->id ?? 0;

            $jsconfig = [
                'cmid' => $cmid,
                'courseid' => $PAGE->course->id ?? 0,
            ];

            $PAGE->requires->js_call_amd('local_hzgrading/hzgrading', 'init', [$jsconfig]);
        }
    }
}
