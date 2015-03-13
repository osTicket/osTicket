/*
 * jQuery dropdown: A simple dropdown plugin
 *
 * Inspired by Bootstrap: http://twitter.github.com/bootstrap/javascript.html#dropdowns
 *
 * Copyright 2011 Cory LaViska for A Beautiful Site, LLC. (http://abeautifulsite.net/)
 *
 * Dual licensed under the MIT or GPL Version 2 licenses
 *
*/
if(jQuery) (function($) {

	$.extend($.fn, {
		dropdown: function(method, data) {

			switch( method ) {
				case 'hide':
					hideDropdowns();
					return $(this);
				case 'attach':
					return $(this).attr('data-dropdown', data);
				case 'detach':
					hideDropdowns();
					return $(this).removeAttr('data-dropdown');
				case 'disable':
					return $(this).addClass('dropdown-disabled');
				case 'enable':
					hideDropdowns();
					return $(this).removeClass('dropdown-disabled');
			}

		}
	});

	function showMenu(event) {

		var trigger = $(this),
			dropdown = $( $(this).attr('data-dropdown') ),
			isOpen = trigger.hasClass('dropdown-open'),
            rtl = $('html').hasClass('rtl');

		event.preventDefault();
		event.stopPropagation();

		hideDropdowns();

		if( isOpen || trigger.hasClass('dropdown-disabled') ) return;

        if (rtl && dropdown.hasClass('anchor-right'))
            dropdown.removeClass('anchor-right');

		dropdown.css({
				left: dropdown.hasClass('anchor-right') ?
				trigger.offset().left - (dropdown.outerWidth() - trigger.outerWidth() - 4) : trigger.offset().left,
				top: trigger.offset().top + trigger.outerHeight()
			}).show();
		trigger.addClass('dropdown-open');
	}

	function hideDropdowns(event) {

		var targetGroup = event ? $(event.target).parents().andSelf() : null;
		if( targetGroup && targetGroup.is('.action-dropdown') && !targetGroup.is('a') ) return;

		$('body')
			.find('.action-dropdown').hide().end()
			.find('[data-dropdown]').removeClass('dropdown-open');
	}

	$(function () {
		$('body').on('click.dropdown', '[data-dropdown]', showMenu);
		$('html').on('click.dropdown', hideDropdowns);
		if( !$.browser.msie || ($.browser.msie && $.browser.version >= 9) ) {
			$(window).on('resize.dropdown', hideDropdowns);
		}
	});

})(jQuery);
