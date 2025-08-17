# Quiz Retake UI

Plugin extending quiz attempts with "risk" checkboxes, dual-grade calculation and review page with visual summary,
filters and retake options.

## Installation

1. Copy the plugin to `local/quiz_retake_ui`.
2. Visit the site administration to trigger the installation.

## Notes

Risky selections are stored per attempt and excluded from the risk-free grade. The retake endpoint is a stub and must be
extended to build a new attempt using the Moodle question engine.
