<!DOCTYPE HTML>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>OS Ticket API tester</title>
    </head>
    <body>
        <div>
            <ol id='output'></ol>
        </div>
        <script>
            var api='B2C040470AC3B3AD237272F9AEABF224';

            function testApi(test, method, url, data) {
                var msg='<h4>'+test+'</h4><p>'+method+' '+url+'</p><p>params:</p><pre><code>'+(data?JSON.stringify(data, null, 2):null)+'</code></pre>';
                if(data && ['POST','PUT'].indexOf(method)==-1) {
                    url=url+'?'+Object.keys(data).map(function(key) {return key + '=' + data[key]}).join('&');
                    data=null;
                }
                else data=JSON.stringify(data);
                var request = new XMLHttpRequest();
                request.open(method, url, false);
                request.setRequestHeader("X-API-Key", api);
                request.send(data);
                try {
                    var dataOut=JSON.parse(request.responseText);
                } catch (e) {
                    if(request.responseText) console.log('invalid response', request.responseText);
                    dataOut=null;
                }
                msg+='<p>Status: '+request.statusText+' ('+request.status+')</p><p>Response:</p><pre><code>'+JSON.stringify(dataOut, null, 2)+'</code></pre><br>';
                var li = document.createElement("li");
                li.innerHTML=msg;
                document.getElementById('output').appendChild(li);
                return dataOut;
            }

            function getOrgData(prefix) {
                return {
                    name: 'ABC Company'+(typeof prefix !== 'undefined'?'_'+prefix:''),
                    address: '123 main street',
                    phone: '4254441212X123',
                    notes: 'Mynotes',
                    website: 'website.com',
                };
            }
            function getUserData(orgId, prefix) {
                var userData= {
                    email: 'John.Doe'+(typeof prefix !== 'undefined'?'_'+prefix:'')+'@gmail.com',
                    phone: '(425) 444-1212 X123',
                    notes: 'Some Notes',
                    name: 'John Doe',
                    password: 'thepassword',
                    timezone: 'America/Los_Angeles',
                };
                if (typeof orgId !== 'undefined') {
                    userData.org_id=orgId;
                }
                return userData
            }
            function getTicketData(user, type) {
                var ticketData={
                    message: "My original message",
                    //message: "data:text/html, My original message",
                    name: user.name,
                    subject: "Testing API",
                    topicId: 2,
                };
                if (type='id') {
                    ticketData.userId=user.id;
                }
                else {
                    ticketData.email=user.email;
                }
                return ticketData
            }

            //Test endpoints
            var org1=testApi('Create organization', 'POST', '/api/scp/organizations.json', getOrgData());
            testApi('Create organization with existing name', 'POST', '/api/scp/organizations.json', getOrgData());
            testApi('Get organization', 'GET', '/api/scp/organizations.json/'+org1.id);
            var user1=testApi('Create user', 'POST', '/api/scp/users.json', getUserData(org1.id));
            testApi('Create user with existing email', 'POST', '/api/scp/users.json', getUserData(org1.id));
            var user2=testApi('Create second user', 'POST', '/api/scp/users.json', getUserData(org1.id, 2));
            testApi('Get organization users', 'GET', '/api/scp/organizations.json/users/'+org1.id);
            testApi('Delete organization and delete users', 'DELETE', '/api/scp/organizations.json/'+org1.id, {deleteUsers: 1});
            testApi('Delete organization with invalid ID', 'DELETE', '/api/scp/organizations.json/'+org1.id);
            testApi('Get user with invalid ID', 'GET', '/api/scp/users.json/'+user1.id);
            testApi('Delete user with invalid ID', 'DELETE', '/api/scp/users.json/'+user1.id);

            var user3=testApi('Create user without an organization', 'POST', '/api/scp/users.json', getUserData());
            testApi('Delete user', 'DELETE', '/api/scp/users.json/'+user3.id);

            var org2=testApi('Create organization', 'POST', '/api/scp/organizations.json', getOrgData());
            var user4=testApi('Create user', 'POST', '/api/scp/users.json', getUserData(org2.id));
            testApi('Delete organization but do not delete users', 'DELETE', '/api/scp/organizations.json/'+org2.id);
            testApi('Get organization with invalid ID', 'GET', '/api/scp/organizations.json/'+org2.id);
            testApi('Get user', 'GET', '/api/scp/users.json/'+user4.id);

            var ticket1=testApi('Create Ticket using user email', 'POST', '/api/tickets.json', getTicketData(user4, 'email'));
            var ticket2=testApi('Create Ticket using user ID', 'POST', '/api/tickets.json', getTicketData(user4, 'id'));
            testApi('Create Ticket using invalid user email', 'POST', '/api/tickets.json', getTicketData(user2, 'email'));
            testApi('Create Ticket using invalid user ID', 'POST', '/api/tickets.json', getTicketData(user2, 'id'));

            testApi('Close Ticket using user email', 'DELETE', '/api/tickets.json/'+ticket1.id, {email: user4.email});
            testApi('Reopen Ticket using user email', 'POST', '/api/tickets.json/'+ticket1.id, {email: user4.email});

            testApi('Close Ticket using user ID', 'DELETE', '/api/tickets.json/'+ticket1.id, {userId: user4.id});
            testApi('Reopen Ticket using user ID', 'POST', '/api/tickets.json/'+ticket1.id, {userId: user4.id});

            testApi('Update Ticket using user email', 'PUT', '/api/tickets.json/'+ticket1.id, {email: user4.email, "message": "My updated message using email"});
            testApi('Update Ticket using user ID', 'PUT', '/api/tickets.json/'+ticket1.id, {userId: user4.id, "message": "My updated message using userId"});
            testApi('Update Ticket using invalid user email', 'PUT', '/api/tickets.json/'+ticket1.id, {email: user2.email, "message": "My updated message using email"});
            testApi('Update Ticket using invalid user user ID', 'PUT', '/api/tickets.json/'+ticket1.id, {userId: user2.id, "message": "My updated message using userId"});

            testApi('Get Tickets using user email', 'GET', '/api/tickets.json', {email: user4.email});
            testApi('Get Tickets using user ID', 'GET', '/api/tickets.json', {userId: user4.id});
            testApi('Get Ticket', 'GET', '/api/tickets.json/'+ticket1.id);

            testApi('Delete user', 'DELETE', '/api/scp/users.json/'+user4.id);
            testApi('Get Ticket with invalid ID', 'GET', '/api/tickets.json/'+ticket1.id);

            testApi('Get Topics', 'GET', '/api/topics.json');
        </script>
    </body>
</html>
