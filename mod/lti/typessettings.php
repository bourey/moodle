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
//
// This file is part of BasicLTI4Moodle
//
// BasicLTI4Moodle is an IMS BasicLTI (Basic Learning Tools for Interoperability)
// consumer for Moodle 1.9 and Moodle 2.0. BasicLTI is a IMS Standard that allows web
// based learning tools to be easily integrated in LMS as native ones. The IMS BasicLTI
// specification is part of the IMS standard Common Cartridge 1.1 Sakai and other main LMS
// are already supporting or going to support BasicLTI. This project Implements the consumer
// for Moodle. Moodle is a Free Open source Learning Management System by Martin Dougiamas.
// BasicLTI4Moodle is a project iniciated and leaded by Ludo(Marc Alier) and Jordi Piguillem
// at the GESSI research group at UPC.
// SimpleLTI consumer for Moodle is an implementation of the early specification of LTI
// by Charles Severance (Dr Chuck) htp://dr-chuck.com , developed by Jordi Piguillem in a
// Google Summer of Code 2008 project co-mentored by Charles Severance and Marc Alier.
//
// BasicLTI4Moodle is copyright 2009 by Marc Alier Forment, Jordi Piguillem and Nikolas Galanis
// of the Universitat Politecnica de Catalunya http://www.upc.edu
// Contact info: Marc Alier Forment granludo @ gmail.com or marc.alier @ upc.edu

/**
 * This file contains the script used to clone Moodle admin setting page.
 * It is used to create a new form used to pre-configure lti activities
 *
 * @package    mod
 * @subpackage lti
 * @copyright  2009 Marc Alier, Jordi Piguillem, Nikolas Galanis
 *  marc.alier@upc.edu
 * @copyright  2009 Universitat Politecnica de Catalunya http://www.upc.edu
 * @author     Marc Alier
 * @author     Jordi Piguillem
 * @author     Nikolas Galanis
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/mod/lti/edit_form.php');
require_once($CFG->dirroot.'/mod/lti/locallib.php');

$section      = 'modsettinglti';
$return       = optional_param('return', '', PARAM_ALPHA);
$adminediting = optional_param('adminedit', -1, PARAM_BOOL);
$action       = optional_param('action', null, PARAM_ACTION);
$id           = optional_param('id', null, PARAM_INT);
$useexisting  = optional_param('useexisting', null, PARAM_INT);
$definenew    = optional_param('definenew', null, PARAM_INT);

// no guest autologin
require_login(0, false);
$url = new moodle_url('/mod/lti/typesettings.php');
$PAGE->set_url($url);

admin_externalpage_setup('managemodules'); // Hacky solution for printing the admin page

$tab = optional_param('tab', '', PARAM_ALPHAEXT);
$redirect = "$CFG->wwwroot/$CFG->admin/settings.php?section=modsettinglti&tab={$tab}";

// WRITING SUBMITTED DATA (IF ANY)

$statusmsg = '';
$errormsg  = '';
$focus = '';

$data = data_submitted();

// Any posted data & any action
if (!empty($data) || !empty($action)) {
    require_sesskey();
}

if (isset($data->submitbutton)) {
    $type = new stdClass();

    if (isset($id)) {
        $type->id = $id;

        lti_update_type($type, $data);

        redirect($redirect);
    } else {
        $type->state = LTI_TOOL_STATE_CONFIGURED;

        lti_add_type($type, $data);

        redirect($redirect);
    }

} else if (isset($data->cancel)) {
    redirect($redirect);

} else if ($action == 'accept') {
    lti_set_state_for_type($id, LTI_TOOL_STATE_CONFIGURED);
    redirect($redirect);

} else if ($action == 'reject') {
    lti_set_state_for_type($id, LTI_TOOL_STATE_REJECTED);
    redirect($redirect);

} else if ($action == 'delete') {
    lti_delete_type($id);
    redirect($redirect);
}

// print header stuff
$PAGE->set_focuscontrol($focus);
if (empty($SITE->fullname)) {
    $PAGE->set_title($settingspage->visiblename);
    $PAGE->set_heading($settingspage->visiblename);

    $PAGE->navbar->add(get_string('lti_administration', 'lti'), $CFG->wwwroot.'/admin/settings.php?section=modsettinglti');

    echo $OUTPUT->header();

    echo $OUTPUT->box(get_string('configintrosite', 'admin'));

    if ($errormsg !== '') {
        echo $OUTPUT->notification($errormsg);

    } else if ($statusmsg !== '') {
        echo $OUTPUT->notification($statusmsg, 'notifysuccess');
    }

    echo '<form action="typesettings.php" method="post" id="'.$id.'" >';
    echo '<div class="settingsform clearfix">';
    echo html_writer::input_hidden_params($PAGE->url);
    echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
    echo '<input type="hidden" name="return" value="'.$return.'" />';

    echo $settingspage->output_html();

    echo '<div class="form-buttons"><input class="form-submit" type="submit" value="'.get_string('savechanges', 'admin').'" /></div>';

    echo '</div>';
    echo '</form>';

} else {
    if ($PAGE->user_allowed_editing()) {
        $url = clone($PAGE->url);
        if ($PAGE->user_is_editing()) {
            $caption = get_string('blockseditoff');
            $url->param('adminedit', 'off');
        } else {
            $caption = get_string('blocksediton');
            $url->param('adminedit', 'on');
        }
        $buttons = $OUTPUT->single_button($url, $caption, 'get');
    }

    $PAGE->set_title("$SITE->shortname: " . get_string('toolsetup', 'lti'));

    $PAGE->navbar->add(get_string('lti_administration', 'lti'), $CFG->wwwroot.'/admin/settings.php?section=modsettinglti');

    echo $OUTPUT->header();

    if ($errormsg !== '') {
        echo $OUTPUT->notification($errormsg);

    } else if ($statusmsg !== '') {
        echo $OUTPUT->notification($statusmsg, 'notifysuccess');
    }

    echo $OUTPUT->heading(get_string('toolsetup', 'lti'));
    echo $OUTPUT->box_start('generalbox');
    if ($action == 'add') {
        $form = new mod_lti_edit_types_form(null, (object)array('isadmin' => true));
        $form->display();
    } else if ($action == 'update') {
        $form = new mod_lti_edit_types_form('typessettings.php?id='.$id, (object)array('isadmin' => true));
        $type = lti_get_type_type_config($id);
        $form->set_data($type);
        $form->display();
    }

    echo $OUTPUT->box_end();
}

echo $OUTPUT->footer();
