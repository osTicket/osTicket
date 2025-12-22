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

        switch ($format) {
            case 'email':
                $supported = array_merge($supported, [
                    'header', 'mid', 'emailId', 'to-email-id', 'ticketId', 'reply-to',
                    'reply-to-name', 'in-reply-to', 'references', 'thread-type', 'system_emails',
                    'mailflags' => ['bounce', 'auto-reply', 'spam', 'viral'],
                    'recipients' => ['*' => ['name', 'email', 'source']]
                ]);
                $supported['attachments']['*'][] = 'cid';
                break;
            case 'json':
            case 'xml':
                $supported = array_merge($supported, [
                    'duedate', 'slaId', 'staffId'
                ]);
                break;
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
        if (isset($data['attachments']) && is_array($data['attachments'])) {
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


    function create($format) {

        if (!($key=$this->requireApiKey()) || !$key->canCreateTickets())
            return $this->exerr(401, __('API key not authorized'));

        $ticket = null;
        if (!strcasecmp($format, 'email')) {
            // Process remotely piped emails - could be a reply...etc.
            $ticket = $this->processEmailRequest();
        } else {
            // Get and Parse request body data for the format
            $ticket = $this->createTicket($this->getRequest($format));
        }



        if ($ticket)
            $this->response(201, $ticket->getNumber());
        else
            $this->exerr(500, _S("unknown error"));

    }

    function list($format) {
        if (!($key = $this->requireApiKey()))
            return $this->exerr(401, __('API key not authorized'));

        global $ost;

        $order = isset($_GET['order']) && $_GET['order'] == 'asc' ? 'ASC' : 'DESC';
        
        // Build query with optional status filtering
        $query = Ticket::objects();
        
        // Status filtering options:
        // ?status=open / ?status=closed / ?status=1,2,3 / ?state=open
        if (isset($_GET['status'])) {
            $status = $_GET['status'];
            if (is_numeric($status)) {
                // Filter by status ID
                $query = $query->filter(array('status_id' => (int) $status));
            } elseif (strtolower($status) === 'open') {
                // Filter by open state
                $query = $query->filter(array('status__state' => 'open'));
            } elseif (strtolower($status) === 'closed') {
                // Filter by closed state  
                $query = $query->filter(array('status__state' => 'closed'));
            } else {
                // Filter by status name (case insensitive)
                $query = $query->filter(array('status__name__icontains' => $status));
            }
        }
        
        // Alternative state filtering: ?state=open
        if (isset($_GET['state'])) {
            $state = strtolower($_GET['state']);
            if (in_array($state, ['open', 'closed'])) {
                $query = $query->filter(array('status__state' => $state));
            }
        }
        
        // Pagination parameters
        $page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
        $per_page = isset($_GET['per_page']) ? min(200, max(1, (int) $_GET['per_page'])) : 100;
        $offset = ($page - 1) * $per_page;
        
        // Get total count BEFORE applying limit/offset
        $total_count = $query->count();
        
        // Apply pagination
        $tickets = $query
            ->order_by($order == 'ASC' ? 'created' : '-created')
            ->limit($per_page)
            ->offset($offset);

        $results = array();
        foreach ($tickets as $ticket) {
            try {
                $results[] = array(
                    'id' => $ticket->getId(),
                    'number' => $ticket->getNumber(),
                    'subject' => $ticket->getSubject(),
                    'created' => $ticket->getCreateDate(),
                    'status' => $ticket->getStatus(),
                );
            } catch (Exception $e) {
                // Continue if individual ticket fails
                continue;
            }
        }

        // Calculate pagination metadata
        $total_pages = ceil($total_count / $per_page);
        $has_next = $page < $total_pages;
        $has_previous = $page > 1;
        
        // Build paginated response
        $response = array(
            'tickets' => $results,
            'pagination' => array(
                'current_page' => $page,
                'per_page' => $per_page,
                'total' => $total_count,
                'total_pages' => $total_pages,
                'has_next' => $has_next,
                'has_previous' => $has_previous,
                'showing_from' => $offset + 1,
                'showing_to' => min($offset + $per_page, $total_count)
            )
        );

        Http::response(200, Format::json_encode($response), 'application/json');
        exit;
    }

    function details($id, $format) {
        if (!($key = $this->requireApiKey()))
            return $this->exerr(401, __('API key not authorized'));

        $ticket = Ticket::lookup($id);
        if (!$ticket) {
            Http::response(404, Format::json_encode(array('error' => 'Ticket not found')), 'application/json');
            exit;
        }

        $result = array(
            'id' => $ticket->getId(),
            'number' => $ticket->getNumber(),
            'subject' => $ticket->getSubject(),
            'created' => $ticket->getCreateDate(),
            'status' => $ticket->getStatus(),
            'thread' => array(),
        );

        // Thread entries with author information
        foreach ($ticket->getThread()->getEntries() as $entry) {
            // Safe author information
            $author = array('type' => 'unknown', 'name' => 'Unknown');

            try {
                if ($entry->getStaff()) {
                    $staff = $entry->getStaff();
                    $author = array(
                        'type' => 'staff',
                        'name' => $staff->getName() ?: 'Staff'
                    );
                } elseif ($entry->getUser()) {
                    $user = $entry->getUser();
                    $author = array(
                        'type' => 'user',
                        'name' => $user->getName() ?: 'User'
                    );
                } elseif ($entry->getPoster()) {
                    $author = array(
                        'type' => 'guest',
                        'name' => $entry->getPoster()
                    );
                }
            } catch (Exception $e) {
                // If something goes wrong, use default author
            }

            // Determine entry properties safely
            $isInternal = ($entry->getType() == 'N'); // Note = internal note
            $isResponse = ($entry->getType() == 'R'); // Response = staff response
            $isMessage = ($entry->getType() == 'M');  // Message = customer message
            $isSystem = !in_array($entry->getType(), ['M', 'R', 'N']); // System entry
            
            // Get attachments safely
            $attachments = array();
            try {
                foreach ($entry->getAttachments() as $attachment) {
                    if ($attachment && $attachment->getFile()) {
                        $attachments[] = array(
                            'id' => $attachment->getId(),
                            'name' => $attachment->getName() ?: 'Attachment',
                            'size' => $attachment->getFile()->getSize() ?: 0,
                            'type' => $attachment->getFile()->getType() ?: 'unknown',
                            'is_inline' => (bool) $attachment->inline
                        );
                    }
                }
            } catch (Exception $e) {
                // If attachments fail, continue with empty array
            }
            
            // Determine display type for frontend
            $displayType = 'unknown';
            if ($isSystem) {
                $displayType = 'system'; // System messages
            } elseif ($isInternal) {
                $displayType = 'internal_note'; // Internal notes
            } elseif ($isResponse) {
                $displayType = 'staff_response'; // Staff response to customer
            } elseif ($isMessage) {
                $displayType = 'customer_message'; // Customer message
            }
            
            // Get body content safely
            $bodyText = '';
            $bodyHtml = '';
            try {
                $body = $entry->getBody();
                if ($body) {
                    $bodyText = method_exists($body, 'getClean') ? $body->getClean() : (string) $body;
                    $bodyHtml = method_exists($body, 'toHtml') ? $body->toHtml() : (string) $body;
                }
            } catch (Exception $e) {
                $bodyText = (string) $entry->getBody();
                $bodyHtml = (string) $entry->getBody();
            }
            
            $result['thread'][] = array(
                'id' => $entry->getId(),
                'type' => $entry->getType(), // M/R/N
                'type_name' => method_exists($entry, 'getTypeName') ? $entry->getTypeName() : $entry->getType(),
                'display_type' => $displayType, // For frontend styling
                'title' => method_exists($entry, 'getTitle') ? $entry->getTitle() : '',
                'body' => $bodyText, // Clean text without HTML
                'body_html' => $bodyHtml, // HTML version with formatting
                'created' => $entry->getCreateDate(),
                'updated' => method_exists($entry, 'getUpdateDate') ? $entry->getUpdateDate() : null,
                'source' => method_exists($entry, 'getSource') ? $entry->getSource() : null,
                'poster' => $entry->getPoster() ?: null,
                'is_internal' => $isInternal,
                'is_system' => $isSystem,
                'is_edited' => method_exists($entry, 'isEdited') ? $entry->isEdited() : false,
                'is_response' => $isResponse,
                'is_message' => $isMessage,
                'attachments' => $attachments,
                'author' => $author
            );
        }

        Http::response(200, Format::json_encode($result), 'application/json');
        exit;
    }

    function attachmentUrl($id, $format) {
        if (!($key = $this->requireApiKey()))
            return $this->exerr(401, __('API key not authorized'));

        // Find attachment safely
        $attachment = Attachment::lookup($id);
        if (!$attachment) {
            Http::response(404, Format::json_encode(array('error' => 'Attachment not found')), 'application/json');
            exit;
        }

        // Generate authorized URL with temporary key
        $expires = time() + (24 * 3600); // Valid for 24 hours
        $signature = hash_hmac('sha256',
            $attachment->getId() . '|' . $expires,
            $key->getKey()
        );

        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        
        $auth_url = sprintf(
            '%s://%s/api/file.php?id=%d&expires=%d&signature=%s&disposition=inline',
            $protocol,
            $host,
            $attachment->getId(),
            $expires,
            $signature
        );

        $result = array(
            'attachment_id' => $attachment->getId(),
            'filename' => $attachment->getName() ?: 'Attachment',
            'size' => 0,
            'type' => 'unknown',
            'url' => $auth_url,
            'expires' => date('Y-m-d H:i:s', $expires),
            'expires_timestamp' => $expires
        );

        // Get file info safely
        try {
            if ($attachment->getFile()) {
                $result['size'] = $attachment->getFile()->getSize() ?: 0;
                $result['type'] = $attachment->getFile()->getType() ?: 'unknown';
            }
        } catch (Exception $e) {
            // Continue with defaults if file info fails
        }

        Http::response(200, Format::json_encode($result), 'application/json');
        exit;
    }

    /* private helper functions */

    function createTicket($data, $source = 'API') {

        # Pull off some meta-data
        $alert       = (bool) (isset($data['alert'])       ? $data['alert']       : true);
        $autorespond = (bool) (isset($data['autorespond']) ? $data['autorespond'] : true);

        // Assign default value to source if not defined, or defined as NULL
        $data['source'] ??= $source;

        // Create the ticket with the data (attempt to anyway)
        $errors = array();
        if (($ticket = Ticket::create($data, $errors, $data['source'],
                $autorespond, $alert)) &&  !$errors)
            return $ticket;

        // Ticket create failed Bigly - got errors?
        $title = null;
        // Got errors?
        if (count($errors)) {
            // Ticket denied? Say so loudly so it can standout from generic
            // validation errors
            if (isset($errors['errno']) && $errors['errno'] == 403) {
                $title = _S('Ticket denied');
                $error = sprintf("%s: %s\n\n%s",
                    $title, $data['email'], $errors['err']);
            } else {
                // unpack the errors
                $error = Format::array_implode("\n", "\n", $errors);
            }
        } else {
            // unknown reason - default
            $error = _S('unknown error');
        }

        $error = sprintf('%s :%s',
            _S('Unable to create new ticket'), $error);
        return $this->exerr($errors['errno'] ?: 500, $error, $title);
    }

    function processEmailRequest() {
        return $this->processEmail();
    }

    function processEmail($data=false, array $defaults = []) {

        try {
            if (!$data)
                $data = $this->getEmailRequest();
            elseif (!is_array($data))
                $data = $this->parseEmail($data);
        } catch (Exception $ex)  {
            throw new EmailParseError($ex->getMessage());
        }

        $data = array_merge($defaults, $data);
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
        try {
            return $this->createTicket($data, 'Email');
        } catch (TicketApiError $err) {
            // Check if the ticket was denied by a filter or banlist
            if ($err->isDenied() && $data['mid']) {
                // We need to log the Message-Id (mid) so we don't
                // process the same email again in subsequent fetches
                $entry = new ThreadEntry();
                $entry->logEmailHeaders(0, $data['mid']);
                // throw TicketDenied exception so the caller can handle it
                // accordingly
                throw new TicketDenied($err->getMessage());
            } else {
                // otherwise rethrow this bad baby as it is!
                throw $err;
            }
        }
    }
}

//Local email piping controller - no API key required!
class PipeApiController extends TicketApiController {

    // Overwrite grandparent's (ApiController) response method.
    function response($code, $resp) {

        // It's important to use postfix exit codes for local piping instead
        // of HTTP's so the piping script can process them accordingly
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
        //We're simply exiting - MTA will take care of the rest based on exit code!
        exit($exitcode);
    }

    static function process($sapi=null) {
        $pipe = new PipeApiController($sapi);
        if (($ticket=$pipe->processEmail()))
            return $pipe->response(201,
                is_object($ticket) ? $ticket->getNumber() : $ticket);

        return $pipe->exerr(416, __('Request failed - retry again!'));
    }

    static function local() {
        return self::process('cli');
    }
}

class TicketApiError extends Exception {

    // Check if exception is because of denial
    public function isDenied() {
        return ($this->getCode() === 403);
    }
}

class TicketDenied extends Exception {}
class EmailParseError extends Exception {}
