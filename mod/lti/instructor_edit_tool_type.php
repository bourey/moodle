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
 * MRTODO: Brief description of this file
 *
 * @package    mod
 * @subpackage lti
 * @copyright  2011 onwards MRTODO
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot.'/mod/lti/edit_form.php');

$courseid = required_param('course', PARAM_INT);

require_login($courseid, false);
$url = new moodle_url('/mod/lti/instructor_edit_tool_type.php');
$PAGE->set_url($url);
$PAGE->set_pagelayout('popup');

$action = optional_param('action', null, PARAM_TEXT);
$typeid = optional_param('typeid', null, PARAM_INT);

require_capability('mod/lti:addcoursetool', get_context_instance(CONTEXT_COURSE, $courseid));

if (!empty($typeid)) {
    $type = lti_get_type($typeid);
    if ($type->course != $courseid) {
        throw new Exception('You do not have permissions to edit this tool type.');
        die;
    }
}

$data = data_submitted();

if (isset($data->submitbutton) && confirm_sesskey()) {
    $type = new stdClass();

    if (!empty($typeid)) {
        $type->id = $typeid;
        $name = json_encode($data->lti_typename);

        lti_update_type($type, $data);

        $fromdb = lti_get_type($typeid);
        $json = json_encode($fromdb);

        //Output script to update the calling window.
        $script = "
            <html>
                <script type=\"text/javascript\">
                    window.opener.M.mod_lti.editor.updateToolType({$json});
                    window.close();
                </script>
            </html>
        ";

        echo $script;
        die;
    } else {
        $type->state = LTI_TOOL_STATE_CONFIGURED;
        $type->course = $COURSE->id;

        $id = lti_add_type($type, $data);

        $fromdb = lti_get_type($id);
        $json = json_encode($fromdb);

        //Output script to update the calling window.
        $script = "
            <html>
                <script type=\"text/javascript\">
                    window.opener.M.mod_lti.editor.addToolType({$json});
                    window.close();
                </script>
            </html>
        ";

        echo $script;

        die;
    }
} else if (isset($data->cancel)) {
    $script = "
        <html>
            <script type=\"text/javascript\">
                window.close();
            </script>
        </html>
    ";

    echo $script;
    die;
}

//Delete action is called via ajax
if ($action == 'delete') {
    lti_delete_type($typeid);
    die;
}

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('toolsetup', 'lti'));

if ($action == 'add') {
    $form = new mod_lti_edit_types_form();
    $form->display();
} else if ($action == 'edit') {
    $form = new mod_lti_edit_types_form();
    $type = lti_get_type_type_config($typeid);
    $form->set_data($type);
    $form->display();
}

echo $OUTPUT->footer();
