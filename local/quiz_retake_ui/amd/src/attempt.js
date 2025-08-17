// AMD module to handle risky checkbox during quiz attempt.
import Ajax from 'core/ajax';
import Notification from 'core/notification';

export const init = (attemptid) => {
    document.querySelectorAll('.que').forEach(q => {
        const slot = q.dataset.slot;
        if (!slot) {
            return;
        }
        const container = q.querySelector('.formulation');
        if (!container) {
            return;
        }
        const label = document.createElement('label');
        label.classList.add('ml-2');
        const checkbox = document.createElement('input');
        checkbox.type = 'checkbox';
        checkbox.classList.add('quiz-retake-risky');
        checkbox.addEventListener('change', e => {
            Ajax.call([{
                methodname: 'local_quiz_retake_ui_toggle_risky',
                args: {attemptid: attemptid, slot: slot, state: e.target.checked},
                fail: Notification.exception
            }]);
        });
        label.append(checkbox, ' ', M.util.get_string('markrisky', 'local_quiz_retake_ui'));
        container.appendChild(label);
    });
};
