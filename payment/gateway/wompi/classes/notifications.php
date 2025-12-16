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
 * Notifications for paygw_wompi.
 *
 * @package    paygw_wompi
 * @copyright  2025 ingeweb.co <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_wompi;

/**
 * Notifications class.
 *
 * Handle notifications for users about their Wompi transactions.
 *
 * @package    paygw_wompi
 * @copyright  2025 ingeweb.co <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class notifications {

    /**
     * Replace placeholders in a template string.
     *
     * @param string $template The template string with placeholders.
     * @param array $replacements Key-value pairs for replacements.
     * @return string The processed string.
     */
    private static function replace_placeholders(string $template, array $replacements): string {
        foreach ($replacements as $key => $value) {
            $template = str_replace('{' . $key . '}', $value, $template);
        }
        return $template;
    }

    /**
     * Send notification to user about their Wompi transaction.
     *
     * @param int $userid The user ID.
     * @param float $fee The payment amount.
     * @param string $currency The currency code.
     * @param int|string $orderid The order/reference ID.
     * @param string $type The notification type (payment_completed or payment_pending).
     * @param object|null $config Optional gateway configuration with email templates.
     * @return int|false The message ID or false on error.
     */
    public static function notify($userid, $fee, $currency, $orderid, $type = '', $config = null) {
        // Get the user object for messaging.
        $user = \core_user::get_user($userid);
        if (empty($user) || isguestuser($user) || !empty($user->deleted)) {
            return false;
        }

        // Prepare replacement values for placeholders.
        $localizedcost = \core_payment\helper::get_cost_as_string($fee, $currency);
        $replacements = [
            'firstname' => $user->firstname,
            'fullname' => fullname($user),
            'amount' => $localizedcost,
            'currency' => $currency,
            'orderid' => $orderid,
        ];

        // Determine subject and body based on type and config.
        if ($type === 'payment_pending') {
            $subject = !empty($config->email_pending_subject)
                ? $config->email_pending_subject
                : get_string('email_pending_subject_default', 'paygw_wompi');
            $body = !empty($config->email_pending_body)
                ? $config->email_pending_body
                : get_string('email_pending_body_default', 'paygw_wompi');
        } else {
            // Default to payment_completed.
            $subject = !empty($config->email_completed_subject)
                ? $config->email_completed_subject
                : get_string('email_completed_subject_default', 'paygw_wompi');
            $body = !empty($config->email_completed_body)
                ? $config->email_completed_body
                : get_string('email_completed_body_default', 'paygw_wompi');
        }

        // Replace placeholders in subject and body.
        $subject = self::replace_placeholders($subject, $replacements);
        $body = self::replace_placeholders($body, $replacements);

        // Build and send the message.
        $message = new \core\message\message();
        $message->component = 'paygw_wompi';
        $message->name = 'payment_successful';
        $message->userfrom = \core_user::get_noreply_user();
        $message->userto = $user;
        $message->subject = $subject;
        $message->fullmessage = $body;
        $message->fullmessageformat = FORMAT_MARKDOWN;
        $message->fullmessagehtml = nl2br(htmlspecialchars($body));
        $message->notification = 1;
        $message->contexturl = '';
        $message->contexturlname = '';

        $content = ['*' => ['header' => '', 'footer' => '']];
        $message->set_additional_content('email', $content);

        return message_send($message);
    }
}
