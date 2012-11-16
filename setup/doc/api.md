osTicket API
============

The osTicket API is implemented as (somewhat) simple XML or JSON over HTTP.
For now, only ticket creation is supported, but eventually, all resources
inside osTicket will be accessible and modifiable via the API.

Authentication
--------------

Authentication via the API is done via API keys configured inside the
osTicket admin panel. API keys are created and tied to a source IP address,
which will be checked against the source IP of requests to the HTTP API.

Wrappers
--------

Currently, there are no wrappers for the API. If you've written one and
would like it on the list, submit a pull request to add your wrapper.

Resources
---------

- [Tickets](api/tickets.md)
