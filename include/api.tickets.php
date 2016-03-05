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
            "message", "ip", "priorityId"
        );
        # Fetch dynamic form field names for the given help topic and add
        # the names to the supported request structure
        if (isset($data['topicId'])
                && ($topic = Topic::lookup($data['topicId']))
                && ($form = $topic->getForm())) {
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
                'in-reply-to', 'references', 'thread-type',
                'flags' => array('bounce', 'auto-reply', 'spam', 'viral'),
                'recipients' => array('*' => array('name', 'email', 'source'))
                ));

            $supported['attachments']['*'][] = 'cid';
        }

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
                    $file['id'] = $fileField->uploadAttachment($file);
                }
                catch (FileUploadError $ex) {
                    $file['error'] = $file['name'] . ': ' . $ex->getMessage();
                }
            }
            unset($file);
        }

        return true;
    }

    function restCreate() {
        $format = $this->contentTypeToFormat($this->getContentType());
        return $this->create($format);
    }

    function create($format) {

        if(!($key=$this->requireApiKey()) || !$key->canCreateTickets())
            return $this->exerr(401, __('API key not authorized'));

        $ticket = null;
        if(!strcasecmp($format, 'email')) {
            # Handle remote piped emails - could be a reply...etc.
            $ticket = $this->processEmail();
        } else {
            # Parse request body
            $ticket = $this->createTicket($this->getRequest($format));
        }

        if(!$ticket)
            return $this->exerr(500, __("Unable to create new ticket: unknown error"));

        $location_base = '/api/tickets/';
        header('Location: '.$location_base.$ticket->getNumber());
        $this->response(201, $ticket->getNumber());
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

        if (($thread = ThreadEntry::lookupByEmailHeaders($data))
                && ($t=$thread->getTicket())
                && ($data['staffId']
                    || !$t->isClosed()
                    || $t->isReopenable())
                && $thread->postEmail($data)) {
            return $thread->getTicket();
        }
        return $this->createTicket($data);
    }


    function restDelete($ticket_number) {

        # FIXME: Create authorization flag for ticket deletion in apikey
        if(!($key=$this->requireApiKey()) || !$key->canCreateTickets())
            return $this->exerr(401, __('API key not authorized'));

        #See if the necessary information are in the request
        if (is_numeric($ticket_number)){
            #Looks if there is a matching ID to the specified number
            $id = Ticket::getIdByNumber($ticket_number);
            if ($id > 0){
                $ticket = new Ticket(0);
                $ticket->load($id);
                $ticket->delete();
                $this->response(200, __("Ticket deleted"));
            }
            else
                $this->response(404, __("Ticket not found"));
        }
        else
            $this->response(415, __("Number not sent"));
    }

    /**
     * RESTful GET ticket
     * 
     * $ticket_number is the external ticket number and is provided by the
     * url dispatcher.
     */
    function restGetTicket($ticket_number) {

        if(!($key=$this->requireApiKey()))
            return $this->exerr(401, __('API key not authorized'));

        # Checks for valid ticket number
        if (!is_numeric($ticket_number))
            return $this->response(404, __("Invalid ticket number"));

        # Checks for existing ticket with that number
        $id = Ticket::getIdByNumber($ticket_number);
        if ($id <= 0)
            return $this->response(404, __("Ticket not found"));

        # Load ticket and send response
        $ticket = new Ticket(0);
        $ticket->load($id);

        $response = array();
        array_push($response, array('number' => $ticket->getNumber()));
        array_push($response, array('subject' => $ticket->getSubject()));
        array_push($response, array('status' => $ticket->getStatus()->getName()));
        array_push($response, array('priority' => $ticket->getPriority()));
        array_push($response, array('department' => $ticket->getDeptName()));
        array_push($response, array('create_timestamp' => $ticket->getCreateDate()));
        array_push($response, array('user_name' => $ticket->getName()->getFull()));
        array_push($response, array('user_email' => $ticket->getEmail()));
        array_push($response, array('user_phone' => $ticket->getPhoneNumber()));
        array_push($response, array('source' => $ticket->getSource()));
        array_push($response, array('ip' => $ticket->getIP()));

        $b = array();
        foreach ($ticket->getAssignees() as $a) {
            if (method_exists($a,"getFull"))
                array_push($b, $a->getFull());
            else
                array_push($b, $a);
        }
        array_push($response, array('assigned_to' => $b));
        unset($b);

        array_push($response, array('sla' => $ticket->getSLA()->getName()));
        array_push($response, array('due_timestamp' => $ticket->getEstDueDate()));
        array_push($response, array('close_timestamp' => $ticket->getCloseDate()));
        array_push($response, array('help_topic' => $ticket->getHelpTopic()));
        array_push($response, array('last_message_timestamp' => $ticket->getLastMsgDate()));
        array_push($response, array('last_response_timestamp' => $ticket->getLastRespDate()));

        # get thread entries
        $tcount = $ticket->getThreadCount();
        $tcount += $ticket->getNumNotes();
        $types = array('M', 'R', 'N');
        $threadTypes=array('M'=>'message','R'=>'response', 'N'=>'note');
        $thread = $ticket->getThreadEntries($types);

        array_push($response, array('thread_count' => $tcount));
        $a = array();
        foreach ($thread as $tentry) {
            array_push($a, array('type', $threadTypes[$tentry['thread_type']]));
            array_push($a, array('timestamp', $tentry['created']));
            array_push($a, array('title', $tentry['title']));
            array_push($a, array('name', $tentry['name']));
            array_push($a, array('body', $tentry['body']->getClean()));
        }
        array_push($response, array('thread_entries' => $a));

        $this->response(200, json_encode($response), $contentType="application/json");
    }

    /**
     * RESTful GET ticket collection
     * 
     * Pagination is made wit Range header.
     * i.e.
     *      Range: items=0-    <-- request all items
     *      Range: items=0-9   <-- request first 10 items
     *      Range: items 10-19 <-- request items 11 to 20
     * 
     * Pagination status is given on Content-Range header.
     * i.e.
     *      Content-Range items 0-9/100 <-- first 10 items retrieved, 100 total items.
     *
     * TODO: Add filtering support
     * 
     */
    function restGetTickets() {

        if(!($key=$this->requireApiKey()))
            return $this->exerr(401, __('API key not authorized'));

        header('Accept-Ranges: items');

        # Parse and check range header
        $range = 'items=0-';
        foreach (getallheaders() as $k => $v) {
            if (strcasecmp('range', $k) == 0) {
                $range = $v;
                break;
            }
        }
        $range = explode('=',$range);

        # check if range is expressed in items
        if (strcasecmp($range[0], 'items') != 0)
            return $this->exerr(416, __('Requested Range Not Satisfiable'));

        # check if values are numeric
        $range = explode('-', $range[1]);
        foreach ($range as $v) {
            if (strlen($v)>0 && !is_numeric($v))
                return $this->exerr(416, __('Requested Range Not Satisfiable'));
            }

        # cast values to integer
        $range_low = intval($range[0]);
        if (strlen($range[1])>0) {
            $range_high = intval($range[1]);
            }
        else
            $range_high = -1; # empty range_high

        if ($range_low < 0)
            return $this->exerr(416, __('Requested Range Not Satisfiable'));

        # determine range length and check
        $range_len = $range_high - $range_low + 1;
        if ($range_len < 1)
            return $this->exerr(416, __('Requested Range Not Satisfiable'));

        # limit query to 100 items
        $range_len_max = 100;
        header("X-Request-Range-Maximum-Length: ${range_len_max})");
        if (($range_len > $range_len_max) || ($range_high < 0))
            $range_len = $range_len_max;

        $range_high_request = $range_low + $range_len - 1;
        header("X-Request-Range: items=${range_low}-${range_high_request}");

        # Build query
        $qfields = array('number', 'created', 'updated', 'closed',
             'lastmessage', 'lastresponse');

        $q = 'SELECT SQL_CALC_FOUND_ROWS ';
        foreach ($qfields as $f) {
            $q.=$f.',';
            }
        $q=rtrim($q, ',');

        $qfrom = ' FROM '.TICKET_TABLE;
        $q .= $qfrom;

        $qlimit = " LIMIT ${range_len} OFFSET ${range_low}";
        $q .= $qlimit;

        $q2 = 'SELECT FOUND_ROWS()';

        $res = db_query($q);

        # get total number of tickets
        $res2 = db_query($q2);
        $row = $res2->data_seek(0);
        $row = $res2->fetch_assoc();
        $found_rows = $row['FOUND_ROWS()'];
        mysqli_free_result($res2);
        unset($row);

        $tickets = array();
        $result_rows = $res->num_rows -1;
        for ($row_no = 0; $row_no <= $result_rows; $row_no++) {
            $res->data_seek($row_no);
            $row = $res->fetch_assoc();
            $ticket = array();
            foreach ($qfields as $f) {
                array_push($ticket, array($f, $row[$f]));
                }
            array_push($ticket, array('href', '/api/tickets/'.$row['number']));
            array_push($tickets, $ticket);
        }

        # Replace range_high with the actual number of rows
        # returned
        $range_high = $range_low + $result_rows;

        header("Content-Range: items ${range_low}-${range_high}/${found_rows}");
        # Partial or full collection?
        if ($result_rows < $found_rows)
            $result_code = 200;
        else
            $result_code = 206; # 206 Partial Content

        $this->response($result_code, json_encode($tickets),
             $contentType="application/json");
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
