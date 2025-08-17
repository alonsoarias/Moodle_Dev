// AMD module for quiz retake review page.
import Ajax from 'core/ajax';
import Notification from 'core/notification';
import Templates from 'core/templates';

export const init = (attemptid) => {
    Ajax.call([{
        methodname: 'local_quiz_retake_ui_get_attempt_stats',
        args: {attemptid: attemptid},
        done: data => {
            Templates.render('local_quiz_retake_ui/review_panel', data).then(html => {
                const container = document.querySelector('#region-main');
                container.insertAdjacentHTML('afterbegin', html);
                const root = container.querySelector('.quiz-retake-summary');
                if (data.riskyslots) {
                    data.riskyslots.forEach(slot => {
                        const q = document.querySelector('.que[data-slot="' + slot + '"]');
                        if (q) {
                            q.classList.add('risky');
                        }
                    });
                }
                const filters = root.querySelectorAll('[data-filter]');
                filters.forEach(btn => {
                    btn.addEventListener('click', e => {
                        const filter = e.currentTarget.dataset.filter;
                        document.querySelectorAll('.que').forEach(q => {
                            q.style.display = (filter === 'all' || q.classList.contains(filter)) ? '' : 'none';
                        });
                    });
                });
                const retakebtn = root.querySelector('[data-action="retake"]');
                if (retakebtn) {
                    retakebtn.addEventListener('click', () => {
                        Ajax.call([{methodname: 'local_quiz_retake_ui_create_retake', args: {attemptid: attemptid, mode: 'failed'}, done: data => {window.location = M.cfg.wwwroot + '/mod/quiz/attempt.php?attempt=' + data.newattemptid;}, fail: Notification.exception}]);
                    });
                }
            }).catch(Notification.exception);
        },
        fail: Notification.exception
    }]);
};
