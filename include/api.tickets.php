<?php

include_once INCLUDE_DIR.'class.api.php';
include_once INCLUDE_DIR.'class.ticket.php';

class TicketApiController extends ApiController {

    # Supported arguments -- anything else is an error. These items will be
    # inspected _after_ the fixup() method of the ApiXxxDataParser classes
    # so that all supported input formats should be supported
    function getRequestStructure($format, $data=null) {
        $supported = array(
            "alert", "autorespond", "source", "topicId",
            "attachments" => array("*" =>
                array("name", "type", "data", "encoding", "size")
            ),
            "message", "ip", "priorityId",
            "system_emails" => array(
                "*" => "*"
            ),
            "thread_entry_recipients" => array (
                "*" => array("to", "cc")
            )
        );
        # Fetch dynamic form field names for the given help topic and add
        # the names to the supported request structure
        if (isset($data['topicId'])
                && ($topic = Topic::lookup($data['topicId']))
                && ($forms = $topic->getForms())) {
            foreach ($forms as $form)
                foreach ($form->getDynamicFields() as $field)
                    $supported[] = $field->get('name');
        }

        # Ticket form fields
        # TODO: Support userId for existing user
        if(($form = TicketForm::getInstance()))
            foreach ($form->getFields() as $field)
                $supported[] = $field->get('name');

        # User form fields
        if(($form = UserForm::getInstance()))
            foreach ($form->getFields() as $field)
                $supported[] = $field->get('name');

        if(!strcasecmp($format, 'email')) {
            $supported = array_merge($supported, array('header', 'mid',
                'emailId', 'to-email-id', 'ticketId', 'reply-to', 'reply-to-name',
                'in-reply-to', 'references', 'thread-type', 'system_emails',
                'mailflags' => array('bounce', 'auto-reply', 'spam', 'viral'),
                'recipients' => array('*' => array('name', 'email', 'source'))
                ));

            $supported['attachments']['*'][] = 'cid';
        }
        $supported[]='userId';  //Just done to supress log warnings.  Figure out the "right" way to do this.
        return $supported;
    }

    /*
     Validate data - overwrites parent's validator for additional validations.
    */
    function validate(&$data, $format, $strict=true) {
        global $ost;

        //Call parent to Validate the structure
        if(!parent::validate($data, $format, $strict) && $strict)
            $this->exerr(400, __('Unexpected or invalid data received'));

        // Use the settings on the thread entry on the ticket details
        // form to validate the attachments in the email
        $tform = TicketForm::objects()->one()->getForm();
        $messageField = $tform->getField('message');
        $fileField = $messageField->getWidget()->getAttachments();

        // Nuke attachments IF API files are not allowed.
        if (!$messageField->isAttachmentsEnabled())
            $data['attachments'] = array();

        //Validate attachments: Do error checking... soft fail - set the error and pass on the request.
        if ($data['attachments'] && is_array($data['attachments'])) {
            foreach($data['attachments'] as &$file) {
                if ($file['encoding'] && !strcasecmp($file['encoding'], 'base64')) {
                    if(!($file['data'] = base64_decode($file['data'], true)))
                        $file['error'] = sprintf(__('%s: Poorly encoded base64 data'),
                            Format::htmlchars($file['name']));
                }
                // Validate and save immediately
                try {
                    $F = $fileField->uploadAttachment($file);
                    $file['id'] = $F->getId();
                }
                catch (FileUploadError $ex) {
                    $name = $file['name'];
                    $file = array();
                    $file['error'] = Format::htmlchars($name) . ': ' . $ex->getMessage();
                }
            }
            unset($file);
        }

        return true;
    }


    public function create($format) {

        if(!($key=$this->requireApiKey()) || !$key->canCreateTickets())
            return $this->exerr(401, __('API key not authorized'));

        $ticket = null;
        if(!strcasecmp($format, 'email')) {
            # Handle remote piped emails - could be a reply...etc.
            $ticket = $this->processEmail();
        } else {
            # Parse request body
            $params=$this->getRequest($format);
            if(empty($params['email'])) {
                $user=$this->getUser($params);
                $params['email']=$user->getEmail();
                unset($params['userId']);
            }
            $ticket = $this->createTicket(array_merge($params, ['source'=>'api']));
        }

        if(!$ticket)
            return $this->exerr(500, __("Unable to create new ticket: unknown error"));

        $this->response(201, json_encode($ticket->getTicketApiEntity()));
    }

    ######  Added Methods ############
    //All methods assume dispatcher validates arguments as integers and handles errors (i.e. (?P<tid>\d+) ).
    //Most tickets require userId or email in parameters (not necessarily for authentication, but to log who made a change)
    //TBD whether camelcase or underscore property names are desired by osTicket.

    public function getTickets($format) {
        //Future:  Allow for optional filtering for name and topic ID
        if(!($key=$this->requireApiKey()) || !$key->canViewTickets())
            return $this->exerr(401, __('API key not authorized'));
        $filter=['user_id' => isset($_GET['userId'])?$_GET['userId']:$this->getUser($_GET)->getId()];
        if(isset($_GET['status_id'])) {
            $filter['status_id']=$_GET['status_id'];
        }
        $tickets = [];
        foreach(Ticket::objects()->filter($filter)->all() as $ticket) {
            $tickets[]=$ticket->getTicketApiEntity();
        }
        $this->response(200, json_encode($tickets));
    }

    public function getTicket($format, $tid) {
        //This API request does not need to provide user identifier.
        if(!($key=$this->requireApiKey()) || !$key->canViewTickets())
            return $this->exerr(401, __('API key not authorized'));
        $this->response(200, json_encode($this->getByTicketId($tid)->getTicketApiEntity()));
    }

    public function closeTicket($format, $tid) {
        if(!($key=$this->requireApiKey()) || !$key->canCloseTickets())
            return $this->exerr(401, __('API key not authorized'));
        $ticket = $this->getByTicketId($tid);
        //$ticket->setStatusId(3);
        //$currentStatus=$ticket->getStatus();
        $status= TicketStatus::lookup(3);
        $errors=[];//passed by reference
        $ticket->setStatus($status, 'Closed by user', $errors);
        $this->response(204, null);
    }
    public function reopenTicket($format, $tid) {
        if(!($key=$this->requireApiKey()) || !$key->canReopenTickets())
            return $this->exerr(401, __('API key not authorized'));
        $ticket = $this->getByTicketId($tid);
        $ticket->reopen();
        $this->response(200, json_encode($ticket->getTicketApiEntity()));
    }
    public function updateTicket($format, $tid) {
        if(!($key=$this->requireApiKey()) || !$key->canUpdateTickets())
            return $this->exerr(401, __('API key not authorized'));
        $params = $this->getRequest($format);
        $user=$this->getUser($params);
        $ticket = $this->getByTicketId($tid, $user);
        $vars=[
            'message'=>$params['message'],
            'userId'=>$user->getId(),
            'poster'=>$user->getFullName(),
            'ip_address'=>$_SERVER['REMOTE_ADDR'] //Use web client's IP
        ];
        $response = $ticket->postMessage($vars, 'api');//Ticket::postMessage($vars, $origin='', $alerts=true)
        $this->response(200, json_encode($ticket->getTicketApiEntity()));
    }
    public function getTopics($format) {
        //This API request does not need to provide user identifier.
        if(!($key=$this->requireApiKey()) || !$key->canViewTopics())
            return $this->exerr(401, __('API key not authorized'));
        $this->response(200, json_encode($this->createList(Topic::getPublicHelpTopics(), 'id', 'value')));
    }
    // Private methods to support new api methods.  Verify if existing osTicket methods should be used instead.
    private function getByTicketId($ticketId) {
        if(!$pk=Ticket::getIdByNumber($ticketId))
            return $this->exerr(400, __("Ticket Number '$ticketId' does not exist"));
        return $this->getByPrimaryId($pk);
    }
    private function getByPrimaryId($pk) {
        if(!$ticket = Ticket::lookup($pk))
            return $this->exerr(400, __("Ticket ID '$pk' does not exist"));
        return $ticket;
    }
    private function getUser($params=[]) {
        //userId or email must be provided in request parameters
        if(!empty($params['userId'])){
            if(!$user = TicketUser::lookupById($params['userId'])) {
                return $this->exerr(400, __("Invalid user.  User ID does not exist."));
            }
        }
        elseif(!empty($params['email'])){
            if(!$user = TicketUser::lookupByEmail($params['email'])) {
                return $this->exerr(400, __("Invalid user.  User email does not exist."));
            }
        }
        else {
            return $this->exerr(400, __("Either user ID or email must be provided in request"));
        }
        return $user;
    }
    private function createList($items, $idName, $valueName) {
        $list=[];
        foreach($items as $key=>$value) {
            $list[]=[$idName=>$key, $valueName=>$value];
        }
        return $list;
    }

    /* private helper functions */

    function createTicket($data) {

        # Pull off some meta-data
        $alert       = (bool) (isset($data['alert'])       ? $data['alert']       : true);
        $autorespond = (bool) (isset($data['autorespond']) ? $data['autorespond'] : true);

        # Assign default value to source if not defined, or defined as NULL
        $data['source'] = isset($data['source']) ? $data['source'] : 'API';

        # Create the ticket with the data (attempt to anyway)
        $errors = array();

        $ticket = Ticket::create($data, $errors, $data['source'], $autorespond, $alert);
        # Return errors (?)
        if (count($errors)) {
            if(isset($errors['errno']) && $errors['errno'] == 403)
                return $this->exerr(403, __('Ticket denied'));
            else
                return $this->exerr(
                        400,
                        __("Unable to create new ticket: validation errors").":\n"
                        .Format::array_implode(": ", "\n", $errors)
                        );
        } elseif (!$ticket) {
            return $this->exerr(500, __("Unable to create new ticket: unknown error"));
        }

        return $ticket;
    }

    function processEmail($data=false) {

        if (!$data)
            $data = $this->getEmailRequest();

        $seen = false;
        if (($entry = ThreadEntry::lookupByEmailHeaders($data, $seen))
            && ($message = $entry->postEmail($data))
        ) {
            if ($message instanceof ThreadEntry) {
                return $message->getThread()->getObject();
            }
            else if ($seen) {
                // Email has been processed previously
                return $entry->getThread()->getObject();
            }
        }

        // Allow continuation of thread without initial message or note
        elseif (($thread = Thread::lookupByEmailHeaders($data))
            && ($message = $thread->postEmail($data))
        ) {
            return $thread->getObject();
        }

        // All emails which do not appear to be part of an existing thread
        // will always create new "Tickets". All other objects will need to
        // be created via the web interface or the API
        return $this->createTicket($data);
    }

}

//Local email piping controller - no API key required!
class PipeApiController extends TicketApiController {

    //Overwrite grandparent's (ApiController) response method.
    function response($code, $resp) {

        //Use postfix exit codes - instead of HTTP
        switch($code) {
            case 201: //Success
                $exitcode = 0;
                break;
            case 400:
                $exitcode = 66;
                break;
            case 401: /* permission denied */
            case 403:
                $exitcode = 77;
                break;
            case 415:
            case 416:
            case 417:
            case 501:
                $exitcode = 65;
                break;
            case 503:
                $exitcode = 69;
                break;
            case 500: //Server error.
            default: //Temp (unknown) failure - retry
                $exitcode = 75;
        }

        //echo "$code ($exitcode):$resp";
        //We're simply exiting - MTA will take care of the rest based on exit code!
        exit($exitcode);
    }

    function  process() {
        $pipe = new PipeApiController();
        if(($ticket=$pipe->processEmail()))
           return $pipe->response(201, $ticket->getNumber());

        return $pipe->exerr(416, __('Request failed - retry again!'));
    }
}

?>
