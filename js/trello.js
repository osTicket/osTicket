/* 
NOTE: The Trello client library has been included as a Managed Resource.  To include the client library in your own code, you would include jQuery and then

<script src="https://api.trello.com/1/client.js?key=your_application_key">...

See https://trello.com/docs for a list of available API URLs

The API development board is at https://trello.com/api

https://trello.com/app-key
http://www.trello.org/help.html
https://developer.atlassian.com/cloud/trello/rest/api-group-actions/#api-actions-id-put
https://developer.atlassian.com/cloud/trello/rest/api-group-boards/#api-boards-id-cards-get

Based on code here => http://jsfiddle.net/A3Xgk/2/

*/

$(function(){

	var onAuthorize = function() {
		updateLoggedIn();
		Trello.get('members/you', function(user){ $('span#trelloFullName').text(user['username']) } )
	};

	var updateLoggedIn = function() {
		var isLoggedIn = Trello.authorized();
		$("#trelloLoggedOut").toggle(!isLoggedIn);
		$("#trelloLoggedIn").toggle(isLoggedIn);
	};

	var logout = function() {
		Trello.deauthorize();
		updateLoggedIn();
	};

	Trello.authorize({
		interactive: false,
		success: onAuthorize
	});

	$("#trelloConnect").click(function() {
		Trello.authorize({
			type: "popup",
			name: "osTicket2TrelloBridge",
			persist: true,
			scope: { read: true, write: true },
			expiration: 'never',
			success: onAuthorize
		});
	});

	$("#trelloDisconnect").click(logout);

	$('select[name="topicId"]').change(function() {
		
		var helpTopic = $('select[name="topicId"]').val();
		var b = false;
		var msg = "trelloCreateCard = ";

		if (helpTopic == 10) {
			b = false;
			msg += "Enabled";
		} else {
			b = true;
			msg += "Disabled";
		}

		console.log( msg );
		//$("input[name='trelloCreateCard']").disabled = b;
		$("input#trelloCreateCard").disabled = b;

	});
	
	//$("input[name='trelloCreateCard']").disabled = true;
	$("input#trelloCreateCard").disabled = true;

	//$("input[name='trelloCreateCard']").click(function() {
	$("input#trelloCreateCard").click(function() {

		var pgId = parseInt($("input#trelloCreateCard")[0].name);
		var helpTopic = helpTopicSet(pgId);
		var title = getIssueSummaryVal(pgId);
		var desc = getIssueDescVal(pgId);

		if ( helpTopic && title != null && desc != null ) {
			addCard(pgId, title, desc);
		}
	});

});

function helpTopicSet(pgId) {

	var isSet = false;
	switch(pgId) {
		case 0:
			isSet = ( $('select[name="topicId"]').val() == 10 ) ? true : false;
			break;
		case 1:
			isSet = true;
			break;
		// Case 2 references the ticket edit form and is currently unused
		case 2:
			isSet = true;
			break;
	}

	return isSet;

}

// Get the value of the Issue Summary field
function getIssueSummaryVal(pgId) {
	
	var val = "";

	switch(pgId) {
		case 0:
			var id = $('input:text')[2].id;
			val = $('input#' + id).val();
			break;
		case 1:
			val = cleanupFieldText($("<textarea>").html($('h3')[0]).text());
			break;
		// Case 2 references the ticket edit form and is currently unused
		case 2:
			val = cleanupFieldText($("<textarea>").html($('h3')[0]).text());
			break;
	}
	return val;

}

// Get the value of the Issue Description field
function getIssueDescVal(pgId) {

	var val = "";
	var tmp = "";

	switch(pgId) {
		case 0:
			val = $("div[placeholder='Details on the reason(s) for opening the ticket.']").children().text();
			break;
		case 1:
			tmp = $("div.thread-body.no-pjax")[0];
			val = cleanupFieldText($("<textarea>").html(tmp).text());
			break;
		// Case 2 references the ticket edit form and is currently unused
		case 2:
			tmp = $("div.thread-body.no-pjax")[0];
			val = cleanupFieldText($("<textarea>").html(tmp).text());
			break;

	}

	return val;

}

function cleanupFieldText(text) {

	return text.replace("\n", "").trim();

}

// Add a new card to the 'Os Ticket Installation board' with
// the issue summary and descritions from osTicket
function addCard(pgId, cardName, cardDesc) {
	
	var myList = '606f22e905669f3fa8e880c5';
	var creationSuccess;

	switch(pgId) {
		case 0:
			creationSuccess = function (data) {
				var stamp = dtStamp() + ' - Card added to <strong>Unite.ly Bugs 2021</strong> Trello board by ' + getUser();
				console.log(JSON.stringify(data, null, 2));
				$("div[placeholder='Optional internal note (recommended on assignment)']").append("<p>" + stamp + "</p>");
				alert('New Trello Card Created');
			};
			break;
		case 1:
			$('a#post-note-tab').click();
			creationSuccess = function (data) {
				var stamp = dtStamp() + ' - Card added to <strong>Unite.ly Bugs 2021</strong> Trello board by ' + getUser();
				console.log(JSON.stringify(data, null, 2));
				$('input#title')[0].value = "Trello Card Added";
				$("div[placeholder='Note details']").append("<p>" + stamp + "</p>");
				alert('New Trello Card Created');
			};
			break;
		// Case 2 references the ticket edit form and is currently unused
		case 2:
			break;
	}

	var newCard = {
		name: cardName,
		desc: cardDesc,
		// Place this card at the top of our list
		idList: myList,
		pos: 'top'
	};

	window.Trello.post('/cards/', newCard, creationSuccess);

}

function getUser() {
	
	var user = $('p#info').children()[0].innerText

	return user;

}

function dtStamp() {
	
	var str = "";

	var currentTime = new Date()

	var month = currentTime.getMonth()
	var day = currentTime.getDay()
	var yr = currentTime.getFullYear()

	var hours = currentTime.getHours()
	var minutes = currentTime.getMinutes()
	var seconds = currentTime.getSeconds()

	var apm = (hours > 11) ? "PM" : "AM"

	if (month < 10) {
		month = "0" + month
	}
	if (day < 10) {
		day = "0" + day
	}
	if (hours > 12) {
		hours = "0" + (hours - 12)
	}
	if (minutes < 10) {
		minutes = "0" + minutes
	}
	if (seconds < 10) {
		seconds = "0" + seconds
	}
	str += month + "/" + day + "/" + yr + " " + hours + ":" + minutes + ":" + seconds + " " + apm;

	return str;

}