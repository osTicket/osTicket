$(document).ready(function(){
	// Show search box in Client Portal pages with sidebar
	if ( $( "a.active.kb" ).length ) {
		$( "#landing-search" ).addClass( "show" );		
		$( "#pre-footer-inner" ).addClass( "show" );		
	} else {
		$( "#landing-search" ).addClass( "hide" );
		$( "#pre-footer-inner" ).addClass( "hide" );	
	}
	if ( $( "#landing-search.show" ).length ) {	
		$( "#pre-footer-inner" ).removeClass( "show" );	
		$( "#pre-footer-inner" ).addClass( "hide" );		
	} else {
		$( "#pre-footer-inner" ).removeClass( "hide" );			
		$( "#pre-footer-inner" ).addClass( "show" );	
	}
});

$(document).ready(function(){
	// Hide client-side Cookie Consent Bar when button clicked
	var setCookie = function(cname, cvalue, exdays) {
	  var d = new Date();
	  d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
	  var expires = "expires=" + d.toUTCString();
	  document.cookie = cname + "=" + cvalue + "; " + expires;
	}
	var getCookie = function(cname) {
	  var name = cname + "=";
	  var ca = document.cookie.split(';');
	  for (var i = 0; i < ca.length; i++) {
		var c = ca[i];
		while (c.charAt(0) == ' ') c = c.substring(1);
		if (c.indexOf(name) == 0) return c.substring(name.length, c.length);
	  }
	  return "";
	}
	jQuery(document).ready(function($) {
	  console.log(getCookie("closed"));
	  if (getCookie("closed") == "closed") {
		$("#complianceouter").hide();
	  }
	  jQuery("#complaince-button").click(function() {
		jQuery("#complianceouter").remove();
		setCookie("closed", "closed", 365)
	  });
	});
});
