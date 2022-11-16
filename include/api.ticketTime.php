<?php

include_once INCLUDE_DIR.'class.api.php';
include_once INCLUDE_DIR.'class.ticket.php';

class TicketTimeApiController extends ApiController {
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
	/*
	 * update thread to mark time entry as billed
	 */
	function threadBilled($id)
	{
        if(!($key=$this->requireApiKey()))
            return $this->exerr(401, __('API key not authorized'));

		$te = ThreadEntry::lookup($id);
		$te->getThread();
		$te->setTimeInvoice(true);
		$te->save();
		$this->response(200, json_encode($te));
	}

	// mark all thread entries having time type as specified as invoiced
	function ticketBilled($id) {
        if(!($key=$this->requireApiKey()))
            return $this->exerr(401, __('API key not authorized'));

		// find the ticket
		$ticket = Ticket::lookup($id);
		if (! $ticket) $this->response(404, "ticket with id $id not found");

		// get the passed time type
		$req = $this->getRequest(null);
		$timeType = $req['time_type'];
		
		// pull the threads and update them
		$threads = $ticket->getThreadEntries();
		foreach ($threads as $te) {
			if ($te->getTimeTypeName() === $timeType) {
				$te->setTimeInvoice(true);
				$te->save();
			}
		}
		$this->response(200, "OK on ticket id $id set time type '".$timeType."' as billed.");

	}
}

?>