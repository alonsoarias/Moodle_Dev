# moodle-enrol_yafee

[![](https://img.shields.io/github/v/release/Snickser/moodle-enrol_yafee.svg)](https://github.com/Snickser/moodle-enrol_yafee/releases)
[![Build Status](https://github.com/Snickser/moodle-enrol_yafee/actions/workflows/moodle-ci.yml/badge.svg)](https://github.com/Snickser/moodle-enrol_yafee/actions/workflows/moodle-ci.yml)

Ещё один модуль записи за оплату, с добавлением функции нотификации об истечении срока обучения и повторной оплаты.

------

"Yet another" Enrolment on payment plugin offers you the ability to create paid courses, with the addition of a notification function about the expiration of the training period and re-payment.

This plugin contains the following features on top of the core payment plugin:
* Course payment is available for suspended students.
* Notifications can be triggered about the expiration of the training period and re-payment.
* Extended enrolment duration, calendar month and calendar year.
* Can show enrolment duration on page.
* Free trial enrolment option.
* Renew enrolment duration by paying again using the link in the navigation menu when it's about to expire.
* Payment for missed unpaid time.
* Manual enrol and unenrol users.
* Uses group enrolment keys.

By enabling the appropriate settings, you can limit the time of paid registration of students for the course. After the set time, access to the course is suspended and can be resumed by re-payment. Course payment is available for suspended students, unlike other plugins.

You can enable sending notifications about the upcoming student disconnection from the course. The frequency of sending is once a day.

This plugin is versatile, but it was originally designed to support my payment plugins to allow for recurring payments:

- [Robokassa](https://github.com/Snickser/moodle-paygw_robokassa)
- [YooKassa](https://github.com/Snickser/moodle-paygw_yookassa)
- [bePaid](https://github.com/Snickser/moodle-paygw_bepaid)
