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

use mod_assign\event\submission_graded;

defined('MOODLE_INTERNAL') || die();

/**
 * Event observers voor local_hzgrading.
 *
 * @package    local_hzgrading
 * @copyright  internetlab.nl
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class observer {

    /**
     * Overschrijft het zojuist door mod_assign opgeslagen (rubric-)cijfer
     * met de eventueel opgeslagen ND/NB/handmatige override.
     *
     * Dit draait NA de standaard opslaglogica van mod_assign, dus het
     * rubric-cijfer staat op dit moment al vast in assign_grades. Wij
     * passen dat record hier aan en pushen het gewijzigde cijfer opnieuw
     * naar de Cijferlijst via de officiële assign_update_grades().
     *
     * @param submission_graded $event
     * @return void
     */
    public static function submission_graded(submission_graded $event): void {
        global $DB, $CFG;

        $userid = $event->relateduserid;
        $assignid = $event->other['assignid'] ?? null;

        if (empty($assignid) || empty($userid)) {
            return;
        }

        $override = $DB->get_record('local_hzgrading_override', [
            'assignid' => $assignid,
            'userid' => $userid,
        ]);

        // Geen actieve override -> gewoon het normale rubric-cijfer laten staan.
        if (!$override || $override->type === 'RESET' || $override->value === null) {
            return;
        }

        require_once($CFG->dirroot . '/mod/assign/locallib.php');
        require_once($CFG->dirroot . '/mod/assign/lib.php');

        // event->objectid is expliciet gedocumenteerd als het id van het
        // bijbehorende assign_grades-record (zie get_objectid_mapping()
        // in mod_assign\event\submission_graded), dus we kunnen die rij
        // direct en veilig bijwerken zonder opnieuw te hoeven opzoeken.
        $DB->set_field('assign_grades', 'grade', $override->value, ['id' => $event->objectid]);

        // Assign-instance opbouwen op de expliciete, versie-stabiele manier
        // (constructor: context, cm, course) i.p.v. te vertrouwen op een
        // eventuele shortcut-methode op het event-object.
        $cm = get_coursemodule_from_instance('assign', $assignid, 0, false, MUST_EXIST);
        $course = get_course($cm->course);
        $context = \context_module::instance($cm->id);
        $assign = new \assign($context, $cm, $course);

        // Duw het aangepaste cijfer door naar de Cijferlijst (gradebook).
        // Dit is dezelfde functie die mod_assign zelf gebruikt bij o.a.
        // reset/cron/herberekeningen, dus stabiel en toekomstbestendig.
        assign_update_grades($assign->get_instance(), $userid);
    }
}
