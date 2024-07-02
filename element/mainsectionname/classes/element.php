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

namespace customcertelement_mainsectionname;

defined('MOODLE_INTERNAL') || die();


class element extends \mod_customcert\element {

    /**
     * This function renders the form elements when adding a customcert element.
     *
     * @param \MoodleQuickForm $mform the edit form instance
     */
    public function render_form_elements($mform) {
        global $DB, $COURSE;
        // Get the course main sections.
        $arrmainsections = [];

        $sql = "SELECT cs.id, cs.name
                FROM {course_format_options} as fo
                JOIN {course_sections} cs ON
                    fo.courseid = cs.course
                    AND fo.value = cs.id
                where cs.course = :course and fo.name = 'mainsectionid' 
                        and visible=1 and fo.value > 0
                group by cs.section";
        $params = ['course' => $COURSE->id];

        // get records
        $sections = $DB->get_records_sql($sql, $params);

        foreach ($sections as $section) {
            $arrmainsections[$section->id] = $section->name;
        }

        // Create the select box where the user field is selected.
        $mform->addElement('select', 'mainsectionname', get_string('mainsectionname', 'customcertelement_mainsectionname'), $arrmainsections);
        $mform->setType('mainsectionname', PARAM_ALPHANUM);
        $mform->addHelpButton('mainsectionname', 'mainsectionname', 'customcertelement_mainsectionname');

        parent::render_form_elements($mform);
    }

    /**
     * This will handle how form data will be saved into the data column in the
     * customcert_elements table.
     *
     * @param \stdClass $data the form data
     * @return string the text
     */
    public function save_unique_data($data) {
        return $data->mainsectionname;
    }

    /**
     * Handles rendering the element on the pdf.
     *
     * @param \pdf $pdf the pdf object
     * @param bool $preview true if it is a preview, false otherwise
     * @param \stdClass $user the user we are rendering this for
     */
    public function render($pdf, $preview, $user) {

        $courseid = \mod_customcert\element_helper::get_courseid($this->id);
        $course = get_course($courseid);

        \mod_customcert\element_helper::render_content($pdf, $this, $this->get_mainsectionname_value());
    }

    /**
     * Render the element in html.
     *
     * This function is used to render the element when we are using the
     * drag and drop interface to position it.
     */
    public function render_html() {
        global $COURSE;

        return \mod_customcert\element_helper::render_html_content($this, $this->get_mainsectionname_value());
    }

    /**
     * Sets the data on the form when editing an element.
     *
     * @param \MoodleQuickForm $mform the edit form instance
     */
    public function definition_after_data($mform) {
        if (!empty($this->get_data())) {
            $element = $mform->getElement('mainsectionname');
            $element->setValue($this->get_data());
        }
        parent::definition_after_data($mform);
    }

    /**
     * Helper function that returns the field value in a human-readable format.
     *
     * @param \stdClass $course the course we are rendering this for
     * @param bool $preview Is this a preview?
     * @return string
     */
    protected function get_mainsectionname_value() : string {
        global $DB;
        // The user field to display.
        $field = $this->get_data();
        $value = '';
        if (is_number($field)) { // Must be a custom course profile field.
            if ($field = $DB->get_record('course_sections', array('id' => $field))) {
                // Found the field name, let's update the value to display.
                $value = $field->name;
            }
        }

        $context = \mod_customcert\element_helper::get_context($this->get_id());
        return format_string($value, true, ['context' => $context]);
    }
}
