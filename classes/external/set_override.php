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

namespace local_hzgrading\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use context_module;
use invalid_parameter_exception;
use stdClass;

/**
 * Slaat een grading-override (ND, NB of handmatig cijfer) op voor een assignment-inzending.
 *
 * Dit gebeurt volledig los van het rubric-formulier van Moodle zelf. De
 * daadwerkelijke toepassing op het cijfer gebeurt in
 * \local_hzgrading\observer::submission_graded(), nadat Moodle het
 * rubric-cijfer heeft opgeslagen.
 *
 * @package    local_hzgrading
 * @copyright  internetlab.nl
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class set_override extends external_api {

    /** @var string[] Toegestane override-types. */
    const ALLOWED_TYPES = ['ND', 'NB', 'MANUAL', 'RESET'];

    /**
     * Parameterdefinitie.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module id van de assignment'),
            'userid' => new external_value(PARAM_INT, 'Id van de student die beoordeeld wordt'),
            'type' => new external_value(PARAM_ALPHA, 'ND, NB, MANUAL of RESET'),
            'value' => new external_value(
                PARAM_RAW,
                'Het override-cijfer als string (bv. "7.5"), leeg bij RESET',
                VALUE_DEFAULT,
                ''
            ),
        ]);
    }

    /**
     * Slaat de override op (insert of update).
     *
     * @param int $cmid
     * @param int $userid
     * @param string $type
     * @param string $value
     * @return array
     */
    public static function execute(int $cmid, int $userid, string $type, string $value = ''): array {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'userid' => $userid,
            'type' => $type,
            'value' => $value,
        ]);

        $cm = get_coursemodule_from_id('assign', $params['cmid'], 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);

        // Standaardvalidatie: klopt de context, mag deze gebruiker hier iets doen.
        self::validate_context($context);
        require_capability('mod/assign:grade', $context);

        $type = strtoupper($params['type']);
        if (!in_array($type, self::ALLOWED_TYPES, true)) {
            throw new invalid_parameter_exception('Ongeldig override type: ' . $type);
        }

        // Bepaal de numerieke waarde. ND/NB hebben een vaste waarde; die
        // wordt hier serverside afgedwongen en dus NOOIT vertrouwd vanuit de client,
        // ook al stuurt de JS 'm netjes mee.
        switch ($type) {
            case 'ND':
                $value = 0.1;
                break;
            case 'NB':
                $value = 0.2;
                break;
            case 'RESET':
                $value = null;
                break;
            case 'MANUAL':
                $raw = str_replace(',', '.', $params['value']);
                if ($raw === '' || !is_numeric($raw)) {
                    throw new invalid_parameter_exception('Ongeldige of lege waarde bij MANUAL override');
                }
                $value = (float) $raw;
                break;
        }

        $existing = $DB->get_record('local_hzgrading_override', [
            'assignid' => $cm->instance,
            'userid' => $params['userid'],
        ]);

        $record = new stdClass();
        $record->assignid = $cm->instance;
        $record->userid = $params['userid'];
        $record->graderid = $USER->id;
        $record->type = $type;
        $record->value = $value;
        $record->timemodified = time();

        if ($existing) {
            $record->id = $existing->id;
            $DB->update_record('local_hzgrading_override', $record);
        } else {
            $DB->insert_record('local_hzgrading_override', $record);
        }

        return ['success' => true];
    }

    /**
     * Returnwaarde-definitie.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Of de override is opgeslagen'),
        ]);
    }
}
