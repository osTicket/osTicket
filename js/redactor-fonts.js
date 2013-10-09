if (!RedactorPlugins) var RedactorPlugins = {};

RedactorPlugins.fontfamily = {
	init: function ()
	{
		var fonts = [ 'Arial', 'Helvetica', 'Georgia', 'Times New Roman', 'Monospace' ];
		var that = this;
		var dropdown = {};

		$.each(fonts, function(i, s)
		{
			dropdown['s' + i] = {
                title: '<span style="font-family:'+s+';">'+s+'</style>' ,
                callback: function() { that.setFontfamily(s); }};
		});

		dropdown['remove'] = { title: 'Remove font', callback: function() { that.resetFontfamily(); }};

		this.buttonAddBefore('bold', 'fontfamily', 'Change font family', false, dropdown);
	},
	setFontfamily: function (value)
	{
		this.inlineSetStyle('font-family', value);
	},
	resetFontfamily: function()
	{
		this.inlineRemoveStyle('font-family');
	}
};

RedactorPlugins.fontcolor = {
	init: function()
	{
		var colors = ['#ffffff', '#000000', '#eeece1', '#1f497d', '#4f81bd', '#c0504d', '#9bbb59', '#8064a2', '#4bacc6', '#f79646', '#ffff00', '#f2f2f2', '#7f7f7f', '#ddd9c3', '#c6d9f0', '#dbe5f1', '#f2dcdb', '#ebf1dd', '#e5e0ec', '#dbeef3', '#fdeada', '#fff2ca', '#d8d8d8', '#595959', '#c4bd97', '#8db3e2', '#b8cce4', '#e5b9b7', '#d7e3bc', '#ccc1d9', '#b7dde8', '#fbd5b5', '#ffe694', '#bfbfbf', '#3f3f3f', '#938953', '#548dd4', '#95b3d7', '#d99694', '#c3d69b', '#b2a2c7', '#b7dde8', '#fac08f', '#f2c314', '#a5a5a5', '#262626', '#494429', '#17365d', '#366092', '#953734', '#76923c', '#5f497a', '#92cddc', '#e36c09', '#c09100', '#7f7f7f', '#0c0c0c', '#1d1b10', '#0f243e', '#244061', '#632423', '#4f6128', '#3f3151', '#31859b', '#974806', '#7f6000'];
		var buttons = ['fontcolor', 'backcolor'];

		for (var i = 1; i >= 0 ; i--)
		{
			var name = buttons[i];

			var $dropdown = $('<div class="redactor_dropdown redactor_dropdown_box_' + name + '" style="display: none; width: 210px;">');

			this.pickerBuild($dropdown, name, colors);
			$(this.$toolbar).append($dropdown);

			this.buttonAddAfter('deleted', name, this.opts.curLang[name], $.proxy(function(btnName, $button, btnObject, e)
			{
				this.dropdownShow(e, btnName);

			}, this));
		}
		this.buttonAddSeparatorBefore(buttons[0]);

	},
	pickerBuild: function($dropdown, name, colors)
	{
		var rule = 'color';
		if (name === 'backcolor') rule = 'background-color';

		var _self = this;
		var onSwatch = function(e)
		{
			e.preventDefault();

			var $this = $(this);
			_self.pickerSet($this.data('rule'), $this.attr('rel'));

		}

		var len = colors.length;
		for (var z = 0; z < len; z++)
		{
			var color = colors[z];

			var $swatch = $('<a rel="' + color + '" data-rule="' + rule +'" href="#" style="float: left; font-size: 0; border: 2px solid #fff; padding: 0; margin: 0; width: 15px; height: 15px;"></a>');
			$swatch.css('background-color', color);
			$dropdown.append($swatch);
			$swatch.on('click', onSwatch);
		}

		var $elNone = $('<a href="#" style="display: block; clear: both; padding: 4px 0; font-size: 11px; line-height: 1;"></a>')
		.html(this.opts.curLang.none)
		.on('click', function(e)
		{
			e.preventDefault();
			_self.pickerSet(rule, false);
		});

		$dropdown.append($elNone);
	},
	pickerSet: function(rule, type)
	{
		this.bufferSet();

		this.$editor.focus();
		this.inlineRemoveStyle(rule);
		if (type !== false) this.inlineSetStyle(rule, type);
		if (this.opts.air) this.$air.fadeOut(100);
		this.sync();
	}
};

RedactorPlugins.fontsize = {
	init: function()
	{
		var fonts = [10, 14, 22, 32];
		var that = this;
		var dropdown = {};

		$.each(fonts, function(i, s)
		{
			dropdown['s' + i] = {
                title: '<span style="font-size:'+s+'px">'+s+'px</span>',
                callback: function() { that.setFontsize(s); } };
		});

		dropdown['remove'] = { title: 'Remove font size', callback: function() { that.resetFontsize(); } };

		this.buttonAddAfter('formatting', 'fontsize', 'Change font size', false, dropdown);
	},
	setFontsize: function(size)
	{
		this.inlineSetStyle('font-size', size + 'px');
	},
	resetFontsize: function()
	{
		this.inlineRemoveStyle('font-size');
	}
};
