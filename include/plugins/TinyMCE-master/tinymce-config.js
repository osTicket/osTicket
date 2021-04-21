tiny = $.tiny = function(el, options) {
	tinymce.init({
		target: el,
		height: {TINYMCE_HEIGHT},
		width: '100%',
		statusbar: false,
		theme: '{TINYMCE_THEME}',
		skin: '{TINYMCE_SKIN}',
		menubar: {TINYMCE_MENUBAR},
		branding: {TINYMCE_POWERED_BY},
		browser_spellcheck: {TINYMCE_BROWSER_SPELLCHECK},
		plugins: '{TINYMCE_PLUGINS}{TINYMCE_STAFF_PLUGINS} embedvideo closeextras',
		contextmenu:'{TINYMCE_CONTEXT}',
		toolbar: '{TINYMCE_TOOLBAR}',
		{TINYMCE_LANGUAGE}
		paste_data_images: true,
		{TINYMCE_AUTOSAVEOPTIONS}
		init_instance_callback: function (editor) {
			editor.on('blur', function (e) {
				$(this).siblings('textarea').trigger('change');
			});
		}
	});
}