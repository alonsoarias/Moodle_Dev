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
 * This module is responsible for handling the Addi payment modal.
 *
 * @module     paygw_addi/gateways_modal
 * @copyright  2025 ingeweb.co <soporte@ingeweb.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import * as Repository from './repository';
import Templates from 'core/templates';
import ModalFactory from 'core/modal_factory';
import ModalEvents from 'core/modal_events';
import {get_string as getString} from 'core/str';

/**
 * Creates and shows a modal that contains the Addi payment button.
 *
 * @param {string} component Payment component
 * @param {string} paymentArea Payment area
 * @param {number} itemId Item id
 * @param {string} description Payment description
 * @returns {Promise}
 */
export const process = (component, paymentArea, itemId, description) => {
    return Promise.all([
        getString('paywithaddi', 'paygw_addi'),
        getString('redirecting', 'paygw_addi'),
        Repository.getConfigForJs(component, paymentArea, itemId),
    ])
    .then(([payButtonLabel, redirectingText, configData]) => {
        // Create the payment URL.
        const payUrl = M.cfg.wwwroot + '/payment/gateway/addi/pay.php?' +
            'component=' + encodeURIComponent(component) +
            '&paymentarea=' + encodeURIComponent(paymentArea) +
            '&itemid=' + encodeURIComponent(itemId) +
            '&description=' + encodeURIComponent(description);

        return Templates.render('paygw_addi/button_placeholder', {
            payUrl: payUrl,
            buttonLabel: payButtonLabel,
            amount: configData.amount,
            currency: configData.currency,
            isValid: configData.isvalid,
            minAmount: configData.minamount,
            maxAmount: configData.maxamount,
        })
        .then((html) => {
            return ModalFactory.create({
                body: html,
                title: payButtonLabel,
                type: ModalFactory.types.CANCEL,
            });
        })
        .then((modal) => {
            modal.getRoot().on(ModalEvents.hidden, () => {
                modal.destroy();
            });

            modal.show();
            return modal;
        });
    });
};
