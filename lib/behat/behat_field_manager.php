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
 * Form fields helper.
 *
 * @package    core
 * @category   test
 * @copyright  2013 David Monllaó
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

use Behat\Mink\Session as Session,
    Behat\Mink\Element\NodeElement as NodeElement;

/**
 * Helper to interact with form fields.
 *
 * @package    core
 * @category   test
 * @copyright  2013 David Monllaó
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_field_manager {

    /**
     * Gets an instance of the form field.
     *
     * Not all the fields are part of a moodle form, in this
     * cases it fallsback to the generic form field. Also note
     * that this generic field type is using a generic setValue()
     * method from the Behat API, which is not always good to set
     * the value of form elements.
     *
     * @param NodeElement $fieldnode
     * @param string $locator The locator to help give more info about the possible problem.
     * @param Session $session The behat browser session
     * @return behat_form_field
     */
    public static function get_field(NodeElement $fieldnode, $locator, Session $session) {
        global $CFG;

        // Get the field type if is part of a moodleform.
        if (self::is_moodleform_field($fieldnode)) {
            $type = self::get_node_type($fieldnode, $locator, $session);
        }

        // If is not a moodleforms field use the base field type.
        if (empty($type)) {
            $type = 'field';
        }

        $classname = 'behat_form_' . $type;

        // Fallsback on the default form field if nothing specific exists.
        $classpath = $CFG->libdir . '/behat/form_field/' . $classname . '.php';
        if (!file_exists($classpath)) {
            $classname = 'behat_form_field';
            $classpath = $CFG->libdir . '/behat/form_field/' . $classname . '.php';
        }

        // Returns the instance.
        require_once($classpath);
        return new $classname($session, $fieldnode);
    }

    /**
     * Detects when the field is a moodleform field type.
     *
     * Note that there are fields inside moodleforms that are not
     * moodleform element; this method can not detect this, this will
     * be managed by get_node_type, after failing to find the form
     * element element type.
     *
     * @param NodeElement $fieldnode
     * @return bool
     */
    protected static function is_moodleform_field(NodeElement $fieldnode) {

        // We already waited when getting the NodeElement and we don't want an exception if it's not part of a moodleform.
        $parentformfound = $fieldnode->find('xpath', "/ancestor::form[contains(concat(' ', normalize-space(@class), ' '), ' mform ')]/fieldset");

        return ($parentformfound != false);
    }

    /**
     * Recursive method to find the field type.
     *
     * Depending on the field the felement class node is a level or in another. We
     * look recursively for a parent node with a 'felement' class to find the field type.
     *
     * @param NodeElement $fieldnode The current node.
     * @param string $locator Just to send an exception that makes sense for the user.
     * @param Session $session The behat browser session
     * @return mixed A NodeElement if we continue looking for the element type and String or false when we are done.
     */
    protected static function get_node_type(NodeElement $fieldnode, $locator, Session $session) {

        // We look for a parent node with 'felement' class.
        if ($class = $fieldnode->getParent()->getAttribute('class')) {

            if (strstr($class, 'felement') != false) {
                // Remove 'felement f' from class value.
                return substr($class, 10);
            }

            // Stop propagation through the DOM, if it does not have a felement is not part of a moodle form.
            if (strstr($class, 'fcontainer') != false) {
                return false;
            }
        }

        return self::get_node_type($fieldnode->getParent(), $locator, $session);
    }

}
