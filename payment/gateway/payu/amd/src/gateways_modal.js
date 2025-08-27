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
 * This module handles PayU content in the gateways modal.
 *
 * @module     paygw_payu/gateways_modal
 * @copyright  2024 Orion Cloud Consulting SAS
 * @author     Alonso Arias <soporte@orioncloud.com.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['core/templates', 'core/modal'], function(Templates, Modal) {
    'use strict';
    
    /**
     * Process payment gateway selection.
     *
     * @param {String} component
     * @param {String} paymentArea
     * @param {String} itemId
     * @param {String} description
     * @returns {Promise}
     */
    const process = function(component, paymentArea, itemId, description) {
        return showModalWithPlaceholder()
            .then(function() {
                // Redirect to method selection page.
                location.href = M.cfg.wwwroot + '/payment/gateway/payu/method.php?' +
                    'sesskey=' + M.cfg.sesskey +
                    '&component=' + component +
                    '&paymentarea=' + paymentArea +
                    '&itemid=' + itemId +
                    '&description=' + encodeURIComponent(description);
                
                return new Promise(function() {
                    // Keep promise pending to prevent modal closing.
                });
            });
    };
    
    /**
     * Show modal with PayU placeholder.
     *
     * @returns {Promise}
     */
    const showModalWithPlaceholder = async function() {
        const modal = await Modal.create({
            body: await Templates.render('paygw_payu/button_placeholder', {}),
            show: true,
            removeOnClose: true,
        });
        
        setTimeout(function() {
            modal.destroy();
        }, 100);
        
        return Promise.resolve();
    };
    
    return {
        process: process
    };
});