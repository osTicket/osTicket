osTicket Forms API
==================

osTicket now includes a (relatively) complete forms API. Forms can be
created as objects and used to render their corresponding widgets to the
screen without actually writing and HTML.

Defining a form
---------------
Instanciate a form from a list of fields. PHP unfortunately does not allow
one to create a class and instanciate a list of instances into a static
property. Because of this limitation, the field list is specified at the
form instance construction time by passing the list of fields into the
constructor.

The simplest way to create forms is to instanciate the Form instance
directly:

    $form = new SimpleForm(array(
        'email' => new TextboxField(array('label'=>'Email Address')),
    );

The form can then be rendered to HTML and sent to the user. Later the form
can be recreated after a POST and the data from the request will
automatically be placed into the form. Check if the form is valid:

    if ($form->isValid())
        $object->update($form->getClean());

The `getClean()` method will return a hash array, where the keys are the
keys in the field array passed to the form constructor, and the values are
the cleaned values from the form fields based on the data from the request.

To create a class that defines the fields statically, one might write a
trampoline constructor:

    class UserForm extends SimpleForm {
        function __construct() {
            $args = func_get_args();
            $fields = array(
                'email' => new TextboxField(array(
                    'label'=>'Email Address')
                ),
            );
            array_unshift($args, $fields);
            call_user_func_array(array('parent','__construct'), $args);
        }
    }

Here, the fields are defined statically in the constructor. Do not bother
trying to specify the fields in a static property. You'll end up crying.
