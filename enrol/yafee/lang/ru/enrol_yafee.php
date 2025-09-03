<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Strings for component 'enrol_yafee', language 'ru'.
 *
 * @package     enrol_yafee
 * @category    string
 * @copyright 2024 Alex Orlov <snickser@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['assignrole'] = 'Назначить роль';
$string['cost'] = 'Оплата за зачисление';
$string['costerror'] = 'Стоимость должна быть указана в виде числа больше ноля, и максимум два знака в дробной части.';
$string['currency'] = 'Валюта';
$string['defaultgroup'] = 'Группа по умолчанию';
$string['defaultrole'] = 'Роль по умолчанию';
$string['defaultrole_desc'] = 'Выберите роль, которая будет назначена самостоятельно записанным пользователям';
$string['donate'] = '<div>Версия плагина: {$a->release} ({$a->versiondisk})<br>
Новые версии плагина вы можете найти на <a href=https://github.com/Snickser/moodle-enrol_yafee>GitHub.com</a>
<img src="https://img.shields.io/github/v/release/Snickser/moodle-enrol_yafee.svg"><br>
Пожалуйста, отправьте мне немножко <a href="https://yoomoney.ru/fundraise/143H2JO3LLE.240720">доната</a>😊</div>
BTC 1GFTTPCgRTC8yYL1gU7wBZRfhRNRBdLZsq<br>
TRX TRGMc3b63Lus6ehLasbbHxsb2rHky5LbPe<br>
ETH 0x1bce7aadef39d328d262569e6194febe597cb2c9<br>
<iframe src="https://yoomoney.ru/quickpay/fundraise/button?billNumber=143H2JO3LLE.240720"
width="330" height="50" frameborder="0" allowtransparency="true" scrolling="no"></iframe>';
$string['editselectedusers'] = 'Изменить зачисления выбранных пользователей';
$string['enrolenddate'] = 'Конечная дата';
$string['enrolenddate_help'] = 'Если параметр включен, то пользователи могут самостоятельно записаться только до этой даты.';
$string['enrolenddaterror'] = 'Дата окончания записи не может быть ранее даты ее начала';
$string['enrolperiod'] = 'Продолжительность обучения ({$a->desc}): {$a->count}';
$string['enrolperiod_desc'] = 'Продолжительность обучения по умолчанию. Если установлен ноль, то, по умолчанию, продолжительность обучения не будет ограничена.';
$string['enrolperiod_help'] = 'Продолжительность обучения, начиная с момента самостоятельной записи пользователя на курс. Если не включать этот параметр, то продолжительность обучения не будет ограничена.';
$string['enrolperiodend'] = 'После оплаты подписка будет продлена до {$a->date} {$a->time}';
$string['enrolstartdate'] = 'Начальная дата';
$string['enrolstartdate_help'] = 'При включенном параметре пользователи могут самостоятельно записаться после этой даты.';
$string['expiredaction'] = 'Действие при истечении срока зачисления';
$string['expiredaction_help'] = 'Выберите выполняемое действие при истечении срока записи пользователя в курсе. Обратите внимание, что из курса удаляются некоторые настройки и данные пользователя при приостановке или исключении его из курса. Для функции исключения или приостановки необходимо включить задачу \enrol_yafee\task\sync_enrolments в планировщике Moodle.';
$string['expiredmessagebody'] = 'Уважаемый(ая) {$a->fullname}, уведомляем, что ваше обучение в курсе «{$a->course}» приостановлено.

Чтобы продлить обучение перейдите по ссылке {$a->payurl}
';
$string['expiredmessagesubject'] = 'Уведомление об окончании срока обучения';
$string['expirymessageenrolledbody'] = 'Уважаемый(ая) {$a->user}, уведомляем, что обучение в курсе «{$a->course}» истекает {$a->timeend}.

При необходимости свяжитесь с {$a->enroller}.';
$string['expirymessageenrolledsubject'] = 'Уведомление об истечении срока обучения';
$string['expirymessageenrollerbody'] = 'Оплаченная запись в курсе «{$a->course}» истекает в течение следующих {$a->threshold} для перечисленных пользователей:

{$a->users}.

Чтобы продлить их обучение, перейдите на {$a->extendurl}';
$string['expirymessageenrollersubject'] = 'Уведомление об истечении срока обучения';
$string['expirynotifyall'] = 'Преподавателя и учащегося';
$string['expirynotifyenroller'] = 'Только преподавателя';
$string['expirynotifyperiod'] = 'Интервал отправки уведомления о приостановке обучения';
$string['expirynotifyperiod_desc'] = 'Отправка уведомлений о приостановке доступа к курсу после истечения срока зачисления пользователя. Данный параметр должен быть равен периоду выполнения планировщика отправки уведомлений enrol_yafee\task\send_expiry_notifications, если меньше - сообщения отправляться не будут, если больше - они будут отправляться несколько раз.';
$string['extremovedsuspendnoroles'] = 'Приостановить участие в курсе и удалить роли';
$string['forcepayment'] = 'Игнорировать даты зачисления в курс для оплаты';
$string['forcepayment_help'] = 'Если установлено, форма оплаты, доступная по ссылке в период порога уведомления, будет доступна игнорируя установленные даты начала или конца зачисления в курс. Например, когда запись в курс уже закрыта, то записанные ранее студенты смогут продолжать оплату обучения.';
$string['freetrial'] = 'Бесплатная пробная запись';
$string['freetrial_desc'] = 'Доступно ознакомительное время ({$a->count} {$a->desc})';
$string['freetrial_help'] = 'Позволяет пользователю открыть курс один раз на определенный период времени без оплаты.';
$string['freetrialbutton'] = 'Войти';
$string['groupkeytext'] = 'Нажмите здесь для ввода пароля группы, при наличии и необходимости.';
$string['groupkeytextforce'] = 'Для записи в курс требуется ввести пароль группы.';
$string['groupsuccess'] = 'Кодовое слово успешно принято';
$string['managemanualenrolements'] = 'Зачисление пользователей YaFee';
$string['menuname'] = '<font color=red><b>Оплатить курс</b></font>';
$string['menunameshort'] = '<font color=red><b>Оплатить курс</b></font>';
$string['messageprovider:expiry_notification'] = 'Уведомления об истечении срока обучения при самостоятельной записи';
$string['newenrols'] = 'Разрешить новые зачисления';
$string['newenrols_desc'] = 'По умолчанию разрешать пользователям автоматическое зачисление в новые курсы.';
$string['newenrols_help'] = 'Этот параметр определяет, могут ли новые пользователи записаться на этот курс, или только записанные продлить (продолжить) обучение.';
$string['paymentrequired'] = 'Для участия в этом курсе требуется вступительный взнос.';
$string['pluginname'] = 'Новое Зачисление за оплату';
$string['pluginname_desc'] = 'Метод платной регистрации позволяет вам устанавливать курсы, требующие оплаты. Существует плата за весь сайт, которую вы устанавливаете здесь как значение по умолчанию для всего сайта, а затем настройка курса, которую вы можете установить для каждого курса индивидуально. Плата за курс перекрывает плату за сайт.';
$string['privacy:metadata'] = 'Плагин не хранит никаких персональных данных.';
$string['renewenrolment'] = 'Продление платной подписки';
$string['renewenrolment_text'] = 'Стоимость продления';
$string['role'] = 'Роль, назначаемая по умолчанию';
$string['sendexpirynotificationstask'] = 'Задача отправки уведомлений об истечении срока действия самостоятельного зачисления';
$string['sendpaymentbutton'] = 'Выбрать способ оплаты';
$string['showduration'] = 'Показывать длительность обучения на странице';
$string['status'] = 'Поддерживать активной текущую самостоятельную регистрацию';
$string['status_desc'] = 'Использовать способ самостоятельной регистрации в новых курсах (отключен по умолчанию).';
$string['syncenrolmentstask'] = 'Синхронизация самостоятельного зачисления';
$string['thisyear'] = 'Начало года';
$string['uninterrupted'] = 'Оплачивать пропущенное время';
$string['uninterrupted_desc'] = 'Цена за курс сформирована с учётом пропущенного времени неоплаченного вами периода ({$a}).';
$string['uninterrupted_help'] = 'К цене курса прибавляется стоимость времени перерыва с последней оплаты. Включается только в курсах с установленной продолжительностью обучения.';
$string['uninterrupted_warn'] = '<font color=red>Работает только в платёжных модулях bePaid, Robokassa, YooKassa, PayAnyWay!</font>';
$string['validationerror'] = 'Регистрация не может быть включена без указания платежного счета';
