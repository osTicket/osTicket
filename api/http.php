<?php
/*********************************************************************
    http.php

    HTTP controller for the osTicket API

    Jared Hancock
    Copyright (c)  2006-2013 osTicket
    http://www.osticket.com

    Released under the GNU General Public License WITHOUT ANY WARRANTY.
    See LICENSE.TXT for details.

    vim: expandtab sw=4 ts=4 sts=4:
**********************************************************************/
require 'api.inc.php';
# Include the main api urls
require_once INCLUDE_DIR."class.dispatcher.php";
$dispatcher = patterns('',

		// Generic method for ticket creation
        url_post("^/tickets\.(?P<format>xml|json|email)$", array('api.tickets.php:TicketApiController','create')),

		// fetches (GET)
		// NOTE ordering may be important here because URL will match in the order these are defined
		url_get("^/tickets$", array('api.tickets.php:TicketApiController','listTickets')),
		url_get("^/ticket/(?P<id>\d+)$", array('api.tickets.php:TicketApiController','getTicket')),
		url_get("^/ticket/(?P<id>\d+)/thread$", array('api.tickets.php:TicketApiController','getTicketThread')),
		url_get("^/ticket/statuses$", array('api.tickets.php:TicketApiController','ticketStatuses')),

		// Updates (PUT)
		// set ticket status (in parameters)
		url("^/ticket/(?P<id>\d+)/setStatus$", array('api.tickets.php:TicketApiController','setStatus'), null, "PUT"),
		// mark thread entry as billed
		url("^/thread/(?P<id>\d+)/billed$", array('api.ticketTime.php:TicketTimeApiController', 'threadBilled'), null, "PUT"),
		// mark ticket thread entries as billed by time type (in parameters)
		url("^/ticket/(?P<id>\d+)/billed$", array('api.ticketTime.php:TicketTimeApiController', 'ticketBilled'), null, "PUT"),

        url('^/tasks/', patterns('',
                url_post("^cron$", array('api.cron.php:CronApiController', 'execute'))
         ))
        );

// Send api signal so backend can register endpoints
Signal::send('api', $dispatcher);
# Call the respective function
print $dispatcher->resolve(Osticket::get_path_info());
?>
