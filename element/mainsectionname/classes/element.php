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

use \format_diplomados\validation\ruleset\curso_level_certificates;

class element extends \mod_customcert\element {

    /**
     * Handles rendering the element on the pdf.
     *
     * @param \pdf $pdf the pdf object
     * @param bool $preview true if it is a preview, false otherwise
     * @param \stdClass $user the user we are rendering this for
     */
    public function render($pdf, $preview, $user) {

        if($preview) {
            \mod_customcert\element_helper::render_content($pdf, $this, "Main Section Name");
        }else{
            $courseid = \mod_customcert\element_helper::get_courseid($this->id);
            $course = get_course($courseid);
            \mod_customcert\element_helper::render_content($pdf, $this, $this->get_mainsectionname_value());
        }
    }

    /**
     * Render the element in html.
     *
     * This function is used to render the element when we are using the
     * drag and drop interface to position it.
     */
    public function render_html() {
  
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
       
        $value = '';
        
        $courseid = \mod_customcert\element_helper::get_courseid($this->get_id());
        $course = get_course($courseid);
        $context = \mod_customcert\element_helper::get_context($this->get_id());
        $modinfo = get_fast_modinfo($course);

        $sectiondata = array();
        
        // Get the page.
        $page = $DB->get_record('customcert_pages', array('id' => $this->get_pageid()), '*', MUST_EXIST);
        // Get the customcert this page belongs to.
        $customcert = $DB->get_record('customcert', array('templateid' => $page->templateid), '*', MUST_EXIST);

        $ruleset = new curso_level_certificates($course->id);
        $ruleset::get_validation_errors();
        $cursoSections = curso_level_certificates::$cursoSections;
        $sectioninfo = $modinfo->get_section_info_all();
        

        foreach ($cursoSections as $cursoSection) {
            [$spanish_cert, $english_cert] = $this->get_curso_certificates($cursoSection, $course);

            $sectiondata[$spanish_cert->instance]['sectionname'] = $sectioninfo[$cursoSection]->name;
            $sectiondata[$english_cert->instance]['sectionname'] = $sectioninfo[$cursoSection]->name;
            
        }

        $value = $sectiondata[$customcert->id]['sectionname'] ?? '';

        return format_string($value, true, ['context' => $context]);
    }

    public function get_curso_certificates(int $sectionNumber, $course) : array {
        $certificates = [null, null];
        $modinfo = get_fast_modinfo($course);

        $sectionMapping = curso_level_certificates::get_ep_section_to_curso_section_mapping();

        $epSectionNumber = array_search($sectionNumber, $sectionMapping);

        if (!$epSectionNumber) {
            return $certificates;
        }

        foreach ($modinfo->sections[$epSectionNumber] as $cmid) {
            $cm = $modinfo->cms[$cmid];
            if ($cm->modname === 'customcert') {
                if (is_null($certificates[0])) {
                    $certificates[0] = $cm;
                } else if (is_null($certificates[1])) {
                    $certificates[1] = $cm;
                } else {
                    break;
                }
            }
        }

        return $certificates;
    }
}
