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
 * Events tests.
 *
 * @package    mod_folder_custom
 * @category   test
 * @copyright  2013 Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_folder_custom\event;

final class events_test extends \advanced_testcase {

    /**
     * Tests set up.
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
    }

    /**
     * Test the folder_custom updated event.
     *
     * There is no external API for updating a folder_custom, so the unit test will simply create
     * and trigger the event and ensure the legacy log data is returned as expected.
     */
    public function test_folder_custom_updated(): void {
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $folder_custom = $this->getDataGenerator()->create_module('folder_custom', array('course' => $course->id));

        $params = array(
            'context' => \context_module::instance($folder_custom->cmid),
            'objectid' => $folder_custom->id,
            'courseid' => $course->id
        );
        $event = \mod_folder_custom\event\folder_custom_updated::create($params);
        $event->add_record_snapshot('folder_custom', $folder_custom);

        // Trigger and capturing the event.
        $sink = $this->redirectEvents();
        $event->trigger();
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\mod_folder_custom\event\folder_custom_updated', $event);
        $this->assertEquals(\context_module::instance($folder_custom->cmid), $event->get_context());
        $this->assertEquals($folder_custom->id, $event->objectid);
    }

    /**
     * Test the folder_custom updated event.
     *
     * There is no external API for updating a folder_custom, so the unit test will simply create
     * and trigger the event and ensure the legacy log data is returned as expected.
     */
    public function test_all_files_downloaded(): void {
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $folder_custom = $this->getDataGenerator()->create_module('folder_custom', array('course' => $course->id));
        $context = \context_module::instance($folder_custom->cmid);
        $cm = get_coursemodule_from_id('folder_custom', $folder_custom->cmid, $course->id, true, MUST_EXIST);

        $sink = $this->redirectEvents();
        folder_custom_downloaded($folder_custom, $course, $cm, $context);
        $events = $sink->get_events();
        $this->assertCount(1, $events);
        $event = reset($events);

        // Checking that the event contains the expected values.
        $this->assertInstanceOf('\mod_folder_custom\event\all_files_downloaded', $event);
        $this->assertEquals(\context_module::instance($folder_custom->cmid), $event->get_context());
        $this->assertEquals($folder_custom->id, $event->objectid);
        $expected = array($course->id, 'folder_custom', 'edit', 'edit.php?id=' . $folder_custom->cmid, $folder_custom->id, $folder_custom->cmid);
        $this->assertEventContextNotUsed($event);
    }
}
