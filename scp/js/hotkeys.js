$(document).bind('keydown', function(e) {
  
	if($('#response').length) {
  
		//ctrl + shift -> send (cannot do enter bc bubbling up event creates a line break - would have to re-init every time box is rendered)
		if(e.ctrlKey && e.which == 16) {
			
			$(':focus').blur();

			setTimeout(function() {
				$('input.save[type=submit][value="Post Reply"]').click();
			}, 250);
			
			return false;
		}
	  
		//ctrl + s -> canned response + move cursor to end
		if(e.ctrlKey && e.which == 83) {
			
			window.scrollTo(0, document.body.scrollHeight);
			
			$R('#response', { focusEnd: true });
			$('#response').prev().focus();
			
			$('#cannedResp').select2('open');
			
			return false;
		}
	  
	  
		//ctrl + m  -> scroll to msg + cursor at end
		if(e.ctrlKey && e.which == 77) {
			
			window.scrollTo(0, document.body.scrollHeight);
			
			$R('#response', { focusEnd: true });
			$('#response').prev().focus();
			
			return false;
		}
		
		//ctrl + del -> delete ticket
		if(e.ctrlKey && e.which == 46) {
			
			//dialog open
			const delBtn = $('form#status input[type=submit][value="Delete"]');
			if(delBtn.length) {
				
				delBtn.click();
				return false;
			}
			
			
			$('li.danger a.ticket-action').each(function() {
				
				const href = $(this).attr('href');
				if(href.indexOf('delete') != -1)
					$(this).click();
			});
			
			return false;
		}
		
		//ctrl + end -> close ticket
		if(e.ctrlKey && e.which == 35) {
			
			//dialog open
			const closeBtn = $('form#status input[type=submit][value="Close"]');
			if(closeBtn.length) {
				
				closeBtn.click();
				return false;
			}
			
			
			$('li a.ticket-action').each(function() {
				
				const href = $(this).attr('href');
				if(href.indexOf('/status/close') != -1)
					$(this).click();
			});
			
			return false;
		}


		//shift + backspace -> ban email
		if(e.shiftKey && e.which == 8) {
			
			//dialog open
			const closeBtn = $('form#confirm-form input[type=submit][value="OK"]');
			
			if(closeBtn.length && closeBtn.is(':visible')) {
				
				closeBtn.click();
				return false;
			}
			
			$('a#ticket-banemail').click();
			
			return false;
		}
		
		//ctrl + arrow right -> next ticket
		if(e.ctrlKey && e.which == 39) {
			
			
			
		}
		
		
		//ctrl + arrow left -> prev ticket
		if(e.ctrlKey && e.which == 37) {
			
			
			
		}
		
		
    }
  
	//check list view
	else if($('input[name="tids[]"]').length) {
		
		//ctrl + enter -> next msg
		if(e.ctrlKey && e.which == 13) {
			
			window.location = $('table.tickets tbody tr:first-child td:nth-child(2) a').attr('href');
			
			return false;
		}
		
		//ctrl + 1-9 -> open indexed number (numpad only)
		if(e.ctrlKey && e.which >= 97 && e.which <= 105) {
			
			const index = e.which - 96;
			
			window.location = $('table.tickets tbody tr:nth-child(' + index + ') td:nth-child(2) a').attr('href');
			
			return false;
		}
		
	}
	
	
	
	
});


//TODO ticket hlpr -> load info

//blur search field TODO have to figure out how to invoke on return from tickets -> listen for events
$(document).ready(function() {
	
	if($('input.basic-search').length && $('input.basic-search').is(':focus'))
		$('input.basic-search').blur();
	
	setTimeout(() => {
		
		if($('input.basic-search').length && $('input.basic-search').is(':focus'))
			$('input.basic-search').blur();
	}, 500);
});
