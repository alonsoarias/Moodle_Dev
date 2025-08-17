<?php
namespace local_quiz_retake_ui;

use renderable;
use templatable;
use renderer_base;
use stdClass;

class review_panel implements renderable, templatable {
    public function __construct(
        public array $stats,
        public string $cutgrade,
        public string $result
    ) {}

    public function export_for_template(renderer_base $output): stdClass {
        return (object) [
            'stats' => $this->stats,
            'cutgrade' => $this->cutgrade,
            'result' => $this->result,
        ];
    }
}
