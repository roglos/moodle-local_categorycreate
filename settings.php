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
 * Category creation local plugin settings and presets.
 *
 * @package    local_categorycreate
 * @copyright  2017 RMOelmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_categorycreate',
        get_string('pluginname', 'local_categorycreate'));
    $ADMIN->add('localplugins', $settings);

        // Headings.
    $settings->add(new admin_setting_heading('local_categorycreate_settings', '',
        get_string('pluginname_desc', 'local_categorycreate')));
    $settings->add(new admin_setting_heading('local_categorycreate_exdbheader',
        get_string('settingsheaderdb', 'local_categorycreate'), ''));

    // Db Connection Settings.
    // -----------------------

    // Db type.
    $options = array('',
        "access",
        "ado_access",
        "ado",
        "ado_mssql",
        "borland_ibase",
        "csv",
        "db2",
        "fbsql",
        "firebird",
        "ibase",
        "informix72",
        "informix",
        "mssql",
        "mssql_n",
        "mssqlnative",
        "mysql",
        "mysqli",
        "mysqlt",
        "oci805",
        "oci8",
        "oci8po",
        "odbc",
        "odbc_mssql",
        "odbc_oracle",
        "oracle",
        "pdo",
        "postgres64",
        "postgres7",
        "postgres",
        "proxy",
        "sqlanywhere",
        "sybase",
        "vfp");
    $options = array_combine($options, $options);
    $settings->add(new admin_setting_configselect('local_categorycreate/dbtype',
        get_string('dbtype', 'local_categorycreate'),
        get_string('dbtype_desc', 'local_categorycreate'), '', $options));

    // Db host.
    $settings->add(new admin_setting_configtext('local_categorycreate/dbhost',
        get_string('dbhost', 'local_categorycreate'),
        get_string('dbhost_desc', 'local_categorycreate'), 'localhost'));

    // Db User.
    $settings->add(new admin_setting_configtext('local_categorycreate/dbuser',
        get_string('dbuser', 'local_categorycreate'), '', ''));

    // Db Password.
    $settings->add(new admin_setting_configpasswordunmask('local_categorycreate/dbpass',
        get_string('dbpass', 'local_categorycreate'), '', ''));

    // Db Name.
    $settings->add(new admin_setting_configtext('local_categorycreate/dbname',
        get_string('dbname', 'local_categorycreate'),
        get_string('dbname_desc', 'local_categorycreate'), ''));

    // Db Encoding.
    $settings->add(new admin_setting_configtext('local_categorycreate/dbencoding',
        get_string('dbencoding', 'local_categorycreate'), '', 'utf-8'));

    // Db Setup.
    $settings->add(new admin_setting_configtext('local_categorycreate/dbsetupsql',
        get_string('dbsetupsql', 'local_categorycreate'),
        get_string('dbsetupsql_desc', 'local_categorycreate'), ''));

    // Db Sybase.
    $settings->add(new admin_setting_configcheckbox('local_categorycreate/dbsybasequoting',
        get_string('dbsybasequoting', 'local_categorycreate'),
        get_string('dbsybasequoting_desc', 'local_categorycreate'), 0));

    // AODBC Debug.
    $settings->add(new admin_setting_configcheckbox('local_categorycreate/debugdb',
        get_string('debugdb', 'local_categorycreate'),
        get_string('debugdb_desc', 'local_categorycreate'), 0));

    // Table Settings.
    $settings->add(new admin_setting_heading('local_categorycreate_remoteheader',
        get_string('settingsheaderremote', 'local_categorycreate'), ''));

    // Table name.
    $settings->add(new admin_setting_configtext('local_categorycreate/remotetable1',
        get_string('remotetablecatlev', 'local_categorycreate'),
        get_string('remotetablecatlev_desc', 'local_categorycreate'), ''));
    $settings->add(new admin_setting_configtext('local_categorycreate/remotetable2',
        get_string('remotetablecats', 'local_categorycreate'),
        get_string('remotetablecats_desc', 'local_categorycreate'), ''));

}
