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
 * Tests for the Educam Bot NLP pipeline.
 *
 * @package     local_educambot
 * @category    test
 * @copyright   2024 Educam
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_educambot\nlp\pipeline;

/**
 * Verifies the basic behaviour of the NLP pipeline.
 *
 * @covers \local_educambot\nlp\pipeline
 */
class local_educambot_pipeline_testcase extends advanced_testcase {
    /**
     * Ensures the pipeline normalises, tokenises and extracts entities as expected.
     */
    public function test_process_creates_expected_artifacts(): void {
        $this->resetAfterTest();

        $pipeline = new pipeline();
        $result = $pipeline->process('¿Cómo envío tareas en Moodle el 15 de marzo?');

        $this->assertSame('como envio tareas en moodle el 15 de marzo', $result['normalised']);
        $this->assertContains('envio', $result['keywords']);
        $this->assertNotEmpty($result['tokens']);
        $this->assertArrayHasKey('activities', $result['entities']);
        $this->assertContains('tarea', $result['entities']['activities']);
        $this->assertContains('15 de marzo', $result['entities']['dates']);
    }
}
