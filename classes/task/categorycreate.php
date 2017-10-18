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
 * A scheduled task for scripted database integrations - category creation.
 *
 * @package    local_categorycreate - template
 * @copyright  2016 ROelmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_categorycreate\task;
use stdClass;
use coursecat;

defined('MOODLE_INTERNAL') || die;

/**
 * A scheduled task for scripted external database integrations.
 *
 * @copyright  2016 ROelmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class categorycreate extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('pluginname', 'local_categorycreate');
    }

    /**
     * Run sync.
     */
    public function execute() {

        global $CFG, $DB;
        require_once($CFG->libdir . "/coursecatlib.php");

        // Check connection and label Db/Table in cron output for debugging if required.
        if (!$this->get_config('dbtype')) {
            echo 'Database not defined.<br>';
            return 0;
        } else {
            echo 'Database: ' . $this->get_config('dbtype') . '<br>';
        }
        if (!$this->get_config('remotetablecatlev')) {
            echo 'Table1 not defined.<br>';
            return 0;
        } else {
            echo 'Table: ' . $this->get_config('remotetablecatlev') . '<br>';
        }
        if (!$this->get_config('remotetablecats')) {
            echo 'Table1 not defined.<br>';
            return 0;
        } else {
            echo 'Table: ' . $this->get_config('remotetablecats') . '<br>';
        }

        echo 'Starting connection...<br>';

        // Report connection error if occurs.
        if (!$extdb = $this->db_init()) {
            echo 'Error while communicating with external database <br>';
            return 1;
        }

        // EXTERNAL DB - TABLE1: Category levels.
        // Get external table name.
        $table = $this->get_config('remotetablecatlev');
        $levels = array();

        // Read data from table1.
        $sql = $this->db_get_sql($table, array(), array(), true, "rank");
        if ($rs = $extdb->Execute($sql)) {
            if (!$rs->EOF) {
                while ($fields = $rs->FetchRow()) {
                    $fields = array_change_key_case($fields, CASE_LOWER);
                    $fields = $this->db_decode($fields);
                    $levels[] = $fields;
                }
            }
            $rs->Close();
        } else {
            // Report error if required.
            $extdb->Close();
            echo 'Error reading data from the external catlevel table, ' . $table . '<br>';
            return 4;
        }

        $cats = array();
        foreach ($levels as $l) { // Loop through each level in turn to create a tree.
            if ($l['inuse'] == 1) {

                $level = $l['categorylevel'];

                // EXTERNAL DB - TABLE2: Categories list.
                // Get external table name.
                $table2 = $this->get_config('remotetable2cats');

                // Read data from table2.
                $sql2 = $this->db_get_sql_like($table2, array("category_idnumber" => $level), array(), true);
                if ($rs2 = $extdb->Execute($sql2)) {
                    if (!$rs2->EOF) {
                        while ($category = $rs2->FetchRow()) {
                            $category = array_change_key_case($category, CASE_LOWER);
                            $category = $this->db_decode($category);
                            $cats[] = $category;

                            // Create data to write category.
                            $data = array();

                            // ID number - UoG essential data!
                            if (isset($category['category_idnumber'])) {
                                $data['idnumber'] = $category['category_idnumber'];
                            } else {
                                echo 'Category IdNumber required';
                                break;
                            }

                            // Name - If no name is set, make name = idnumber.
                            if (isset($category['category_name']) && $category['category_name'] !== 'Undefined') {
                                $data['name'] = $category['category_name'];
                            } else {
                                $data['name'] = $category['category_idnumber'];
                            }

                            // Default $parent values as Misc category, to give base values and get initial record.
                            $parent = $DB->get_record('course_categories', array('name' => 'Miscellaneous'));
                            $parent->id = 0;
                            $parent->visible = 1;
                            $parent->depth = 0;
                            $parent->path = '';
                            // If exists overide default $parent by fetching parent->id based on unique parent category idnumber.
                            if (!$category['parent_cat_idnumber'] == '') {
                                // Check if the parent category already exists - based on unique idnumber.
                                if ($DB->record_exists('course_categories',
                                    array('idnumber' => $category['parent_cat_idnumber']))) {
                                    // Fetch that parent category details.
                                    $parent = $DB->get_record('course_categories',
                                    array('idnumber' => $category['parent_cat_idnumber']));
                                }
                            }
                            // Set $data['parent'] as the id of the parent category and depth as parent +1.
                            $data['parent'] = $parent->id;
                            $data['depth'] = $parent->depth + 1;

                            // Create a category that inherits visibility from parent.
                            $data['visible'] = $parent->visible;
                            // If a category is marked as 'deleted' then ensure it is hidden - don't actually delete it.
                            if ($category['deleted']) {
                                $data['visible'] = 0;
                            }

                            if (!$DB->record_exists('course_categories',
                                array('idnumber' => $category['category_idnumber']))) {
                                // Set new category id by inserting the data created above.
                                coursecat::create($data);
                                echo 'Category ' . $data['idnumber'] . ' added<br>';
                            } else {
                                // IF category already exists, fetch the existing id.
                                $data['id'] = $DB->get_field('course_categories', 'id',
                                    array('idnumber' => $category['category_idnumber']));
                                // Set the path as necessary.
                                $data['path'] = $parent->path . '/' . $data['id'];
                                // As category already exists, update it with any changes.
                                $DB->update_record('course_categories', $data);
                                echo 'Category ' . $data['idnumber'] . ' updated<br>';
                            }
                        }
                    }
                    $rs2->Close();
                } else {
                    // Report error if required.
                    $extdb->Close();
                    echo 'Error reading data from the external categories table, ' . $table .'<br>';
                    return 4;
                }
            } else {
                echo 'Category Level ' . $l['categorylevel'] . ' is not in use.<br>';
            }
        }
        // Free memory.
        $extdb->Close();
    }





    /* Db functions cloned from enrol/db plugin.
     * ========================================= */

    /**
     * Tries to make connection to the external database.
     *
     * @return null|ADONewConnection
     */
    public function db_init() {
        global $CFG;

        require_once($CFG->libdir.'/adodb/adodb.inc.php');

        // Connect to the external database (forcing new connection).
        $extdb = ADONewConnection($this->get_config('dbtype'));
        if ($this->get_config('debugdb')) {
            $extdb->debug = true;
            ob_start(); // Start output buffer to allow later use of the page headers.
        }

        // The dbtype my contain the new connection URL, so make sure we are not connected yet.
        if (!$extdb->IsConnected()) {
            $result = $extdb->Connect($this->get_config('dbhost'),
                $this->get_config('dbuser'),
                $this->get_config('dbpass'),
                $this->get_config('dbname'), true);
            if (!$result) {
                return null;
            }
        }

        $extdb->SetFetchMode(ADODB_FETCH_ASSOC);
        if ($this->get_config('dbsetupsql')) {
            $extdb->Execute($this->get_config('dbsetupsql'));
        }
        return $extdb;
    }

    public function db_addslashes($text) {
        // Use custom made function for now - it is better to not rely on adodb or php defaults.
        if ($this->get_config('dbsybasequoting')) {
            $text = str_replace('\\', '\\\\', $text);
            $text = str_replace(array('\'', '"', "\0"), array('\\\'', '\\"', '\\0'), $text);
        } else {
            $text = str_replace("'", "''", $text);
        }
        return $text;
    }

    public function db_encode($text) {
        $dbenc = $this->get_config('dbencoding');
        if (empty($dbenc) or $dbenc == 'utf-8') {
            return $text;
        }
        if (is_array($text)) {
            foreach ($text as $k => $value) {
                $text[$k] = $this->db_encode($value);
            }
            return $text;
        } else {
            return core_text::convert($text, 'utf-8', $dbenc);
        }
    }

    public function db_decode($text) {
        $dbenc = $this->get_config('dbencoding');
        if (empty($dbenc) or $dbenc == 'utf-8') {
            return $text;
        }
        if (is_array($text)) {
            foreach ($text as $k => $value) {
                $text[$k] = $this->db_decode($value);
            }
            return $text;
        } else {
            return core_text::convert($text, $dbenc, 'utf-8');
        }
    }

    public function db_get_sql($table, array $conditions, array $fields, $distinct = false, $sort = "") {
        $fields = $fields ? implode(',', $fields) : "*";
        $where = array();
        if ($conditions) {
            foreach ($conditions as $key => $value) {
                $value = $this->db_encode($this->db_addslashes($value));

                $where[] = "$key = '$value'";
            }
        }
        $where = $where ? "WHERE ".implode(" AND ", $where) : "";
        $sort = $sort ? "ORDER BY $sort" : "";
        $distinct = $distinct ? "DISTINCT" : "";
        $sql = "SELECT $distinct $fields
                  FROM $table
                 $where
                  $sort";
        return $sql;
    }

    public function db_get_sql_like($table2, array $conditions, array $fields, $distinct = false, $sort = "") {
        $fields = $fields ? implode(',', $fields) : "*";
        $where = array();
        if ($conditions) {
            foreach ($conditions as $key => $value) {
                $value = $this->db_encode($this->db_addslashes($value));

                $where[] = "$key LIKE '%$value%'";
            }
        }
        $where = $where ? "WHERE ".implode(" AND ", $where) : "";
        $sort = $sort ? "ORDER BY $sort" : "";
        $distinct = $distinct ? "DISTINCT" : "";
        $sql2 = "SELECT $distinct $fields
                  FROM $table2
                 $where
                  $sort";
        return $sql2;
    }


    /**
     * Returns plugin config value
     * @param  string $name
     * @param  string $default value if config does not exist yet
     * @return string value or default
     */
    public function get_config($name, $default = null) {
        $this->load_config();
        return isset($this->config->$name) ? $this->config->$name : $default;
    }

    /**
     * Sets plugin config value
     * @param  string $name name of config
     * @param  string $value string config value, null means delete
     * @return string value
     */
    public function set_config($name, $value) {
        $pluginname = $this->get_name();
        $this->load_config();
        if ($value === null) {
            unset($this->config->$name);
        } else {
            $this->config->$name = $value;
        }
        set_config($name, $value, "local_$pluginname");
    }

    /**
     * Makes sure config is loaded and cached.
     * @return void
     */
    public function load_config() {
        if (!isset($this->config)) {
            $name = $this->get_name();
            $this->config = get_config("local_$name");
        }
    }
}
