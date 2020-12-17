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
 * Runestone Lesson plugin settings and presets.
 *
 * @package    mod_runestone
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_heading('mod_runestone_settings', '', get_string('pluginname_desc', 'mod_runestone')));

    $settings->add(new admin_setting_heading('mod_runestone_externalurl', get_string('externalurl', 'mod_runestone'), ''));
    $settings->add(new admin_setting_configtext('mod_runestone/externalurl', get_string('url_settings', 'mod_runestone'), get_string('url_desc', 'mod_runestone'), ''));

    $settings->add(new admin_setting_heading('mod_runestone_exdbheader', get_string('settingsheaderdb', 'mod_runestone'), ''));

    $options = array('', "access", "ado_access", "ado", "ado_mssql", "borland_ibase", "csv", "db2", "fbsql", "firebird", "ibase", "informix72", "informix", "mssql", "mssql_n", "mssqlnative", "mysql", "mysqli", "mysqlt", "oci805", "oci8", "oci8po", "odbc", "odbc_mssql", "odbc_oracle", "oracle", "pdo", "postgres64", "postgres7", "postgres", "proxy", "sqlanywhere", "sybase", "vfp");
    $options = array_combine($options, $options);
    $settings->add(new admin_setting_configselect('mod_runestone/dbtype', get_string('dbtype', 'mod_runestone'), get_string('dbtype_desc', 'mod_runestone'), '', $options));

    $settings->add(new admin_setting_configtext('mod_runestone/dbhost', get_string('dbhost', 'mod_runestone'), get_string('dbhost_desc', 'mod_runestone'), 'localhost'));

    $settings->add(new admin_setting_configtext('mod_runestone/dbuser', get_string('dbuser', 'mod_runestone'), '', ''));

    $settings->add(new admin_setting_configpasswordunmask('mod_runestone/dbpass', get_string('dbpass', 'mod_runestone'), '', ''));

    $settings->add(new admin_setting_configtext('mod_runestone/dbname', get_string('dbname', 'mod_runestone'), get_string('dbname_desc', 'mod_runestone'), 'runestone'));

    $settings->add(new admin_setting_configtext('mod_runestone/dbencoding', get_string('dbencoding', 'mod_runestone'), '', 'utf-8'));
}
