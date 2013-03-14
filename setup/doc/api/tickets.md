Tickets
=======
The API supports ticket creation via the HTTP API (as well as via email,
etc.). Currently, the API support creation of tickets only -- so no
modifications and deletions of existing tickets is possible via the API for
now.

Create a Ticket
---------------

Tickets can be created in the osTicket system by sending an HTTP POST to
`api/tickets.xml`, `api/tickets.email` or `api/tickets.json` depending on the format of the
request content.

### Fields ######

*   __email__:   *required* Email address of the submitter
*   __name__:    *required* Name of the submitter
*   __subject__: *required* Subject of the ticket
*   __message__: *required* Initial message for the ticket thread
*   __alert__:       If unset, disable alerts to staff. Default is `true`
*   __autorespond__: If unset, disable autoresponses. Default is `true`
*   __ip__:          IP address of the submitter
*   __phone__:       Phone number of the submitter
*   __phone_ext__:   Phone number extension -- can also be embedded in *phone*
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

### XML Payload Example ######

* `POST /api/tickets.xml`

The XML data format is extremely lax. Content can be either sent as an
attribute or a named element if it has no sub-content.

In the example below, the simple element could also be replaced as
attributes on the root `<ticket>` element; however, if a `CDATA` element is
necessary to hold special content, or difficult content such as double
quotes is to be embedded, simple sub-elements are also supported.

Notice that the phone extension can be sent as the `@ext` attribute of the
`phone` sub-element.

``` xml
<?xml version="1.0" encoding="UTF-8"?>
<ticket alert="true" autorespond="true" source="API">
    <name>Angry User</name>
    <email>api@osticket.com</email>
    <subject>Testing API</subject>
    <phone ext="123">318-555-8634</phone>
    <message><![CDATA[Message content here]]></message>
    <attachments>
        <file name="file.txt" type="text/plain"><![CDATA[
            File content is here and is automatically trimmed
        ]]></file>
        <file name="image.gif" type="image/gif" encoding="base64">
            R0lGODdhMAAwAPAAAAAAAP///ywAAAAAMAAwAAAC8IyPqcvt3wCcDkiLc7C0qwy
            GHhSWpjQu5yqmCYsapyuvUUlvONmOZtfzgFzByTB10QgxOR0TqBQejhRNzOfkVJ
            +5YiUqrXF5Y5lKh/DeuNcP5yLWGsEbtLiOSpa/TPg7JpJHxyendzWTBfX0cxOnK
            PjgBzi4diinWGdkF8kjdfnycQZXZeYGejmJlZeGl9i2icVqaNVailT6F5iJ90m6
            mvuTS4OK05M0vDk0Q4XUtwvKOzrcd3iq9uisF81M1OIcR7lEewwcLp7tuNNkM3u
            Nna3F2JQFo97Vriy/Xl4/f1cf5VWzXyym7PHhhx4dbgYKAAA7
        </file>
    </attachments>
    <ip>123.211.233.122</ip>
</ticket>
```

### JSON Payload Example ###

* `POST /api/tickets.json`

Attachment data for the JSON content uses the [RFC 2397][] data URL format.
As described above, the content-type and base64 encoding hints are optional.
Furthermore, a character set can be optionally declared for each attachment
and will be automatically converted to UTF-8 for database storage.

Notice that the phone number extension can be embedded in the `phone` value
denoted with a capital `X`

Do also note that the JSON format forbids a comma after the last element in
an object or array definition, and newlines are not allowed inside strings.

``` json
{
    "alert": true,
    "autorespond": true,
    "source": "API",
    "name": "Angry User",
    "email": "api@osticket.com",
    "phone": "3185558634X123",
    "subject": "Testing API",
    "ip": "123.211.233.122",
    "message": "MESSAGE HERE",
    "attachments": [
        {"file.txt": "data:text/plain;charset=utf-8,content"},
        {"image.png": "data:image/png;base64,R0lGODdhMAA..."},
    ]
}
```

[rfc 2397]:     http://www.ietf.org/rfc/rfc2397.txt     "Data URLs"

### Email Payload Example ######

* `POST /api/tickets.email`

osTicket supports both remote (over http) and local piping. Please refer to the wiki on step-by-step instruction of setting up email piping.

```email

MIME-Version: 1.0
Received: by 10.194.9.167 with HTTP; Thu, 7 Feb 2013 09:01:04 -0800 (PST)
Date: Thu, 7 Feb 2013 11:01:04 -0600
Delivered-To: support@osticket.com
Message-ID: <CAL4KyrgKmpYxdX+6u3HyHZ3qN5K0mU2_sdfoVu6rT8cUNn+52w@osticket.com>
Subject: Testing
From: Peter Rotich <peter@osticket.com>
To: support@osticket.com
Content-Type: multipart/mixed; boundary=047d7bfcfaf263782204d52563a5

--047d7bfcfaf263782204d52563a5
Content-Type: multipart/alternative; boundary=047d7bfcfaf263781204d52563a3

--047d7bfcfaf263781204d52563a3
Content-Type: text/plain; charset=ISO-8859-1

Testing testing.

--
Peter Rotich
http://www.osticket.com

--047d7bfcfaf263781204d52563a3
Content-Type: text/html; charset=ISO-8859-1
Content-Transfer-Encoding: quoted-printable

<div dir=3D"ltr">Testing testing.<br clear=3D"all"><div><br></div>-- <br>Pe=
ter Rotich<br>
<a href=3D"http://www.osticket.com" target=3D"_blank">http://www.osticket.=
com</a>
</div>

--047d7bfcfaf263781204d52563a3--
--047d7bfcfaf263782204d52563a5
Content-Type: text/plain; charset=US-ASCII; name="file.txt"
Content-Disposition: attachment; filename="file.txt"
Content-Transfer-Encoding: base64
X-Attachment-Id: f_hcw5kqf60

Sm9lIGRhZGR5cyBjb250ZW50Cg==
--047d7bfcfaf263782204d52563a5--
```

Local piping can utilize `api/pipe.php` without the neeed to setup an API key.

### Response ######

If successful, the server will send `HTTP/201 Created`. Otherwise, it will
send an appropriate HTTP status with the content being the error
description. Most likely offenders are

* Required field not included
* Data type mismatch (text send for numeric field)
* Incorrectly encoded base64 data
* Unsupported field sent
* Incorrectly formatted content (bad JSON or XML)

Upon success, the content of the response will be the external ticket id of
the newly-created ticket.

    Status: 201 Created
    123456
