# Report Custom Cajasan block

This block provides quick access to the **Course Status and Tracking Report**. The following changes were introduced in this update:

* Visibility of the block is now controlled through the new capability `block/report_customcajasan:viewblock`. Only users with editing roles (e.g. teachers and managers) can see and interact with the block.
* Access to the report continues to rely on Moodle capabilities. Teachers and managers must have the capability `block/report_customcajasan:viewreport` within the relevant course context, while site administrators require the dedicated system capability `block/report_customcajasan:viewsystemreport` to use the block on site-level and dashboard pages.
* Each block instance offers configurable display options. When editing a block you can choose which elements to show (report link, usage instructions, status legend) and optionally add a custom message for participants with editing privileges.

Remember to run the Moodle upgrade after deploying these changes so the new capabilities are created and available for assignment.
