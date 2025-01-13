<?php
// This file is part of the customcert module for Moodle - http://moodle.org/
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
 * This file contains the customcert element mainsectionname's core interaction API.
 */

namespace customcertelement_programsummary;

use \format_diplomados\validation\ruleset\curso_level_certificates;

class element extends \mod_customcert\element {

    /**
     * Overriding the constructor to change the default reference point:
     * When unset, we want our ref point to be Topleft:
     */
    public function __construct($element) {
        $element->refpoint ??= 'L';
        parent::__construct($element);
    }

    /**
     * Handles rendering the element on the pdf.
     *
     * @param \pdf $pdf the pdf object
     * @param bool $preview true if it is a preview, false otherwise
     * @param \stdClass $user the user we are rendering this for
     */
    public function render($pdf, $preview, $user) {
        $html = $this->get_program_summary(preview: $preview);
        \mod_customcert\element_helper::render_content($pdf, $this, $html);
    }

    /**
     * Render the element in html.
     *
     * This function is used to render the element when we are using the
     * drag and drop interface to position it.
     */
    public function render_html() {
        $html = $this->get_program_summary(preview: True);
        return \mod_customcert\element_helper::render_html_content($this, $html);
    }

    /**
     * Generates the HTML for the program summary.
     *
     * If we are previewing, we _might_ not expect to have valid programs to pull from, so we will provide dummy data if none cannot be found.
     * Otherwise: we should log an error.
     *
     * @param bool $preview true if it is a preview, false otherwise
     * @return string
     */
    protected function get_program_summary(bool $preview = false) : string {
        global $OUTPUT;

        $courseid = \mod_customcert\element_helper::get_courseid($this->get_id());
        $course = get_course($courseid);
        $context = \mod_customcert\element_helper::get_context($this->get_id());
        $modinfo = get_fast_modinfo($course);

        $ruleset = new curso_level_certificates($course->id);
        $ruleset::get_validation_errors();
        $curso_section_numbers = curso_level_certificates::get_curso_sections();

        if($preview && empty($curso_section_numbers)) {
            $curso_names = ['curso 1', 'curso 2', 'curso 3'];
        }else {
            $curso_names = array_map(function($curso) use ($modinfo, $context) { 
                return format_string($modinfo->get_section_info($curso)->name, true, ['context' => $context]);
            }, $curso_section_numbers);
        }

        return $OUTPUT->render_from_template('customcertelement_programsummary/summary', (object) ['names' => $curso_names]);
    }
}
