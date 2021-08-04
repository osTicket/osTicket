<?php

include_once INCLUDE_DIR.'class.api.php';
include_once INCLUDE_DIR.'class.ticket.php';

class TicketApiController extends ApiController {
	function getRequest($format)
	{
		// The parent class defines stuff to pass data in the _body_ of the request
		// and allows for various formats json/xml/email
		// We are less clever and just want to process GET/POST data in the traditional way
		// so if there is a format specified, pass this up to the parent to fetch the request
		// data, otherwise we are just going to use the traditional $_GET/$_POST arrays which
		// host the request data
		if ($format)
			return parent::getRequest($format);

		if ($_SERVER['REQUEST_METHOD'] == "POST")
			return $_POST;
		else
			return $_GET;
	}
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

	/* Ticket Create API */
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
	/*
	 * Return a list of tickets
	 * 
	 * passed search criteria
	 *		'status' timesheet status name from TicketStatus
	 *
	 * returns a JSON encoded list of tickets which match the search criteria
	 */
    function listTickets() {
        if(!($key=$this->requireApiKey())) // || !$key->canCreateTickets())
            return $this->exerr(401, __('API key not authorized'));

		// Get passed data from GET or POST request
		$request = $this->getRequest(null);
		if (isset($request['status'])) {
			$tickets = Ticket::objects()
							->values("ticket_id", "number")
							->filter([ "status__name__like" => $request['status'] ])
							->all();
			// we actually want to return 'id' instead of 'ticket_id', so this is an easy hack given I don't
			// know now to do column aliases in their ORM
			$this->response(200, str_replace("ticket_id", "id", json_encode($tickets)));
		}
	}

	/*
	 * Return details of a ticket given the Ticket Number/ID
	 * GET /ticket/<id>
	 *		id_type="id|number"		if not specified, defaults to id
	 *
	 * Returns
	 *
	 * JSON encoded ticket
	 */
	function getTicket($id)
	{
       if(!($key=$this->requireApiKey()))
            return $this->exerr(401, __('API key not authorized'));
		
		if (! isset($id)) {
			// should never happen
			$this->exerr(204, "No ticket number received in getTicket()");
		}

		$ticket = null;
		$req = $this->getRequest(null);
		$type = $req['id_type'] ?? "id";
		switch ($type) {
			case 'number':
				$tickets = Ticket::objects()
							->filter([ 'number' => $id ])
							->all();
				if (! $tickets || count($tickets) != 1) {
					$msg = "ticket with id/number $id not found";
					$this->response(404, $msg);
				}
				$ticket = $tickets[0];
				break;
			case 'id':
			default:
				$ticket = Ticket::lookup($id);
				break;
		}

		if (! $ticket) {
			$msg = "ticket with number $id not found";
			$this->exerr(404, $msg);
		}

		$resp = $this->formatTicket($ticket);
		$this->response(200, json_encode($resp));
	}
	/*
	 * Return ticket Thread details from Ticket Number/ID
	 * GET /ticket/<id>/thread
	 *		id_type="id|number"		if not specified, defaults to id
	 *
	 * Returns
	 *
	 * JSON encoded ticket
	 */
	function getTicketThread($id)
	{
       if(!($key=$this->requireApiKey()))
            return $this->exerr(401, __('API key not authorized'));
		
		if (! isset($id)) {
			// should never happen
			$this->exerr(204, "No ticket number received in getTicket()");
		}

		$ticket = null;
		$req = $this->getRequest(null);
		$type = $req['id_type'] ?? "id";
		switch ($type) {
			case 'number':
				$tickets = Ticket::objects()
							->filter([ 'number' => $id ])
							->all();
				if (! $tickets || count($tickets) != 1) {
					$msg = "ticket with id/number $id not found";
					$this->response(404, $msg);
				}
				$ticket = $tickets[0];
				break;
			case 'id':
			default:
				$ticket = Ticket::lookup($id);
				break;
		}

		if (! $ticket) {
			$msg = "ticket with number $id not found";
			$this->exerr(404, $msg);
		}

		$resp = $this->formatTicketThread($ticket);
		$this->response(200, json_encode($resp));
	}

	/*
	 * generate an array of useful ticket info
	 * Since the Ticket class doesnt implement Arrayable we do it ourselves
	 * should probably be in the Ticket class ...
	 */
	protected function formatTicket($ticket) {
		global $cfg;

		$output = array();
		$output['id'] = $ticket->getId();
		$output['number'] = $ticket->getNumber();
		$output['state'] = $ticket->getStatus()->getLocalName();
		$output['status_state'] = $ticket->getStatus()->getState();
		$output['email'] = $ticket->getEmail()->getAddress();
		$output['name'] = $ticket->getName()->getFull();
		$output['subject'] = $ticket->getSubject();
		$output['owner'] = $ticket->getOwner()->getName()->getFull();

		// Owner may not belong to an organisation
		$org = $ticket->getOwner()->getOrganization();
		if (isset($org))
			$output['organisation'] = $org->getName();

		// Ticket may not be assigned, but we can bypass this by going
		// directly to the Staff member associated with the ticket
		if ($ticket->getStaff())
			$output['assignee'] = $ticket->getStaff()->getName()->getFull();

		$output['created'] = $ticket->getCreateDate();
		$output['updated'] = $ticket->getUpdateDate();
		$output['closed'] = $ticket->getCloseDate();
		
		// this is based on the configured base URL for the help desk and may not (in dev at least)
		// be the *actual* URL
		$output['url'] = $ticket->getVar("staff_link");

		if ($cfg->isThreadTime())
			$output['total_time_spent'] = $ticket->getTimeSpent();

//		$output['raw'] = serialize($ticket);

        return $output;
	}
	/*
	 * generate an array of useful ticket thread details
	 * Since the ThreadEntry class doesnt implement Arrayable we do it ourselves
	 */
	protected function formatTicketThread($ticket) {
		global $cfg;
		$output = array();
		foreach ($ticket->getThreadEntries() as $th) {
			$thread = array();
			$thread['title'] = $th->getTitle();
			$thread['type_name'] = $th->getTypeName();
			$thread['poster'] = $th->getPoster();
			$thread['created'] = $th->getCreateDate();
			if ($cfg->isThreadTime()) {
				$thread['time_spent'] = $th->getTimeSpent();
				$thread['time_bill'] = $th->getTimeBill();
				$thread['time_invoice'] = $th->getTimeInvoice();
				$thread['time_type'] = $th->getTimeTypeName();
			}
			$thread['body'] = $th->getBody()->getClean();
//			$thread['raw'] = json_encode($th);
			$output[$th->getId()] = $thread;
		}
		return $output;
	}
	/*
	 * Request for defined ticket statuses
	 */
	function ticketStatuses()
	{
		$tsl = TicketStatusList::getStatuses(array('states' => array("open", "closed")));
		foreach ($tsl as $ts) {
			$k = $ts->getName();
			$st[$k]['id'] = $ts->getId();
			$st[$k]['name'] = $ts->getName();
			$st[$k]['state'] = $ts->getState();
			$st[$k]['properties'] = $ts->get("properties");
		}
        $this->response(200, json_encode($st));
	}
	/*
	 * API request to set the status of a ticket
	 * Request should contain
	 *		id		ID of ticket to update
	 *		status	Ticket status to assign, shold be one of those returned by API::ticketStatuses
	 *		comment	(optional) comment to add to thread entry
	 *
	 * Returns
	 *		OK		Done it!
	 *		FAILED	Error occurred
	 */
	function setStatus($id)
	{
        if(!($key=$this->requireApiKey()))
            return $this->exerr(401, __('API key not authorized'));

		$req = $this->getRequest(null);
		$ticket = Ticket::lookup($id);
		if (! $ticket) $this->response(404, "ticket with id $id not found");

		$errors = null;
		$st = TicketStatus::lookup(array("name" => $req['status']));
		if (! $st) $this->response(406, "non-existant status ".$req['status']);

		$comment = $req['comment'] ?? "set by API";
		if ($ticket->setStatus($st, $comment, $errors, false, true)) {
			$this->response(200, "OK");
		}
		else {
			$this->response(406, "FAILED");
		}
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
