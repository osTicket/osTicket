<?php
require_once INCLUDE_DIR . 'class.plugin.php';

class TinyMCEPluginConfig extends PluginConfig
{

    // Provide compatibility function for versions of osTicket prior to
    // translation support (v1.9.4)
    function translate()
    {
        if (! method_exists('Plugin', 'translate')) {
            return array(
                function ($x) {
                    return $x;
                },
                function ($x, $y, $n) {
                    return $n != 1 ? $y : $x;
                }
            );
        }
        return Plugin::translate('tinymce');
    }

    /**
     * Build an Admin settings page.
     *
     * {@inheritdoc}
     *
     * @see PluginConfig::getOptions()
     */
    function getOptions()
    {
        list ($__, $_N) = self::translate();
        $themes = array();
        $directory = $_SERVER['DOCUMENT_ROOT'] . ROOT_PATH . "js/tinymce/";
        if(is_dir($directory . "themes") && ($themesfound = scandir($directory . "themes")))
            foreach(preg_grep('/^([^.])/', $themesfound) as $theme)
                $themes[$theme] = $theme;
        $skins = array();
        if(is_dir($directory . "skins") && ($skinsfound = scandir($directory . "skins")))
            foreach(preg_grep('/^([^.])/', $skinsfound) as $skin)
                $skins[$skin] = $skin;
        return array(
            'mainoptions' => new SectionBreakField([
                'label' => $__('Main options'),
                'hint' => $__('Mainly visual settings.'),
                'default' => TRUE
            ]),
            'plugins' => new ChoiceField([
                'label' => $__('Plugins'),
                'required' => false,
                'hint' => $__('What plugins do you want to load.'),
                'default' => array(
                    'advlist' => __('Advanced list'),
                    'anchor' => __('Anchor'),
                    'autolink' => __('Autolink'),
                    'charmap' => __('Unicode characters'),
                    'code' => __('Edit HTML'),
                    'contextmenu' => __('Context menu'),
                    'fullscreen' => __('Fullscreen'),
                    'help' => __('Help'),
                    'image' => __('Images'),
                    'insertdatetime' => __('Date and time'),
                    'link' => __('Links'),
                    'lists' => __('Normalize lists'),
                    'media' => __('HTML5 Media'),
                    'paste' => __('Filter office content'),
                    'preview' => __('Preview'),
                    'print' => __('Print'),
                    'searchreplace' => __('Search and Replace'),
                    'table' => __('Table'),
                    'textcolor' => __('Text color'),
                    'wordcount' => __('Word count'),
                ),
                'configuration'=>array('multiselect'=>true,'prompt'=>__('Plugins')),
                'choices' => array(
                    __('Free Plugins') => array(
                        'advlist' => __('Advanced list'),
                        'anchor' => __('Anchor'),
                        'autolink' => __('Autolink'),
                        'autoresize' => __('Auto resize'),
                        'bbcode' => __('Enable bbcode'),
                        'charmap' => __('Unicode characters'),
                        'code' => __('Edit HTML'),
                        'codesample' => __('Insert code samples'),
                        'emoticons' => __('Emoticons'),
                        'fullpage' => __('Edit document properties'),
                        'fullscreen' => __('Fullscreen'),
                        'help' => __('Help'),
                        'hr' => __('Horizontal rule'),
                        'image' => __('Images'),
                        'imagetools' => __('Image editing'),
                        'insertdatetime' => __('Date and time'),
                        'legacyoutput' => __('Legacy output'),
                        'link' => __('Links'),
                        'lists' => __('Normalize lists'),
                        'media' => __('HTML5 Media'),
                        'nonbreaking' => __('Non breaking character'),
                        'pagebreak' => __('Page break'),
                        'paste' => __('Filter office content'),
                        'preview' => __('Preview'),
                        'print' => __('Print'),
                        'searchreplace' => __('Search and Replace'),
                        'tabfocus' => __('Tab focus'),
                        'table' => __('Table'),
                        'textcolor' => __('Text color'),
                        'textpattern' => __('Textpatterns'),
                        'toc' => __('Table of Contents'),
                        'visualblocks' => __('Visual Blocks'),
                        'visualchars' => __('Make invisible characters visible'),
                        'wordcount' => __('Word count'),
                    ),
                    __('Fee based plugins') => array(
                        'a11ychecker' => __('Accessibility checker'),
                        'advcode' => __('Powerful HTML editor'),
                        'linkchecker' => __('Test links'),
                        'mediaembed' => __('Embed Rich Media'),
                        'powerpaste' => __('Automaticly cleanup office content'),
                        'tinymcespellchecker' => __('Spell checker'),
                    ),
                )
            ]),
            'toolbar' => new TextareaField([
                'label' => $__('Toolbar'),
                'required' => false,
                'configuration'=>array('cols'=>50,'length'=>1024,'rows'=>4,'html'=>false),
                'hint' => sprintf($__('How do you want your toolbar to look like, %s'), '<a href="https://www.tinymce.com/docs/configure/editor-appearance/#toolbar">Toolbar</a>'),
                'default' => 'insert | undo redo |  styleselect | bold italic backcolor  | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | help'
            ]),
            'theme' => new ChoiceField([
                'label' => $__('Theme'),
                'required' => true,
                'hint' => $__('What theme do you want to use.'),
                'default' => 'modern',
                'choices' => $themes
            ]),
			'contextmenu' => new ChoiceField([
                'label' => $__('Context Menu'),
                'required' => true,
                'hint' => $__('Context Menu Items when righ clicking.'),
                'default' => array(
                    'forecolor' => __('Font Color'),
                    'backcolor' => __('Background Color'),
                    'advlist' => __('Advanced list'),
                    'anchor' => __('Anchor'),
                    'autolink' => __('Autolink'),
                    'charmap' => __('Unicode characters'),
                    'code' => __('Edit HTML'),
                    'fullscreen' => __('Fullscreen'),
                    'help' => __('Help'),
                    'image' => __('Images'),
                    'insertdatetime' => __('Date and time'),
                    'link' => __('Links'),
                    'lists' => __('Normalize lists'),
                    'media' => __('HTML5 Media'),
                    'paste' => __('Paste'),
					'copy' => __('Copy'),
					'cut' => __('Cut'),
                    'preview' => __('Preview'),
                    'print' => __('Print'),
                    'searchreplace' => __('Search and Replace'),
                    'table' => __('Table'),
                    'wordcount' => __('Word count')
					),
                'configuration'=>array('multiselect'=>true,'prompt'=>__('contextmenu')),
                'choices'  => array(
                    'forecolor' => __('Font Color'),
                    'backcolor' => __('Background Color'),
					'advlist' => __('Advanced list'),
                    'anchor' => __('Anchor'),
                    'autolink' => __('Autolink'),
                    'charmap' => __('Unicode characters'),
                    'code' => __('Edit HTML'),
                    'fullscreen' => __('Fullscreen'),
                    'help' => __('Help'),
                    'image' => __('Images'),
                    'insertdatetime' => __('Date and time'),
                    'link' => __('Links'),
                    'lists' => __('Normalize lists'),
                    'media' => __('HTML5 Media'),
                    'paste' => __('Paste'),
					'copy' => __('Copy'),
					'cut' => __('Cut'),
                    'preview' => __('Preview'),
                    'print' => __('Print'),
                    'searchreplace' => __('Search and Replace'),
                    'table' => __('Table'),
                    'wordcount' => __('Word count')
					),
            ]),
            'skin' => new ChoiceField([
                'label' => $__('Skin'),
                'required' => true,
                'hint' => sprintf($__('What skin do you want to use. %s'), '<a href="http://skin.tinymce.com/">Skin creator</a>'),
                'default' => 'lightgray',
                'choices' => $skins
            ]),
            'browserspellcheck' => new BooleanField([
                'label' => $__('Browser spellchecker'),
                'required' => false,
                'hint' => $__('Enable the browsers native spellchecker.'),
                'default' => true
            ]),
            'menubar' => new BooleanField([
                'label' => $__('Show menubar'),
                'required' => false,
                'hint' => $__('Display the menubar or not.'),
                'default' => true
            ]),
            'poweredby' => new BooleanField([
                'label' => $__('Show powered by message'),
                'required' => false,
                'hint' => $__('Display the powered by message or not.'),
                'default' => true
            ]),
            'height' => new TextboxField([
                'label' => $__('Height'),
                'required' => true,
                'size'=>16,
                'validator' => 'number',
                'hint' => $__('The default height of TinyMCE'),
                'default' => 250
            ]),
            'jsfile' => new ChoiceField([
                'label' => $__('TinyMCE source'),
                'required' => true,
                'hint' => $__('Where do you want to load TinyMCE from.'),
                'default' => 'js',
                'choices' => array(
                    'js' => sprintf(__('Javascript folder (%s)'), 'js/tinymce'),
                    'cloud' => __('Cloud hosted'),
                )
            ]),
            'apikey' => new TextboxField([
                'label' => $__('TinyMCE API Key'),
                'required' => false,
                'size'=>16,
                'hint' => sprintf($__('If you\'re using cloud hosted TinyMCE you may require an API key.<br/>%sSign up for a API key%s'), '<a href="https://store.ephox.com/signup/">', '</a>'),
                'default' => ''
            ]),
            'autosaveoptions' => new SectionBreakField([
                'label' => $__('Autosave options'),
                'hint' => $__('The options regarding autosaving/drafts.'),
                'default' => TRUE
            ]),
            'doautosave' => new BooleanField([
                'label' => $__('Enable autosaving'),
                'required' => false,
                'hint' => $__('TinyMCE will create drafts automaticly.'),
                'default' => true
            ]),
            'autosaveinterval' => new TextboxField([
                'label' => $__('Autosave frequency'),
                'required' => false,
                'size'=>16,
                'validator' => 'number',
                'hint' => $__('How long between each save in seconds.'),
                'default' => '30'
            ]),
            'tryrestoreempty' => new BooleanField([
                'label' => $__('Restore when empty'),
                'required' => false,
                'hint' => $__('If the user has a draft available restore it automaticly.'),
                'default' => true
            ]),
            'autosaveretention' => new TextboxField([
                'label' => $__('Draft lifespan'),
                'required' => false,
                'size'=>16,
                'validator' => 'number',
                'hint' => $__('How long should a draft be stored for in minutes.'),
                'default' => '30'
            ]),
        );
    }
}
