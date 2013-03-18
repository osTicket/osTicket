<?php
#########################################################
# Copyright © 2008 Darrin Yeager                        #
# http://www.dyeager.org/                               #
# Licensed under BSD license.                           #
#   http://www.dyeager.org/downloads/license-bsd.txt    #
#########################################################

function getDefaultLanguage() {
   if (isset($_SERVER["HTTP_ACCEPT_LANGUAGE"]))
      return parseDefaultLanguage($_SERVER["HTTP_ACCEPT_LANGUAGE"]);
   else
      return parseDefaultLanguage(NULL);
   }

function parseDefaultLanguage($http_accept, $deflang = "en") {
   if(isset($http_accept) && strlen($http_accept) > 1)  {
      # Split possible languages into array
      $x = explode(",",$http_accept);
      foreach ($x as $val) {
         #check for q-value and create associative array. No q-value means 1 by rule
         if(preg_match("/(.*);q=([0-1]{0,1}\.\d{0,4})/i",$val,$matches))
            $lang[$matches[1]] = (float)$matches[2];
         else
            $lang[$val] = 1.0;
      }

      #return default language (highest q-value)
      $qval = 0.0;
      foreach ($lang as $key => $value) {
         if ($value > $qval) {
            $qval = (float)$value;
            $deflang = $key;
         }
      }
   }
   return strtolower($deflang);
}
$language=getDefaultLanguage();
echo "Your browser preferred language is: '".$language."'<br>";

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
if(!file_exists('include/locale/'.$tmplangcode)||!is_dir('include/locale/'.$tmplangcode))
{
	$tmplangcode=$language.'_'.strtolower($lang_dialect);
	if(!file_exists('include/locale/'.$tmplangcode)||!is_dir('include/locale/'.$tmplangcode))
	{
		$tmplangcode=$language.'_'.strtoupper($lang_dialect);
		if(!file_exists('include/locale/'.$tmplangcode)||!is_dir('include/locale/'.$tmplangcode))
		{
			$tmplangcode=$language.'-'.strtoupper($lang_dialect);
			if(!file_exists('include/locale/'.$tmplangcode)||!is_dir('include/locale/'.$tmplangcode))
			{
				if(!file_exists('include/locale/'.$language)||!is_dir('include/locale/'.$language)) //check short langcode
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
if(file_exists('include/locale/'.$language.'/redirect'))
{
	$f = fopen('include/locale/'.$language.'/redirect','r');
	if($f!==false)
	{
		$line = fgets($f);
		if(strlen($line)>=2) //safety check
		{
			echo "using the redirect file include/locale/".$language."/redirect<br>";
			$language=$line; //redirect language
			echo "redirecting to '".$language."'<br>";
		}
		fclose($f);
	}
}

echo "The following folder will be used to translate your osticket: '".$language."'";


$old_error_reporting = error_reporting();
error_reporting (E_ERROR);
$f = fopen('include/locale/'.$language.'/LC_MESSAGES/messages.mo', 'r');
$meta = stream_get_meta_data($f);
if($meta['mode']==NULL)
{
	echo '<br><b>ERROR: The translation file "include/locale/'.$language.'/LC_MESSAGES/messages.mo" isn\'t readable, check permissions.</b><br>';
}
else
{
	fclose($f);
}
error_reporting($old_error_reporting);
?>