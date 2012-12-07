<?php

include_once "include/class.api.php";
include_once "include/class.ticket.php";

class TicketController extends ApiController {

    # Supported arguments -- anything else is an error. These items will be
    # inspected _after_ the fixup() method of the ApiXxxDataParser classes
    # so that all supported input formats should be supported
    function getRequestStructure($format) {
        $supported = array(
            "alert", "autorespond", "source", "topicId",
            "name", "email", "subject", "phone", "phone_ext",
            "attachments" => array("*" => 
                array("name", "type", "data", "encoding")
            ), 
            "message", "ip", "priorityId"
        );
        if ($format == "xml") return array("ticket" => $supported);
        else return $supported;
    }

    function create($format) {

        if(!($key=$this->getApiKey()) || !$key->canCreateTickets())
            Http::response(401, 'API key not authorized');

        # Parse request body
        $data = $this->getRequest($format);
        if ($format == "xml") $data = $data["ticket"];

        # Pull off some meta-data
        $alert = $data['alert'] ? $data['alert'] : true; 
        $autorespond = $data['autorespond'] ? $data['autorespond'] : true;
        $source = $data['source'] ? $data['source'] : 'API';

        $attachments = $data['attachments'] ? $data['attachments'] : array();

		# TODO: Handle attachment encoding (base64)
        foreach ($attachments as $filename=>&$info) {
            if ($info["encoding"] == "base64") {
                # XXX: May fail on large inputs. See
                #      http://us.php.net/manual/en/function.base64-decode.php#105512
                if (!($info["data"] = base64_decode($info["data"], true)))
                    Http::response(400, sprintf(
                        "%s: Poorly encoded base64 data",
                        $info['name']));
            }
            $info['size'] = strlen($info['data']);
        }

        # Create the ticket with the data (attempt to anyway)
        $errors = array();
        $ticket = Ticket::create($data, $errors, $source, $autorespond, 
            $alert);

        # Return errors (?)
        if (count($errors)) {
            Http::response(400, "Unable to create new ticket: validation errors:\n"
                . Format::array_implode(": ", "\n", $errors));
        } elseif (!$ticket) {
            Http::response(500, "Unable to create new ticket: unknown error");
        }

        # Save attachment(s)
        foreach ($attachments as &$info)
            $ticket->saveAttachment($info, $ticket->getLastMsgId(), "M");

        # All done. Return HTTP/201 --> Created
        Http::response(201, $ticket->getExtId());
    }
}

?>
