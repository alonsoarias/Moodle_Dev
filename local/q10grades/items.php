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
 * Q10 Items mapping page - Map Q10 grade items to Moodle activities.
 *
 * @package    local_q10grades
 * @copyright  2025 Your Institution
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/gradelib.php');

$courseid = required_param('courseid', PARAM_INT);
$action = optional_param('action', 'list', PARAM_ALPHA);
$itemid = optional_param('itemid', 0, PARAM_INT);
$delete = optional_param('delete', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_BOOL);
$fetchq10 = optional_param('fetchq10', 0, PARAM_BOOL);

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$context = context_course::instance($courseid);

require_login($course);
require_capability('local/q10grades:sync', $context);

$PAGE->set_url(new moodle_url('/local/q10grades/items.php', ['courseid' => $courseid]));
$PAGE->set_context($context);
$PAGE->set_course($course);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title(get_string('q10items', 'local_q10grades'));
$PAGE->set_heading($course->fullname);

$PAGE->navbar->add(get_string('syncgrades', 'local_q10grades'),
    new moodle_url('/local/q10grades/sync.php', ['courseid' => $courseid]));
$PAGE->navbar->add(get_string('q10items', 'local_q10grades'));

// Get Moodle grade items for this course.
$gradeitems = grade_item::fetch_all(['courseid' => $courseid]);
$moodleactivities = [];
if ($gradeitems) {
    foreach ($gradeitems as $item) {
        if ($item->itemtype === 'course') {
            continue; // Skip course total.
        }
        $itemname = $item->itemname ?: get_string('unnamed', 'local_q10grades');
        if ($item->itemmodule) {
            $itemname = get_string('modulename', $item->itemmodule) . ': ' . $itemname;
        } elseif ($item->itemtype === 'category') {
            $itemname = get_string('category', 'grades') . ': ' . $itemname;
        }
        $moodleactivities[$item->id] = $itemname;
    }
}

// Check API and mapping.
$apiclient = new \local_q10grades\q10_api_client();
$syncmanager = new \local_q10grades\grade_sync_manager($courseid);
$mapping = $syncmanager->get_mapping();

// Handle fetch from Q10.
if ($fetchq10 && confirm_sesskey() && $mapping && $apiclient->is_configured()) {
    try {
        $q10items = $apiclient->get_evaluation_components($mapping->q10_subject_id);

        if (!empty($q10items['data'])) {
            $imported = 0;
            foreach ($q10items['data'] as $q10item) {
                // Check if already exists.
                $existing = $DB->get_record('local_q10grades_items', [
                    'courseid' => $courseid,
                    'q10_item_id' => $q10item['id'],
                ]);

                if (!$existing) {
                    $record = new stdClass();
                    $record->courseid = $courseid;
                    $record->q10_item_id = $q10item['id'];
                    $record->q10_item_name = $q10item['name'] ?? $q10item['id'];
                    $record->q10_item_weight = $q10item['weight'] ?? null;
                    $record->calculation_type = 'average';
                    $record->enabled = 1;
                    $record->sortorder = $imported;
                    $record->timecreated = time();
                    $record->timemodified = time();
                    $record->usermodified = $USER->id;

                    $DB->insert_record('local_q10grades_items', $record);
                    $imported++;
                }
            }

            redirect(
                new moodle_url('/local/q10grades/items.php', ['courseid' => $courseid]),
                get_string('itemsimported', 'local_q10grades', $imported),
                null,
                \core\output\notification::NOTIFY_SUCCESS
            );
        } else {
            redirect(
                new moodle_url('/local/q10grades/items.php', ['courseid' => $courseid]),
                get_string('noitemsq10', 'local_q10grades'),
                null,
                \core\output\notification::NOTIFY_WARNING
            );
        }
    } catch (\Exception $e) {
        redirect(
            new moodle_url('/local/q10grades/items.php', ['courseid' => $courseid]),
            get_string('fetcherror', 'local_q10grades', $e->getMessage()),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }
}

// Handle delete.
if ($delete && confirm_sesskey()) {
    $item = $DB->get_record('local_q10grades_items', ['id' => $delete, 'courseid' => $courseid], '*', MUST_EXIST);

    if ($confirm) {
        $DB->delete_records('local_q10grades_items', ['id' => $delete]);
        redirect(
            new moodle_url('/local/q10grades/items.php', ['courseid' => $courseid]),
            get_string('itemdeleted', 'local_q10grades'),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    } else {
        echo $OUTPUT->header();
        echo $OUTPUT->heading(get_string('deleteitem', 'local_q10grades'));
        echo $OUTPUT->confirm(
            get_string('deleteitemconfirm', 'local_q10grades', $item->q10_item_name),
            new moodle_url('/local/q10grades/items.php', [
                'courseid' => $courseid,
                'delete' => $delete,
                'confirm' => 1,
                'sesskey' => sesskey(),
            ]),
            new moodle_url('/local/q10grades/items.php', ['courseid' => $courseid])
        );
        echo $OUTPUT->footer();
        exit;
    }
}

// Handle add/edit item.
if ($action === 'add' || $action === 'edit') {
    $item = null;
    if ($action === 'edit' && $itemid) {
        $item = $DB->get_record('local_q10grades_items', ['id' => $itemid, 'courseid' => $courseid], '*', MUST_EXIST);
    }

    // Process form submission.
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && confirm_sesskey()) {
        $q10itemid = required_param('q10_item_id', PARAM_TEXT);
        $q10itemname = required_param('q10_item_name', PARAM_TEXT);
        $q10itemweight = optional_param('q10_item_weight', null, PARAM_FLOAT);
        $calculationtype = required_param('calculation_type', PARAM_ALPHA);
        $selectedactivities = optional_param_array('selected_activities', [], PARAM_INT);
        $enabled = optional_param('enabled', 0, PARAM_BOOL);

        // Collect weights for weighted average.
        $activityweights = [];
        foreach ($selectedactivities as $actid) {
            $weight = optional_param('weight_' . $actid, 1, PARAM_FLOAT);
            $activityweights[$actid] = $weight;
        }

        $customformula = optional_param('custom_formula', '', PARAM_TEXT);

        $now = time();

        $record = new stdClass();
        $record->courseid = $courseid;
        $record->q10_item_id = $q10itemid;
        $record->q10_item_name = $q10itemname;
        $record->q10_item_weight = $q10itemweight;
        $record->calculation_type = $calculationtype;
        $record->selected_activities = json_encode(array_values($selectedactivities));
        $record->activity_weights = json_encode($activityweights);
        $record->custom_formula = $customformula;
        $record->enabled = $enabled ? 1 : 0;
        $record->timemodified = $now;
        $record->usermodified = $USER->id;

        if ($item) {
            $record->id = $item->id;
            $DB->update_record('local_q10grades_items', $record);
            $message = get_string('itemupdated', 'local_q10grades');
        } else {
            $record->timecreated = $now;
            $record->sortorder = $DB->count_records('local_q10grades_items', ['courseid' => $courseid]);
            $DB->insert_record('local_q10grades_items', $record);
            $message = get_string('itemcreated', 'local_q10grades');
        }

        redirect(
            new moodle_url('/local/q10grades/items.php', ['courseid' => $courseid]),
            $message,
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    }

    // Show form.
    echo $OUTPUT->header();
    echo $OUTPUT->heading($action === 'add' ? get_string('additem', 'local_q10grades') : get_string('edititem', 'local_q10grades'));

    // Prepare form data.
    $selectedactivities = [];
    $activityweights = [];
    if ($item) {
        $selectedactivities = json_decode($item->selected_activities, true) ?? [];
        $activityweights = json_decode($item->activity_weights, true) ?? [];
    }

    $calculationtypes = [
        'average' => get_string('formulaaverage', 'local_q10grades'),
        'weighted' => get_string('formulaweighted', 'local_q10grades'),
        'sum' => get_string('formulasum', 'local_q10grades'),
        'highest' => get_string('formulahighest', 'local_q10grades'),
        'lowest' => get_string('formulalowest', 'local_q10grades'),
    ];

    ?>
    <form method="post" action="<?php echo $PAGE->url->out(false, ['action' => $action, 'itemid' => $itemid]); ?>" class="mform">
        <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">

        <div class="card mb-4">
            <div class="card-header">
                <h5><?php echo get_string('q10iteminfo', 'local_q10grades'); ?></h5>
            </div>
            <div class="card-body">
                <div class="form-group row mb-3">
                    <label class="col-sm-3 col-form-label" for="q10_item_id">
                        <?php echo get_string('q10itemid', 'local_q10grades'); ?> <span class="text-danger">*</span>
                    </label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" id="q10_item_id" name="q10_item_id"
                               value="<?php echo s($item->q10_item_id ?? ''); ?>" required>
                        <small class="form-text text-muted"><?php echo get_string('q10itemid_help', 'local_q10grades'); ?></small>
                    </div>
                </div>

                <div class="form-group row mb-3">
                    <label class="col-sm-3 col-form-label" for="q10_item_name">
                        <?php echo get_string('q10itemname', 'local_q10grades'); ?> <span class="text-danger">*</span>
                    </label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control" id="q10_item_name" name="q10_item_name"
                               value="<?php echo s($item->q10_item_name ?? ''); ?>" required
                               placeholder="<?php echo get_string('q10itemname_placeholder', 'local_q10grades'); ?>">
                    </div>
                </div>

                <div class="form-group row mb-3">
                    <label class="col-sm-3 col-form-label" for="q10_item_weight">
                        <?php echo get_string('q10itemweight', 'local_q10grades'); ?>
                    </label>
                    <div class="col-sm-9">
                        <input type="number" class="form-control" id="q10_item_weight" name="q10_item_weight"
                               value="<?php echo $item->q10_item_weight ?? ''; ?>" step="0.01" min="0" max="100">
                        <small class="form-text text-muted"><?php echo get_string('q10itemweight_help', 'local_q10grades'); ?></small>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h5><?php echo get_string('selectactivities', 'local_q10grades'); ?></h5>
            </div>
            <div class="card-body">
                <p class="text-muted"><?php echo get_string('selectactivities_desc', 'local_q10grades'); ?></p>

                <?php if (empty($moodleactivities)): ?>
                    <div class="alert alert-warning"><?php echo get_string('noactivities', 'local_q10grades'); ?></div>
                <?php else: ?>
                    <div class="form-group">
                        <div class="activity-list" style="max-height: 300px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 0.25rem; padding: 0.5rem;">
                            <?php foreach ($moodleactivities as $actid => $actname): ?>
                                <div class="form-check mb-2">
                                    <input class="form-check-input activity-checkbox" type="checkbox"
                                           name="selected_activities[]" value="<?php echo $actid; ?>"
                                           id="activity_<?php echo $actid; ?>"
                                           <?php echo in_array($actid, $selectedactivities) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="activity_<?php echo $actid; ?>">
                                        <?php echo s($actname); ?>
                                    </label>
                                    <div class="weight-input ml-4 mt-1" id="weight_div_<?php echo $actid; ?>"
                                         style="<?php echo in_array($actid, $selectedactivities) ? '' : 'display:none;'; ?>">
                                        <label class="small text-muted"><?php echo get_string('weight', 'local_q10grades'); ?>:</label>
                                        <input type="number" class="form-control form-control-sm d-inline-block"
                                               name="weight_<?php echo $actid; ?>"
                                               value="<?php echo $activityweights[$actid] ?? 1; ?>"
                                               step="0.1" min="0" style="width: 80px;">
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h5><?php echo get_string('calculationmethod', 'local_q10grades'); ?></h5>
            </div>
            <div class="card-body">
                <div class="form-group row mb-3">
                    <label class="col-sm-3 col-form-label" for="calculation_type">
                        <?php echo get_string('calculationtype', 'local_q10grades'); ?>
                    </label>
                    <div class="col-sm-9">
                        <select class="form-control" id="calculation_type" name="calculation_type">
                            <?php foreach ($calculationtypes as $type => $label): ?>
                                <option value="<?php echo $type; ?>"
                                        <?php echo ($item->calculation_type ?? 'average') === $type ? 'selected' : ''; ?>>
                                    <?php echo $label; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted"><?php echo get_string('calculationtype_help', 'local_q10grades'); ?></small>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-group mb-4">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="enabled" value="1" id="enabled"
                       <?php echo ($item->enabled ?? 1) ? 'checked' : ''; ?>>
                <label class="form-check-label" for="enabled">
                    <?php echo get_string('enableitem', 'local_q10grades'); ?>
                </label>
            </div>
        </div>

        <div class="form-group">
            <button type="submit" class="btn btn-primary"><?php echo get_string('savechanges'); ?></button>
            <a href="<?php echo new moodle_url('/local/q10grades/items.php', ['courseid' => $courseid]); ?>"
               class="btn btn-secondary"><?php echo get_string('cancel'); ?></a>
        </div>
    </form>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Show/hide weight inputs based on checkbox state.
        document.querySelectorAll('.activity-checkbox').forEach(function(checkbox) {
            checkbox.addEventListener('change', function() {
                var weightDiv = document.getElementById('weight_div_' + this.value);
                if (this.checked) {
                    weightDiv.style.display = 'block';
                } else {
                    weightDiv.style.display = 'none';
                }
            });
        });
    });
    </script>
    <?php

    echo $OUTPUT->footer();
    exit;
}

// List items.
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('q10items', 'local_q10grades'));

// Navigation tabs.
$tabs = [];
$tabs[] = new tabobject('sync',
    new moodle_url('/local/q10grades/sync.php', ['courseid' => $courseid]),
    get_string('syncgrades', 'local_q10grades'));
$tabs[] = new tabobject('mapping',
    new moodle_url('/local/q10grades/mapping.php', ['courseid' => $courseid]),
    get_string('coursemapping', 'local_q10grades'));
$tabs[] = new tabobject('items',
    new moodle_url('/local/q10grades/items.php', ['courseid' => $courseid]),
    get_string('q10items', 'local_q10grades'));
$tabs[] = new tabobject('upload',
    new moodle_url('/local/q10grades/upload.php', ['courseid' => $courseid]),
    get_string('uploadgrades', 'local_q10grades'));
$tabs[] = new tabobject('history',
    new moodle_url('/local/q10grades/history.php', ['courseid' => $courseid]),
    get_string('synchistory', 'local_q10grades'));

echo $OUTPUT->tabtree($tabs, 'items');

// Warnings.
if (!$syncmanager->has_mapping()) {
    echo $OUTPUT->notification(
        get_string('nomapping', 'local_q10grades') . ' ' .
        html_writer::link(
            new moodle_url('/local/q10grades/mapping.php', ['courseid' => $courseid]),
            get_string('configuremapping', 'local_q10grades')
        ),
        'warning'
    );
}

echo html_writer::tag('p', get_string('q10items_desc', 'local_q10grades'), ['class' => 'lead']);

// Action buttons.
echo html_writer::start_div('mb-4');

echo html_writer::link(
    new moodle_url('/local/q10grades/items.php', ['courseid' => $courseid, 'action' => 'add']),
    get_string('additem', 'local_q10grades'),
    ['class' => 'btn btn-primary mr-2']
);

if ($mapping && $apiclient->is_configured()) {
    echo html_writer::link(
        new moodle_url('/local/q10grades/items.php', ['courseid' => $courseid, 'fetchq10' => 1, 'sesskey' => sesskey()]),
        get_string('fetchfromq10', 'local_q10grades'),
        ['class' => 'btn btn-outline-secondary']
    );
}

echo html_writer::end_div();

// Get existing items.
$items = $DB->get_records('local_q10grades_items', ['courseid' => $courseid], 'sortorder');

if (empty($items)) {
    echo $OUTPUT->notification(get_string('noitems', 'local_q10grades'), 'info');
} else {
    $calculationtypes = [
        'average' => get_string('formulaaverage', 'local_q10grades'),
        'weighted' => get_string('formulaweighted', 'local_q10grades'),
        'sum' => get_string('formulasum', 'local_q10grades'),
        'highest' => get_string('formulahighest', 'local_q10grades'),
        'lowest' => get_string('formulalowest', 'local_q10grades'),
    ];

    $table = new html_table();
    $table->head = [
        get_string('q10itemname', 'local_q10grades'),
        get_string('q10itemid', 'local_q10grades'),
        get_string('selectedactivities', 'local_q10grades'),
        get_string('calculationtype', 'local_q10grades'),
        get_string('status'),
        get_string('actions'),
    ];
    $table->attributes['class'] = 'table table-striped';

    foreach ($items as $item) {
        $selectedactivities = json_decode($item->selected_activities, true) ?? [];
        $activitycount = count($selectedactivities);

        // Build activity names list.
        $activitynames = [];
        foreach ($selectedactivities as $actid) {
            if (isset($moodleactivities[$actid])) {
                $activitynames[] = $moodleactivities[$actid];
            }
        }
        $activitieshtml = $activitycount > 0 ?
            html_writer::tag('span', $activitycount . ' ' . get_string('activities', 'local_q10grades'),
                ['class' => 'badge badge-info', 'title' => implode("\n", $activitynames)]) :
            html_writer::tag('span', get_string('none'), ['class' => 'text-muted']);

        $status = $item->enabled ?
            html_writer::tag('span', get_string('enabled', 'local_q10grades'), ['class' => 'badge badge-success']) :
            html_writer::tag('span', get_string('disabled', 'local_q10grades'), ['class' => 'badge badge-secondary']);

        $actions = html_writer::link(
            new moodle_url('/local/q10grades/items.php', ['courseid' => $courseid, 'action' => 'edit', 'itemid' => $item->id]),
            $OUTPUT->pix_icon('t/edit', get_string('edit')),
            ['class' => 'mr-2']
        );
        $actions .= html_writer::link(
            new moodle_url('/local/q10grades/items.php', ['courseid' => $courseid, 'delete' => $item->id, 'sesskey' => sesskey()]),
            $OUTPUT->pix_icon('t/delete', get_string('delete')),
            ['class' => 'text-danger']
        );

        $table->data[] = [
            html_writer::tag('strong', s($item->q10_item_name)),
            html_writer::tag('code', s($item->q10_item_id)),
            $activitieshtml,
            $calculationtypes[$item->calculation_type] ?? $item->calculation_type,
            $status,
            $actions,
        ];
    }

    echo html_writer::table($table);
}

echo $OUTPUT->footer();
