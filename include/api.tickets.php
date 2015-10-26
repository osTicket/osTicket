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
    
    function delete($format) {
        
        if(!($key=$this->requireApiKey()) || !$key->canCreateTickets())
            return $this->exerr(401, __('API key not authorized'));
        
        
        $request = $this->getRequest($format);
        #See if the necessary information are in the request
        if (array_key_exists('number', $request) && is_numeric($request['number'])){
            #Looks if there is a matching ID to the specified number
            $id = Ticket::getIdByNumber($request['number']); 
            if ($id > 0){
                
                $ticket = new Ticket();
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

    function status($format) {
        
        if(!($key=$this->requireApiKey()))
            return $this->exerr(401, __('API key not authorized'));
        
        
        $request = $this->getRequest($format);
        #See if the necessary information are in the request
        if (array_key_exists('number', $request) && is_numeric($request['number'])){
            #Looks if there is a matching ID to the specified number
            $id = Ticket::getIdByNumber($request['number']); 
            if ($id > 0){
                #Load the ticket
                $ticket = new Ticket();
                $ticket->load($id);

                #Prepare the response
                $response = [];
                foreach($request as $key=>$value){
                    $response[$key] = $this->recursiveParameterProcessing($ticket, $key, $value);
                }
                
                #Send the response
                $this->response(200, json_encode($response));
            }
            else #No ticket matching to sent number
                $this->exerr(404, __("Ticket not found"));
        }
        else #No number was sent with the Request
            $this->exerr(415, __("Number not sent"));
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

        $this->response(201, $ticket->getNumber());
    }

    /* private helper functions */

    function createTicket($data) {

        # Pull off some meta-data
        $alert       = (bool) (isset($data['alert'])       ? $data['alert']       : true);
        $autorespond = (bool) (isset($data['autorespond']) ? $data['autorespond'] : true);

        # Assign default value to source if not defined, or defined as NULL
        $data['source'] = isset($data['source']) ? $data['source'] : 'API';
        
        $softerrors = "";
        # Check for Assignment Information
        if (!isset($data['assignId'])){
            if (isset($data['assignEmail'])){
                if ($staff = Staff::getIdByEmail($data['assignEmail']))
                    $data['assignId'] = $staff;
                else
                    return $this->exerr(404, __("No staffmember has specified email address."));
                unset($data['assignEmail']);
            }
        }
        #Check for Department Information
        if (!isset($data['deptId'])){
            if (isset($data['deptName'])){
                if ($dept = Dept::getIdByName($data['deptName']))
                    $data['deptId'] = $dept;
                else
                    return $this->exerr(404, __("No Department corresponding to sent department Name."));
                unset($data['deptName']);
            }
        }
        
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
    
    
    
    #Extracts data from the ticket for the response.
    #Example: ['Staff'=>['Email'=>1, 'Id'=>0]] as parameters means that the following happens:
    #$temp = $ticket->getStaff();
    #return ['Email'=>$temp->getEmail(), 'Id'=>$temp->Id];
    #And in the $response array, it is then saved as ['Staff'=>['Email'=>$temp->getEmail(), 'Id'=>$temp->Id]]
    function recursiveParameterProcessing($object, $key, $value) {
        if ($value){
            #A <$value> that is not 0 means that a get<$key>() Function of the object is accessed
            $command = 'get'.$key;
            if (method_exists($object, $command)){
                $nextobject = $object->$command();
                if ($nextobject === null)
                    return null;#Returns null if the get-function returns null
            }
            else
                return false;#Returns false if there is no get-function for the key
            
            #An array <$value> calls a get<$key>() of the object and then does the same process again
            #for the result of said get-Function using the $keys and $values of the array used
            if (is_array($value)){
                $array = [];
                foreach($value as $k=>$val){
                    $array[$k] = $this->recursiveParameterProcessing($nextobject, $k, $val);
                }
                return $array;
            }
            else{#A numeric <$value> that is not 0 means that the result of the get<$key>-function is returned as string
                return (string)$nextobject;
            }
        }
        else{#A <$value> of 0 means that the object property is accessed directly via the <$key>
            return (string)$object->$key;
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
