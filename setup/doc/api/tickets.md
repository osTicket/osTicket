Tickets
=======
The API supports ticket creation via the HTTP API (as well as via email,
etc.). Currently, the API support creation of tickets only -- so no
modifications and deletions of existing tickets is possible via the API for
now.

Create a Ticket
---------------

### Fields ######

*   __email__:   *required* Email address of the submitter
*   __name__:    *required* Name of the submitter
*   __subject__: *required* Subject of the ticket
*   __message__: *required* Initial message for the ticket thread

*   __alert__:       If unset, disable alerts to staff. Default is `true`
*   __autorespond__: If unset, disable autoresponses. Default is
                     `true`
*   __ip__:          IP address of the submitter
*   __phone__:       Phone number of the submitter. See examples below for
                     embedding the extension with the phone number
*   __phone_ext__:   Phone number extension -- can also be embedded in
                     *phone*
*   __priorityId__:  Priority *id* for the new ticket to assume
*   __source__:      Source of the ticket, default is `API`
*   __topicId__:     Help topic *id* associated with the ticket

*   __attachments__: An array of files to attach to the initial message.
                     Each attachment must have some content and also the
                     following fields:
    *   __name__:     *required* name of the file to be attached. Multiple files
                      with the same name are allowable
    *   __type__:     Mime type of the file. Default is `text/plain`
    *   __encoding__: Set to `base64` if content is base64 encoded 

### XMl Payload Example ######

The XML data format is extremely lax. Content can be either sent as an
attribute or a named element if it has no sub-content.

In the example below, the simple element could also be replaced as
attributes on the root `<ticket>` element; however, if a `CDATA` element is
necessary to hold special content, or difficult content such as double
quotes is to be embedded, simple sub-elements are also supported.

Notice that the phone extension can be sent as the `@ext` attribute of the
`phone` sub-element.

    <?xml version="1.0" encoding="UTF-8"?>
    <ticket alert="true" autorespond="true" source="API">
        <name>Peter Rotich</name>
        <email>peter@osticket.com</email>
        <subject>Testing API</subject>
        <phone ext="123">504-305-8634</phone>
        <message><![CDATA[Message content here]]></message>
        <attachments>
            <file name="file.txt" type="plain/text" 
                encoding="base64"><![CDATA[
                    File content is here and is automatically trimmed
            ]]></file>
        </attachments>
        <ip>123.211.233.122</ip>
    </ticket>

### JSON Payload Example ###

Attachment data for the JSON payload uses the [RFC 2397][] data URL format.
As described above, the content-type and base64 encoding hints are optional.
Furthermore, a character set can be optionally declared for each attachment
and will be automatically converted to UTF-8 for database storage.

Notice that the phone number extension can be embedded in the `phone` value
denoted with a capital `X`

Do also note that the JSON format forbids a comma after the last element in
an object or array definition, and newlines are not allowed inside strings.

    {
        "alert": true,
        "autorespond": true,
        "source": "API",
        "name": "Peter Rotich",
        "email": "peter@osticket.com",
        "phone": "5043058634X123",
        "subject": "Testing API",
        "ip": "123.211.233.122",
        "message": "MESSAGE HERE",
        "attachments": [
            {"file.txt": "data:text/plain;charset=utf-8,content"},
            {"image.png": "data:image/png;base64,R0lGODdhMAA..."},
        ]
    }

[rfc 2397]:     http://www.ietf.org/rfc/rfc2397.txt     "Data URLs"

### Response ######

If successful, the server will send `HTTP/201 Created`. Otherwise, it will
send an appropriate HTTP status with the content being the error
description. Most likely offenders are

* Required field not included
* Data type mismatch (text send for numeric field)
* Incorrectly encoded base64 data
* Unsupported field sent
* Incorrectly formatted payload (bad JSON or XML)

Upon success, the content of the response will be the external ticket id of
the newly-created ticket.

    Status: 201 Created
    123456
