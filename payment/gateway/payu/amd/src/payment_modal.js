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
 * PayU payment gateway modal handler.
 *
 * @module     paygw_payu/payment_modal
 * @copyright  2024 Orion Cloud Consulting SAS
 * @author     Alonso Arias <soporte@orioncloud.com.co>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Templates from 'core/templates';
import Modal from 'core/modal';
import Ajax from 'core/ajax';
import Notification from 'core/notification';

/**
 * Show payment modal and handle form submission.
 * 
 * @param {String} component Component name
 * @param {String} paymentArea Payment area
 * @param {String} itemId Item ID
 * @param {String} description Payment description
 * @returns {Promise}
 */
export const process = (component, paymentArea, itemId, description) => {
    return showPaymentModal(component, paymentArea, itemId, description)
        .then((modal) => {
            handlePaymentMethod(modal);
            return modal;
        })
        .catch(Notification.exception);
};

/**
 * Show the payment modal with PayU form.
 * 
 * @param {String} component
 * @param {String} paymentArea
 * @param {String} itemId
 * @param {String} description
 * @returns {Promise<Modal>}
 */
const showPaymentModal = async(component, paymentArea, itemId, description) => {
    const templateContext = {
        component: component,
        paymentarea: paymentArea,
        itemid: itemId,
        description: description,
        sesskey: M.cfg.sesskey,
    };
    
    // Load banks for PSE if needed.
    try {
        const banks = await loadPSEBanks();
        templateContext.banks = banks;
    } catch (error) {
        console.error('Error loading PSE banks:', error);
    }
    
    const modal = await Modal.create({
        title: M.util.get_string('gatewayname', 'paygw_payu'),
        body: await Templates.render('paygw_payu/checkout_modal', templateContext),
        large: true,
        show: true,
        removeOnClose: true,
    });
    
    // Add submit button to modal footer.
    modal.setFooter(await Templates.render('paygw_payu/modal_footer', {}));
    
    return modal;
};

/**
 * Load PSE banks list via AJAX.
 * 
 * @returns {Promise<Array>}
 */
const loadPSEBanks = () => {
    return Ajax.call([{
        methodname: 'paygw_payu_get_pse_banks',
        args: {}
    }])[0];
};

/**
 * Handle payment method selection and form changes.
 * 
 * @param {Modal} modal
 */
const handlePaymentMethod = (modal) => {
    const root = modal.getRoot();
    const form = root.find('#payu-checkout-form');
    const methodSelect = form.find('#paymentmethod');
    
    // Toggle payment method fields.
    methodSelect.on('change', function() {
        const method = $(this).val();
        form.find('.payment-method-fields').addClass('d-none');
        form.find('.payment-method-fields[data-method="' + method + '"]').removeClass('d-none');
        
        // Update required fields based on method.
        updateRequiredFields(form, method);
    });
    
    // Initial toggle.
    methodSelect.trigger('change');
    
    // Handle form submission.
    form.on('submit', function(e) {
        e.preventDefault();
        submitPayment(form, modal);
    });
    
    // Handle modal footer submit button.
    root.find('#payu-submit-payment').on('click', function() {
        form.submit();
    });
};

/**
 * Update required fields based on payment method.
 * 
 * @param {jQuery} form
 * @param {String} method
 */
const updateRequiredFields = (form, method) => {
    // Remove all required attributes first.
    form.find('[required]').removeAttr('required');
    
    // Add required based on method.
    switch(method) {
        case 'creditcard':
            form.find('#cardholder, #cardnumber, #expmonth, #expyear, #cvv').attr('required', true);
            break;
        case 'pse':
            form.find('#psebank, #documentnumber').attr('required', true);
            break;
        case 'nequi':
        case 'bancolombia':
            form.find('#phone').attr('required', true);
            break;
        case 'googlepay':
            form.find('#gp_token').attr('required', true);
            break;
        case 'cash':
            form.find('#documentnumber').attr('required', true);
            break;
    }
};

/**
 * Submit payment form via AJAX.
 * 
 * @param {jQuery} form
 * @param {Modal} modal
 */
const submitPayment = async(form, modal) => {
    // Validate form.
    if (!form[0].checkValidity()) {
        form[0].reportValidity();
        return;
    }
    
    // Show loading.
    modal.getRoot().find('#payu-submit-payment').prop('disabled', true)
        .html(M.util.get_string('processingpayment', 'paygw_payu'));
    
    try {
        // Serialize form data.
        const formData = {};
        form.serializeArray().forEach(field => {
            formData[field.name] = field.value;
        });
        
        // Call AJAX to process payment.
        const response = await Ajax.call([{
            methodname: 'paygw_payu_process_payment',
            args: formData
        }])[0];
        
        if (response.success) {
            // Handle successful response based on payment method.
            if (response.redirect_url) {
                // Redirect to bank or payment page.
                window.location.href = response.redirect_url;
            } else if (response.receipt_url) {
                // Show receipt for cash payments.
                window.open(response.receipt_url, '_blank');
                modal.destroy();
                window.location.reload();
            } else {
                // Payment completed.
                modal.destroy();
                window.location.reload();
            }
        } else {
            // Show error.
            throw new Error(response.message || 'Payment failed');
        }
    } catch (error) {
        Notification.addNotification({
            type: 'error',
            message: error.message || M.util.get_string('paymenterror', 'paygw_payu')
        });
        
        // Re-enable submit button.
        modal.getRoot().find('#payu-submit-payment').prop('disabled', false)
            .html(M.util.get_string('submitpayment', 'paygw_payu'));
    }
};

/**
 * Format and validate phone number for Nequi.
 * 
 * @param {String} phone
 * @returns {String|null}
 */
export const formatPhoneNumber = (phone) => {
    // Remove all non-digits.
    phone = phone.replace(/\D/g, '');
    
    // Check if valid Colombian number.
    if (phone.startsWith('57')) {
        phone = phone.substring(2);
    }
    
    // Must be 10 digits.
    if (phone.length !== 10) {
        return null;
    }
    
    // Must start with 3.
    if (!phone.startsWith('3')) {
        return null;
    }
    
    return '57 ' + phone;
};