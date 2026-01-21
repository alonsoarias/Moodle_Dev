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
 * Grunt configuration for mod_udes
 *
 * @copyright  2026 Universidad de Santander - UDES (udes.edu.co)
 * @author     Alonso Arias <soporte@orioncloud.com.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Grunt configuration
 */
module.exports = function(grunt) {
    var path = require('path'),
        tasks = {},
        cwd = process.env.PWD || process.cwd();

    // AMD src and dest paths.
    tasks.amd = {
        options: {
            dir: 'amd/src',
            dest: 'amd/build'
        }
    };

    // Register AMD task.
    grunt.registerTask('amd', 'Minify AMD modules', function() {
        grunt.task.run(['uglify:amd']);
    });

    // Minify configuration.
    tasks.uglify = {};
    tasks.uglify.amd = {
        files: [{
            expand: true,
            cwd: 'amd/src',
            src: ['*.js', '!*.min.js'],
            dest: 'amd/build',
            ext: '.min.js'
        }],
        options: {
            compress: {
                hoist_vars: false,
                loops: false,
                unused: false
            },
            mangle: false,
            preserveComments: /^!/
        }
    };

    // Load NPM tasks.
    grunt.loadNpmTasks('grunt-contrib-uglify');

    // Register tasks.
    grunt.initConfig(tasks);

    // Default task - minify AMD modules.
    grunt.registerTask('default', ['amd']);
};
