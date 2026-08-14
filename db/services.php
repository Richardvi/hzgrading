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
 * External services voor local_hzgrading.
 *
 * @package    local_hzgrading
 * @copyright  internetlab.nl
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_hzgrading_set_override' => [
        'classname'     => \local_hzgrading\external\set_override::class,
        'methodname'    => 'execute',
        'description'   => 'Slaat een grading-override (ND, NB of handmatig cijfer) op voor een ' .
            'assignment-inzending, zodat deze na het opslaan van het rubric-cijfer alsnog wordt toegepast.',
        'type'          => 'write',
        'ajax'          => true,
        'capabilities'  => 'mod/assign:grade',
    ],
];
