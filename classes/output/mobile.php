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
 * Contains the mobile output class for the custom certificate.
 *
 * @package   mod_customcert
 * @copyright 2018 Mark Nelson <markn@moodle.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_customcert\output;

defined('MOODLE_INTERNAL') || die();

/**
 * Mobile output class for the custom certificate.
 *
 * @copyright  2018 Mark Nelson <markn@moodle.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mobile {

    /**
     * Returns the initial page when viewing the activity for the mobile app.
     *
     * @param  array $args Arguments from tool_mobile_get_content WS
     * @return array HTML, javascript and other data
     */
    public static function mobile_view_activity($args) {
        global $OUTPUT, $DB, $USER;

        $args = (object) $args;

        // Get the group variable.
        $groupid = empty($args->group) ? 0 : $args->group; // By default, group 0.
        $cmid = $args->cmid;

        // Capabilities check.
        $cm = get_coursemodule_from_id('customcert', $cmid);
        $context = \context_module::instance($cm->id);
        self::require_capability($cm, $context, 'mod/customcert:view');

        // Set some variables we are going to be using.
        $certificate = $DB->get_record('customcert', ['id' => $cm->instance], '*', MUST_EXIST);
        $certificate->name = format_string($certificate->name);
        list($certificate->intro, $certificate->introformat) = external_format_text($certificate->intro,
            $certificate->introformat, $context->id, 'mod_customcert', 'intro');

        // Get the groups (if any) to display - also sets active group.
        $groups = self::get_groups($cm, $groupid, $USER->id);

        // Get any issues this person may have.
        $issues = $DB->get_records('customcert_issues', ['userid' => $USER->id, 'customcertid' => $certificate->id]);

        $candownload = true;
        if ($certificate->requiredtime && !has_capability('mod/certificate:manage', $context)) {
            if (\mod_customcert\certificate::get_course_time($certificate->course) < ($certificate->requiredtime * 60)) {
                $candownload = false;
            }
        }

        $fileurl = "";
        if ($candownload) {
            $fileurl = new \moodle_url('/mod/customcert/mobile/pluginfile.php', ['certificateid' => $certificate->id,
                'userid' => $USER->id]);
            $fileurl = $fileurl->out(true);
        }

        $showreport = false;
        $numissues = 0;
        if (has_capability('mod/customcert:viewreport', $context)) {
            // Get the total number of issues.
            $showreport = true;
            $groupmode = groups_get_activity_groupmode($cm);
            if (has_capability('moodle/site:accessallgroups', $context)) {
                $groupmode = 'aag';
            }
            $numissues = \mod_customcert\certificate::get_number_of_issues($certificate->id, $cm, $groupmode);
        }

        $data = [
            'certificate' => $certificate,
            'cmid' => $cm->id,
            'groupselected' => $groupid,
            'showgroups' => !empty($groups),
            'groups' => array_values($groups),
            'hasissues' => !empty($issues),
            'issues' => array_values($issues),
            'candownload' => $candownload,
            'fileurl' => $fileurl,
            'showreport' => $showreport,
            'numissuesinreport' => $numissues,
            'currenttimestamp' => time()
        ];

        return [
            'templates' => [
                [
                    'id' => 'main',
                    'html' => $OUTPUT->render_from_template('mod_customcert/mobile_view_activity_page', $data),
                ],
            ],
            'javascript' => '',
            'otherdata' => ''
        ];
    }

    /**
     * Returns the list of issues certificates for the activity for the mobile app.
     *
     * @param  array $args Arguments from tool_mobile_get_content WS
     * @return array HTML, javascript and other data
     */
    public static function mobile_view_report($args) {
        global $DB, $OUTPUT, $USER;

        $args = (object) $args;

        $cmid = $args->cmid;
        $groupid = empty($args->group) ? 0 : $args->group; // By default, group 0.

        // Capabilities check.
        $cm = get_coursemodule_from_id('customcert', $cmid);
        $context = \context_module::instance($cm->id);

        self::require_capability($cm, $context, 'mod/customcert:viewreport');

        // Get the groups (if any) to display - also sets active group.
        $groups = self::get_groups($cm, $groupid, $USER->id);

        $certificate = $DB->get_record('customcert', ['id' => $cm->instance], '*', MUST_EXIST);
        $certificate->name = format_string($certificate->name);
        list($certificate->intro, $certificate->introformat) = external_format_text($certificate->intro,
            $certificate->introformat, $context->id, 'mod_customcert', 'intro');

        $groupmode = groups_get_activity_groupmode($cm);
        if (has_capability('moodle/site:accessallgroups', $context)) {
            $groupmode = 'aag';
        }

        $issues = \mod_customcert\certificate::get_issues($certificate->id, $groupmode, $cm, 0, 0);
        foreach ($issues as $issue) {
            $issue->displayname = fullname($issue);
            $issue->fileurl = new \moodle_url('/mod/customcert/mobile/pluginfile.php', ['certificateid' => $certificate->id,
                'userid' => $issue->id]);
        }

        $data = [
            'certificate' => $certificate,
            'cmid' => $cmid,
            'showgroups' => !empty($groups),
            'groups' => array_values($groups),
            'canmanage' => has_capability('mod/customcert:manage', $context),
            'hasissues' => !empty($issues),
            'issues' => array_values($issues),
            'currenttimestamp' => time()
        ];

        return [
            'templates' => [
                [
                    'id' => 'main',
                    'html' => $OUTPUT->render_from_template('mod_customcert/mobile_report_page', $data),
                ],
            ],
            'javascript' => '',
            'otherdata' => ''
        ];
    }

    /**
     * Returns an array of groups to be displayed (if applicable) for the activity.
     *
     * The groups API is a mess hence the hackiness.
     *
     * @param \stdClass $cm The course module
     * @param int $groupid The group id
     * @param int $userid The user id
     * @return array The array of groups, may be empty.
     */
    protected static function get_groups($cm, $groupid, $userid) {
        $arrgroups = [];
        if ($groupmode = groups_get_activity_groupmode($cm)) {
            if ($groups = groups_get_activity_allowed_groups($cm, $userid)) {
                $context = \context_module::instance($cm->id);
                if ($groupmode == VISIBLEGROUPS || has_capability('moodle/site:accessallgroups', $context)) {
                    $allparticipants = new \stdClass();
                    $allparticipants->id = 0;
                    $allparticipants->name = get_string('allparticipants');
                    $allparticipants->selected = $groupid === 0;
                    $arrgroups[0] = $allparticipants;
                }
                self::update_active_group($groupmode, $groupid, $groups, $cm);
                // Detect which group is selected.
                foreach ($groups as $gid => $group) {
                    $group->selected = $gid == $groupid;
                    $arrgroups[] = $group;
                }
            }
        }

        return $arrgroups;
    }

    /**
     * Update the active group in the session.
     *
     * This is a hack. We can't call groups_get_activity_group to update the active group as it relies
     * on optional_param('group' .. which we won't have when using the mobile app.
     *
     * @param int $groupmode The group mode we are in, eg. NOGROUPS, VISIBLEGROUPS
     * @param int $groupid The id of the group that has been selected
     * @param array $allowedgroups The allowed groups this user can access
     * @param \stdClass $cm The course module
     */
    private static function update_active_group($groupmode, $groupid, $allowedgroups, $cm) {
        global $SESSION;

        $context = \context_module::instance($cm->id);

        if (has_capability('moodle/site:accessallgroups', $context)) {
            $groupmode = 'aag';
        }

        if ($groupid == 0) {
            // The groups are only all visible in VISIBLEGROUPS mode or if the user can access all groups.
            if ($groupmode == VISIBLEGROUPS || has_capability('moodle/site:accessallgroups', $context)) {
                $SESSION->activegroup[$cm->course][$groupmode][$cm->groupingid] = 0;
            }
        } else {
            if ($allowedgroups && array_key_exists($groupid, $allowedgroups)) {
                $SESSION->activegroup[$cm->course][$groupmode][$cm->groupingid] = $groupid;
            }
        }
    }

    /**
     * Confirms the user is logged in and has the specified capability.
     *
     * @param \stdClass $cm
     * @param \context $context
     * @param string $cap
     */
    protected static function require_capability(\stdClass $cm, \context $context, string $cap) {
        require_login($cm->course, false, $cm, true, true);
        require_capability($cap, $context);
    }
}