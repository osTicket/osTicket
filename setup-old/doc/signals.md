osTicket Signals API
====================
osTicket uses a very simple publish and subscribe signal model to add
extensibility. To keep things simplistic between classes and to maintain
compatibility with PHP version 4, signals will not be explicitly defined or
registered. Instead, signals are connected to callbacks via a string signal
name.

The system is proofed with a static inspection test which will ensure that
for every given Signal::connect() function call, somewhere else in the
codebase there exists a Signal::send() for the same-named signal.

Publishing a signal
-------------------
    $info = array('username'=>'blah');
    Signal::send('signal.name', $this, $info);

All subscribers to the signal will be called in the order they connect()ed
to the signal. Subscribers do not have the opportunity to interrupt or
discontinue delivery of the signal to other subscribers. The $object
argument is required and should almost always be ($this). Its interpretation
is the object originating or sending the signal. It could also be
interpreted as the context of the signal.

$data if sent should be a hash-array of data included with the signal event.
There is otherwise no definition for what should or could be included in the
$data array. The received data is received by reference and can be passed to
the callable by reference, if the callable is defined to receive it by
reference. Therefore, it is possible to propagate changes in the signal
handlers back to the originating context.

Connecting to a signal
----------------------
    Signal::connect('signal.name', 'function', optional 'check_callable');

The subscribed function should receive two arguments and will have this
signature:

    function callback($object, $data);

Where the $object argument is the object originating the signal, called the
context, and the $data is a hash-array of other information originating
from- and pertaining to the signal.

The exact value of the $data argument is not defined. It is signal specific.
It should be a hash-array of data; however, no runtime checks are made to
ensure such an interface.

Optionally, if $object is a class and is passed into the ::connect() method,
only instances of the named class or subclass will actually be connected to
the callable function.

A predicate function, $check, can be used to filter calls to the signal
handler. The function will receive the signal data and should return true if
the signal handler should be called.

Signals in osTicket
-------------------
#### ajax.client
Sent before an AJAX request is processed for the client interface

Context:
Object<Dispatcher> - Dispatcher used to resolve and service the request

Parameters:
(none)

#### ajax.scp
Sent before an AJAX request is processed for the staff interface

Context:
Object<Dispatcher> - Dispatcher used to resolve and service the request

Parameters:
(none)

#### auth.login.succeeded
Sent after a successful login is process for a user

Context:
Object<StaffSession> - Staff object retrieved from the login credentials

Parameters:
(none)

#### auth.login.failed
Sent after an unsuccessful login is attempted by a user.

Context:
null

Arguments:
  * **username**: *read-only* username submitted to the login form
  * **passowrd**: *read-only* password submitted to the login form

#### auth.pwreset.email
Sent just before an email is sent to the user with the password reset token

Context:
Object<Staff> - Staff object who will receive the email

Parameters:
  * **email**: *read-only* email object used to send the email
  * **vars**: (array) template variables used to render the password-reset
        email template
  * **log**: (bool) TRUE if a log should be appended to the system log
        concerning the password reset attempt

#### auth.pwreset.login
Sent just before processing the automatic login for the staff from the link
and token provided in the password-reset email. This signal is only sent if
the token presented is considered completely valid and the password for the
staff is forced to-be-changed.

Context:
Object<Staff> - Staff being logged in from the reset token

Parameters:
  * **page**: Page / URL sent in the redirect to the user. In other words,
        the next page the staff will see.

#### auth.pwchange
Sent when the password for a user is changed

Context:
Object<Staff> - Staff whose password is being changed

Parameters:
  * **password**: New password (clear-text) for the user

#### cron
Sent at the end of a cron run

Context:
null
