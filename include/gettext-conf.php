<?php
//Multilanguage Support
//To add additional languages add a folder with your language code to 'include/locale' (for example 'de-de'), create the folder 'LC_MESSAGES' inside it and create your
//'messages.po' file inside 'LC_MESSAGES'. With the example of de-de the full path to 'messages.po' should look like 'include/locale/de-de/LC_MESSAGES/messages.po'.
//In case you don't know your language code (or to be more precise: the one your browser prefers), open the php page: 'testlang.php'

//the language detection first checks if a language folder, that has the same name as the preferred broser language exists
//if that is the case it checks whether it should redirect to another language folder or not
//if that isn't the case it tries to split the language code to its short version and does the same checks
//it doesn't check the sanity of the redirect file, so make sure the content is valid

//to redirect a language code to a different one, create a folder inside 'include/locale' that represents the language code you want to redirect and then create a file called 'redirect' in it.
//the content of 'redirect' has to be the language code it should redirect to.
//Example:
//you want to redirect the language code 'de-de' to the code 'de'
//create a folder inside 'include/locale' called 'de-de'. then create the file 'redirect' in it. the path to this redirect should now look like 'include/locale/de-de/redirect'.
//now open the redirect file you've created, type de in the first line and save it


$use_php_gettext=true; //Set this to false to disable php_gettext

require_once(INCLUDE_DIR.'locale/lang.php');

if($use_php_gettext==true&&function_exists('mb_detect_encoding'))
{
	require_once(INCLUDE_DIR.'gettext.inc');
}
$language=getDefaultLanguage(); //if you want to use just one static language replace the call to getDefaultLanguage() with your language code (for example 'de-de')

//get the first and second part of the language code
if(strpos($language,'_')!==false)
{
	$language=substr($language,0,strpos($language,'_'));
	$lang_dialect=substr($language,strpos($language,'_'));
}
elseif(strpos($language,'-')!==false)
{
	$language=substr($language,0,strpos($language,'-'));
	$lang_dialect=substr($language,strpos($language,'-'));
}
if(!isset($lang_dialect))
{
	$lang_dialect=$language;
}

$tmplangcode=$language.'-'.strtolower($lang_dialect);
if(!file_exists(INCLUDE_DIR.'locale/'.$tmplangcode)||!is_dir(INCLUDE_DIR.'locale/'.$tmplangcode))
{
	$tmplangcode=$language.'_'.strtolower($lang_dialect);
	if(!file_exists(INCLUDE_DIR.'locale/'.$tmplangcode)||!is_dir(INCLUDE_DIR.'locale/'.$tmplangcode))
	{
		$tmplangcode=$language.'_'.strtoupper($lang_dialect);
		if(!file_exists(INCLUDE_DIR.'locale/'.$tmplangcode)||!is_dir(INCLUDE_DIR.'locale/'.$tmplangcode))
		{
			$tmplangcode=$language.'-'.strtoupper($lang_dialect);
			if(!file_exists(INCLUDE_DIR.'locale/'.$tmplangcode)||!is_dir(INCLUDE_DIR.'locale/'.$tmplangcode))
			{
				if(!file_exists(INCLUDE_DIR.'locale/'.$language)||!is_dir(INCLUDE_DIR.'locale/'.$language)) //check short langcode
				{
					$language='en'; //set as default
				}
			}
			else
			{
				$language=$tmplangcode;
			}
		}
		else
		{
			$language=$tmplangcode;
		}
	}
	else
	{
		$language=$tmplangcode;
	}
}
else
{
	$language=$tmplangcode;
}
//check if a redirect file is in there
if(file_exists(INCLUDE_DIR.'locale/'.$language.'/redirect'))
{
	$f = fopen(INCLUDE_DIR.'locale/'.$language.'/redirect','r');
	if($f!==false)
	{
		$line = fgets($f);
		if(strlen($line)>=2) //safety check
		{
			$language=$line; //redirect language
		}
		fclose($f);
	}
}

// gettext setup
$domain = 'messages';
if(extension_loaded('gettext')&&$use_php_gettext==false)
{
	putenv('LC_ALL=' . $language);
	setlocale(LC_ALL, $language);
	bindtextdomain($domain, INCLUDE_DIR.'locale');
	textdomain($domain);
	if(!function_exists('__'))
	{
		function __($text){return _($text);}
	}
}
else if($use_php_gettext==true&&function_exists('mb_detect_encoding'))
{
	T_setlocale(LC_ALL, $language);
	// Set the text domain as 'messages'
	T_bindtextdomain($domain, INCLUDE_DIR.'locale');
	T_bind_textdomain_codeset($domain, 'UTF-8');
	T_textdomain($domain);
}
else
{
	if(!function_exists('__'))
	{
		function __($text){return $text;} //fallback definition: in case the gettext extension wasn't loaded osticket should at least work in english
	}
}
?>