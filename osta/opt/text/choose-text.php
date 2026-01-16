<?php 
require( "../../../bootstrap.php");
Bootstrap::loadConfig();
Bootstrap::defineTables(TABLE_PREFIX);
Bootstrap::i18n_prep();
Bootstrap::loadCode();
Bootstrap::connect();

$themes = array( "title", "subtitle", "mobile-text", "mobile-link" );
for( $x=0; $x<sizeof( $themes ); $x++ ) {
	$sql = "SELECT * FROM config WHERE `namespace`=\"osticketawesome\" and `key`=\"" . $themes[$x] . "\"";
	$res = db_query($sql);
	if(  db_fetch_array($res) ) { 
		db_query("UPDATE config set `value` = '" . $_POST[ $themes[$x]  ] . "' where `namespace`='osticketawesome' and `key`='" . $themes[$x]  . "'");
	}
	else { 
		$sql = "INSERT INTO `config` (`id`,`namespace`,`key`, `value` ) VALUES ((77780 + $x),'osticketawesome' , '" . $themes[$x] . "', '" . $_POST[ $themes[$x] ] . "')";
		$res = db_query($sql);
	}
	
}	
header('Location: ' . $_SERVER['HTTP_REFERER']);
?>