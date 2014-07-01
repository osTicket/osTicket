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
            $this->exerr(400, 'Unexpected or invalid data received');

        //Nuke attachments IF API files are not allowed.
        if(!$ost->getConfig()->allowAPIAttachments())
            $data['attachments'] = array();

        //Validate attachments: Do error checking... soft fail - set the error and pass on the request.
        if($data['attachments'] && is_array($data['attachments'])) {
            foreach($data['attachments'] as &$attachment) {
                if(!$ost->isFileTypeAllowed($attachment))
                    $attachment['error'] = 'Invalid file type (ext) for '.Format::htmlchars($attachment['name']);
                elseif ($attachment['encoding'] && !strcasecmp($attachment['encoding'], 'base64')) {
                    if(!($attachment['data'] = base64_decode($attachment['data'], true)))
                        $attachment['error'] = sprintf('%s: Poorly encoded base64 data', Format::htmlchars($attachment['name']));
                }
                if (!$attachment['error']
                        && ($size = $ost->getConfig()->getMaxFileSize())
                        && ($fsize = $attachment['size'] ?: strlen($attachment['data']))
                        && $fsize > $size) {
                    $attachment['error'] = sprintf('File %s (%s) is too big. Maximum of %s allowed',
                            Format::htmlchars($attachment['name']),
                            Format::file_size($fsize),
                            Format::file_size($size));
                }
            }
            unset($attachment);
        }

        return true;
    }


    function create($format) {

        if(!($key=$this->requireApiKey()) || !$key->canCreateTickets())
            return $this->exerr(401, 'API key not authorized');

        $ticket = null;
        if(!strcasecmp($format, 'email')) {
            # Handle remote piped emails - could be a reply...etc.
            $ticket = $this->processEmail();
        } else {
            # Parse request body
            $ticket = $this->createTicket($this->getRequest($format));
        }

        if(!$ticket)
            return $this->exerr(500, "Unable to create new ticket: unknown error");

        $this->response(201, $ticket->getNumber());
    }

    /* private helper functions */

    function createTicket($data) {

        # Pull off some meta-data
        $alert = $data['alert'] ? $data['alert'] : true;
        $autorespond = $data['autorespond'] ? $data['autorespond'] : true;
        $data['source'] = $data['source'] ? $data['source'] : 'API';

        # Create the ticket with the data (attempt to anyway)
        $errors = array();

        $ticket = Ticket::create($data, $errors, $data['source'], $autorespond, $alert);
        # Return errors (?)
        if (count($errors)) {
            if(isset($errors['errno']) && $errors['errno'] == 403)
                return $this->exerr(403, 'Ticket denied');
            else
                return $this->exerr(
                        400,
                        "Unable to create new ticket: validation errors:\n"
                        .Format::array_implode(": ", "\n", $errors)
                        );
        } elseif (!$ticket) {
            return $this->exerr(500, "Unable to create new ticket: unknown error");
        }

        return $ticket;
    }

    function processEmail($data=false) {

        if (!$data)
            $data = $this->getEmailRequest();

        if (($thread = ThreadEntry::lookupByEmailHeaders($data))
                && $thread->postEmail($data)) {
            return $thread->getTicket();
        }
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

        return $pipe->exerr(416, 'Request failed - retry again!');
    }
}

?>
