var folder = window.location.pathname;
folder = folder.substr(0, folder.lastIndexOf('/'));
var baseUrl = window.location.protocol + "//" + window.location.host + folder ;
// esc
Mousetrap.bind('esc', function(e) {
   $('#help').modal('toggle');
});
// shift + ?
Mousetrap.bind('?', function(e) {
  $('#help').modal('toggle');
});
// shift + up
Mousetrap.bind('shift+up', function(e) {
	window.scrollTo({ top: 0, behavior: 'smooth' });
});
// shift + down
Mousetrap.bind('shift+down', function(e) {
	  $("html, body").animate({ scrollTop: $(document).height() }, 1000);
});
// shift + a	
Mousetrap.bind('shift+a', function(e) {
	  javascript:$.dialog('ajax.php/tickets/search', 201);
});
// shift + b	
Mousetrap.bind('shift+b', function(e) {
	  history.back();
});
// shift + e	
Mousetrap.bind('shift+e', function(e) {

    var loc = location.href;        
    loc += loc.indexOf("?") === -1 ? "?" : "&";

    location.href = loc + '&a=edit'; 	  
	  
});
// shift + f	
Mousetrap.bind('shift+f', function(e) {
	  history.forward();
});
// shift + h	
Mousetrap.bind('shift+h', function(e) {
	location.href = window.location.protocol + "//" + window.location.host + folder + ('/tickets.php?a=open');
});
// shift + l	
Mousetrap.bind('shift+l', function(e) {
	location.href = window.location.protocol + "//" + window.location.host + folder + ('/logout.php');
});
// shift + n	
Mousetrap.bind('shift+n', function(e) {
	location.href = window.location.protocol + "//" + window.location.host + folder + ('/tickets.php?a=open');
});
// shift + o	
$(document).ready(function() {
if (window.location.href.indexOf("theme.php") > -1) {
	Mousetrap.bind('shift+o', function(e) {
		location.href = window.location.href.split('#')[0].replace('/scp/theme.php', '/osta/old/scp/settings.php?t=pages#logos');
	});	
} else {
		Mousetrap.bind('shift+o', function(e) {
			location.href = location.href.replace('/scp', '/osta/old/scp');
		});	
	}
});	
// shift + p
Mousetrap.bind('shift+p', function(e) {
	location.href = window.location.protocol + "//" + window.location.host + folder + ('/profile.php');
});
// shift + r	
Mousetrap.bind('shift+r', function(e) {
	location.reload();
});	
// shift + s	
Mousetrap.bind('shift+s', function(e) {
	if (e.preventDefault) {
		e.preventDefault();
	} else {
		// internet explorer
		e.returnValue = false;
	}
	$(".basic-search").focus();
});
// shift + t	
Mousetrap.bind('shift+t', function(e) {
	location.href = window.location.protocol + "//" + window.location.host + folder + ('/tasks.php');
});	
// shift + u
Mousetrap.bind('shift+u', function(e) {
	location.href = window.location.protocol + "//" + window.location.host + folder + ('/users.php');
});	
// shift + z	
Mousetrap.bind('shift+r', function(e) {
	location.href = window.location.protocol + "//" + window.location.host + folder + ('/orgs.php');
});	
