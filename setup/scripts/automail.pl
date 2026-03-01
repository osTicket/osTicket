#!/usr/bin/perl
#######################################################################
#    automail.pl
#
#    Perl script used for remote email piping...same as as the PHP version.
#
#    Peter Rotich <peter@osticket.com>
#    Copyright (c) 2006-2020 osTicket
#    http://www.osticket.com
#
#    Released under the GNU General Public License WITHOUT ANY WARRANTY.
#    See LICENSE.TXT for details.
#
#    vim: expandtab sw=4 ts=4 sts=4:
#######################################################################

#Requirements: The following libraries/modules are required.
#  LWP    => LWP (World-Wide Web Library required for UserAgent)
#  Switch => Switch (Module required for switch statements)
#  HTTPS  => LWP::Protocol::https (Module required if using HTTPS)

#Configuration: Enter the url and key. That is it.
#  url=> URL to pipe.php e.g http://yourdomain.com/api/tickets.email
#  key=> API Key (see admin panel on how to generate a key)

%config = (url => 'http://yourdomain.com/api/tickets.email',
           key => 'API KEY HERE');

#Get piped message from stdin
while (<STDIN>) {
    $rawemail .= $_;
}

use LWP::UserAgent;
$ua = LWP::UserAgent->new;

$ua->agent('osTicket API Client v1.14');
$ua->default_header('X-API-Key' => $config{'key'});
$ua->timeout(10);

use HTTP::Request::Common qw(POST);

my $enc ='text/plain';
my $req = (POST $config{'url'}, Content_Type => $enc, Content => $rawemail);
$response = $ua->request($req);

#
# Process response
# Add exit codes - depending on what your  MTA expects.
# By default postfix exit codes are used - which are standard for MTAs.
#

use Switch;

$code = 75;
switch($response->code) {
    case 201 { $code = 0; }
    case 400 { $code = 66; }
    case [401,403] { $code = 77; }
    case [415,416,417,501] { $code = 65; }
    case 503 { $code = 69 }
    case 500 { $code = 75 }
}
#print "RESPONSE: ". $response->code. ">>>".$code;
if ($code == 66) {
    print "HTTPS protocol required. Please update the URL in automail.pl to include 'https' and ensure the 'LWP::Protocol::https' Perl module is installed.\r\n"
}
exit $code;
