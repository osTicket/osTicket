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

API keys can be created and managed via the admin panel. Navigate to Manage
-> API keys. Use *Add New API Key* to create a new API key. Currently, no
special configuration is required to allow the API key to be used for the
HTTP API. All API keys are valid for the HTTP API.

HTTP Access
-----------
Access to the HTTP API is restricted to valid API keys. An `x-api-key` HTTP
header must be sent to indicate which API key is to be used with the
request. The API key must match the remote IP of the connected HTTP client.
The remote IP is checked as usual. If the osTicket server is sitting behind
a reverse proxy, the original IP of the client will be retrieved from the
`X-Forwarded-For` header, if provided by your proxy.

* Note that the header name must be 'x-api-key', with all letters in lower case.

Example:

    x-api-key: BA00B76BAA30F62E1940B46CC1C3C73C

Commandline Example with Curl:

    curl -d "{}" -H "x-api-key: BA00B76BAA30F62E1940B46CC1C3C73C"
        https://support.you.tld/api/tickets.json

Wrappers
--------

Wrappers are located in the api/wrappers folder. They are
separated by language to keep it organized.

If you've written one wrapper and would like it on the list,
submit a pull request to add your wrapper.

The current list of wrappers is:

- [C#] osTicketWrapper by pedro-lb: https://github.com/pedro-lb/osTicket


Resources
---------

- [Tickets](api/tickets.md)
- [Tasks](api/tasks.md)
