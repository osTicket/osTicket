<?php 
function default_config() { 
	return array( 
		"theme"=>"ice", 
		"logo-options"=>"default",
		
		"headerbg"=>"#1e1e1e", 
		"headertitlecolor"=>"#ffffff",
		"headertitlehovercolor"=>"#33f078",
		
		"navbarbg"=>"#3858e9", 
		"navbarlink"=>"#ffffff",
		"navbarlinkhover"=>"#33f078",
		
		"mobilemenubg"=>"#1e1e1e", 
		"mobilelinkcolor"=>"#ffffff",
		"stickybar"=>"#26292c",
		
		"title"=>"osTicket Awesome",
		"subtitle"=>"Support Ticket Center",
		"mobile-text"=>"osTicket Awesome",
		"mobile-link"=>"https://osticketawesome.com",
		
		"consent-message-option"=>"true",		
		"consent-message"=>"This website relies on temporary cookies to function, but no personal data is ever stored in the cookies.",
		"consent-message-button"=>"OK",
		
		"backdrop"=>"01",
		"upload-dir"=>  ROOT_PATH  . "osta/uploads/",
		"header-options"=> "header-solid-color", 
		"backdrop-options"=>"solid-color",
		"custom-backdrops"=>"[]",
		"custom-logos"=>"[]",
		"show-errors"=>"false",
		"scroll-to-top-option"=>"false",
		"desktop-scroll-option"=>"true",
		"mobile-scroll-option"=>"",
		"ie-redirect"=>"",
		"ie-redirect-url"=>"",
		"custom-print-logo"=>"",
		"custom-print-logo-enabled"=>"",
		"custom-css-enabled"=>"true",
		"custom-css"=>"/* Add your custom CSS code here.

		eg. p {	color: red; }
		
		CSS tips: http://www.w3schools.com/css/css_syntax.asp
		
		End of comment */ ",			
	);
};

function get_config() { 
	$config = default_config();
	$sql = "SELECT * FROM " . TABLE_PREFIX . "config WHERE `namespace`=\"osticketawesome\"";
	$res = db_query($sql);

	while(  $row = db_fetch_array($res) ) { 
		$config[ $row["key"] ] = $row["value"];
	}
	return $config;
}

function get_theme_css() { 
	ob_start();
	$config = get_config();
	$theme = $config["theme"];
	$base =  ROOT_PATH;
	?>
<style type="text/css">
	<?php
	if ( $theme == "custom" ) { 
		$themes = array( "headerbg"=>"header-bg", "headertitlecolor"=>"header-title", "headertitlehovercolor"=>"header-title-hover", "navbarbg"=>"nav-bar-bg", "navbarlink"=>"nav-bar-link", "navbarlinkhover"=>"nav-bar-link-hover", "mobilemenubg"=>"mobile-menu-bg", "mobilelinkcolor"=>"mobile-link-color" , "stickybar"=>"stickybar" );

		?>
			:root {
		<?php 
			foreach( $themes as $key=>$value ) { 
				echo "--" . $value . ": " . $config[$key] . ";";
			}
			?>}
		<?php
	}
	?>
	#header,
		#loginBody #brickwall,
		#background-solid-image .image	{
			background-image: url("<?php echo $base; ?>osta/img/backdrops/<?php echo $config["backdrop"]?>");
		}
	<?php
	if ( $config[ "header-options" ] == "header-solid-color" ) {
		?>
	#header {
		   background-image: initial !important;
		}
		<?php
	}
	?>
	<?php
	if ( $config[ "backdrop-options" ] == "solid-color" ) {
		?>
		#loginBody #brickwall {
			background-image: initial;
		}
		<?php
	}
	?>	
	<?php
	if ( $config[ "backdrop-options" ] == "solid-color" ) {
		?>
		#loginBody #brickwall {
			background-image: initial;
		}
		<?php
	}
	?></style>

	<link type="text/css" rel="stylesheet" href="<?php echo $base; ?>osta/opt/logo/logo-options-<?php  echo $config["logo-options"]  ?>.css">
	<link rel="shortcut icon" href="<?php echo $base; ?>osta/css/themes/<?php echo $theme ?>/favicon.ico">
	<link rel="shortcut icon" href="<?php echo $base; ?>osta/css/themes/<?php echo $theme ?>/favicon.png">
	

	<?php
	//if ( $theme != "custom" ) 
	echo  "<link type=\"text/css\" rel=\"stylesheet\" id=\"jssDefault\" href=\"" . $base . "osta/css/themes/" . $theme . ".css\">";
	if ( $config["custom-css-enabled"] == "true" )  echo "<style type=\"text/css\">" . $config["custom-css"] . "</style>";
	return ob_get_clean();
}

function chk($config,$key,$value) { 
	return ($config[$key] == $value ) ? " checked=\"checked\"" : "";
}

function notchk($config,$key,$value) { 
	return ($config[$key] != $value ) ? " checked=\"checked\"" : "";
}

function uploadImage($file, &$error, $upload_dir, $image_array, $aspect_ratio=2) {
      
        if (extension_loaded('gd')) {
            $source_path = $file['tmp_name'];
            list($source_width, $source_height, $source_type) = getimagesize($source_path);

            switch ($source_type) {
                case IMAGETYPE_GIF:
                case IMAGETYPE_JPEG:
                case IMAGETYPE_PNG:
                    break;
                default:
                    $error = __('Invalid image file type');
                    return false;
            }
        }

        if(!$file['name'] || $file['error'] || !is_uploaded_file($file['tmp_name'])) {
        	$error = __('Invalid Upload');
            return false;
        }

        list($key, $sig) = AttachmentFile::_getKeyAndHash($file['tmp_name'], true);

        $info=array('type'=>$file['type'],
                    'filetype'=>$ft,
                    'size'=>$file['size'],
                    'name'=>$file['name'],
                    'key'=>$key,
                    'signature'=>$sig,
                    'tmp_name'=>$file['tmp_name'],
                    );

        if ( move_uploaded_file( $info["tmp_name"], $_SERVER['DOCUMENT_ROOT'] . $upload_dir . $info["name"] ) ) 
		{
			$a = json_decode( $image_array , true);
			$a[] = array( "client"=>false, "staff"=>false, "image"=>$info["name"] );
			return json_encode($a);
		}
		$error = "Unable to write to:" .  $upload_dir . $info["name"] ;
		return false;
    }

function deleteImages($images, $deletedKeys, $upload_dir ) {
	$images = json_decode( $images, true );
	$out = [];
	foreach( $images as $k=>$v ) { 
		if ( in_array( $k, $deletedKeys ) ) { 
			unlink( $_SERVER['DOCUMENT_ROOT'] . $upload_dir . $v["image"] );
		}
		else  $out[] = $v;
	} 
	return json_encode( $out );

}

function selectImage($images, $selected, $type ) {
	$images = json_decode( $images, true );
	foreach( $images as $k=>$v ) { 
		//echo $type . " = " . $selected . " = " . $k . print_r( $v, true ) . "<BR>";
		$images[$k][$type] = $selected == $k ? true : false;
	} 
	return json_encode( $images );

}

function dbg($var) { 
	return "<pre>" . print_r( $var, true ) . "</pre>";
}

function get_logo($config, $type) {
	if( isset( $config ) && isset( $config["custom-logos"] ) ) { 
		
		 foreach( json_decode( $config["custom-logos"], true )  as $k=>$v ) { 
		 	if ( isset( $v[$type] ) && $v[$type] == true ) {
				
		 		return ( isset( $config["upload-dir"] ) ? $config["upload-dir"] : "" )  . $v["image"]; 
		 	}
		 };
	}
	return ROOT_PATH . "scp/logo.php";
}


function get_print_logo($config, $force_custom=false) {
	if( isset( $config ) && isset( $config["custom-print-logo"] ) && strlen( $config["custom-print-logo"] ) > 0 ) { 
			if ( $force_custom || (isset( $config["custom-print-logo-enabled"] ) && $config["custom-print-logo-enabled"] == "true" ) ) { 
			return ( isset( $config["upload-dir"] ) ? $config["upload-dir"] : "" )  . $config["custom-print-logo"] ; 
		}
	}
	return ROOT_PATH . "osta/img/ost-print.png";
}

function show_consent_message($custom) { 
	if ($custom["consent-message-option"] == "true" ) { ?>
		<div id="complianceouter">	
			<div id="compliance">
				<div id="icon-compliance-outer">
					<svg id="icon-compliance" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" version="1.1"  width="24" height="24" viewBox="0 0 24 24">
					   <path d="M12,1L3,5V11C3,16.55 6.84,21.74 12,23C17.16,21.74 21,16.55 21,11V5L12,1M12,5A3,3 0 0,1 15,8A3,3 0 0,1 12,11A3,3 0 0,1 9,8A3,3 0 0,1 12,5M17.13,17C15.92,18.85 14.11,20.24 12,20.92C9.89,20.24 8.08,18.85 6.87,17C6.53,16.5 6.24,16 6,15.47C6,13.82 8.71,12.47 12,12.47C15.29,12.47 18,13.79 18,15.47C17.76,16 17.47,16.5 17.13,17Z" />
					</svg>	
				</div>
				<span id="complaince-message"><?php echo $custom["consent-message"]; ?><a id="compliance-link" href="rede.ca"></a></span><a id="complaince-button-link" aria-label="dismiss"><div id="complaince-button"><span id="complaince-button-label"><?php echo $custom["consent-message-button"]; ?></span></div></a>
			</div>
		</div>
	<?php } 
}
function update_config($post) { 
	$debug = false;
	/*
	if($cfg && $cfg->updateSettings($post,$errors)) {
	    $msg=sprintf(__('Successfully updated %s.'), Format::htmlchars($page[0]));
	} elseif(!$errors['err']) {
	    $errors['err'] = sprintf('%s %s',
	        __('Unable to update settings.'),
	        __('Correct any errors below and try again.'));
	} */
	
	// nullable inputs list
	foreach( array("scroll-to-top-option", "desktop-scroll-option", "mobile-scroll-option", "ie-redirect", "custom-print-logo-enabled", "consent-message-option", "custom-css-enabled","show-errors") as $i ) { 
		if ( !isset( $post[$i] ) ) $post[$i] = "";
	}
	$config = get_config(); 
	$post = array_merge( $config, $post );
	$options = array_keys($config);
	
	if( isset( $post["ie-redirect-url"] ) ) { 
		$post["ie-redirect-url"] = preg_replace("/scp\/?.*/", '', strtolower( $post["ie-redirect-url"] ) );
	}
	if( $post["radio"] == "custom-theme-options" ) $post["theme"] = "custom";
	else if ( $post["theme"] == "custom" )  $post["theme"] = "ice";

	if ( isset( $post[ "custom-css"] ) ) { 
		$post["custom-css"] = strip_tags($post["custom-css"]);
	}
	if ( isset( $post[ "selected-logo"] ) ) { 
		$post["custom-logos"] = selectImage( $post["custom-logos"], $post[ "selected-logo"], "client" );
	}

	if ( isset( $post[ "selected-logo-scp"] ) ) { 
		$post["custom-logos"] = selectImage( $post["custom-logos"], $post[ "selected-logo-scp"], "staff" );
	}
	

	if ( isset( $post[ "selected-backdrop"] ) ) { 
		$post["custom-backdrops"] = selectImage( $post["custom-backdrops"], $post[ "selected-backdrop"], "client" );
	}

	if ( isset( $post[ "selected-backdrop-scp"] ) ) { 
		$post["custom-backdrops"] = selectImage( $post["custom-backdrops"], $post[ "selected-backdrop-scp"], "staff" );
	}


	if ( isset( $post[ "delete-logo"] ) ) { 
		$post["custom-logos"] = deleteImages( $post["custom-logos"], $post[ "delete-logo"], $config["upload-dir"]);
	}

	if ( isset( $post[ "delete-backdrop"] ) ) { 
		$post["custom-backdrops"] = deleteImages( $post["custom-backdrops"], $post[ "delete-backdrop"], $config["upload-dir"]);
	}
	
	 if ($_FILES['logo']) {
        $error = false;
        list($logo) = AttachmentFile::format($_FILES['logo']);
        if (!$logo)
            ; 
        elseif ($logo['error'])
        	$_SESSION["errors"][] = $logo['error'];
        elseif (!$new_images= uploadImage($logo, $error, $config["upload-dir"], $post["custom-logos"], 9999999 )) // 2
        	$_SESSION["errors"][] = sprintf(__('Unable to upload logo image: %s'), $error);
        else { 
        	$post["custom-logos"] = $new_images;
        }
    }

    if ($_FILES['backdrop']) {
        $error = false;
        list($backdrop) = AttachmentFile::format($_FILES['backdrop']);
        if (!$backdrop)
            ; 
        elseif ($backdrop['error'])
        	$_SESSION["errors"][] = $backdrop['error'];
        elseif (!$new_images= uploadImage($backdrop, $error, $config["upload-dir"], $post["custom-backdrops"], 9999999 )) // 2
        	$_SESSION["errors"][] = sprintf(__('Unable to upload backdrop image: %s'), $error);
        else { 
        	$post["custom-backdrops"] = $new_images;
        }
	}
	
	if ($_FILES['custom-print-logo'])  {
       $error = false;
	   $printlogo = $_FILES['custom-print-logo'];
        if (!$printlogo || !$printlogo['tmp_name'])
            ; 
        elseif ($printlogo['error'])
        	$_SESSION["errors"][] = $printlogo['error'];
        elseif (!$new_images= uploadImage($printlogo, $error, $config["upload-dir"], $post["custom-print-logo"], 9999999 )) // 2
        	$_SESSION["errors"][] = sprintf(__('Unable to upload print-logo image: %s'), $error);
        else { 
			$post["custom-print-logo"] = json_decode($new_images);
			$post["custom-print-logo"] = $post["custom-print-logo"][0]->image;
		}
    }

	for( $x=0; $x<sizeof( $options ); $x++ ) {
		if( $options[$x]  == "upload-dir" ) continue;
		if ( isset( $post[ $options[$x] ] ) ) { 
			$sql = "SELECT * FROM " . TABLE_PREFIX . "config WHERE `namespace`=\"osticketawesome\" and `key`=\"" . $options[$x] . "\"";
			$res = db_query($sql);
			if(  $row = db_fetch_array($res) ) { 
				$sql = "UPDATE " . TABLE_PREFIX . "config set `value` = ? where `namespace`='osticketawesome' and `key`='" . $options[$x]  . "'";
				if( $debug ) echo $sql . "<br/>";
				$stmt = db_prepare($sql);
				$stmt->bind_param("s", $post[ $options[$x] ] );
				$stmt->execute(); 
			}
			else { 
				$id_start =  77700;
				$sql = "SELECT max(id) as id  FROM `" . TABLE_PREFIX . "config` WHERE id > " . $id_start;
				$row = db_fetch_array(db_query($sql));
				if( $row["id"] == null ) $row["id"] = $id_start;
				$sql = "INSERT INTO `" . TABLE_PREFIX . "config` (`id`,`namespace`,`key`, `value` ) VALUES (" . ( $row["id"] + 1 ) . ",'osticketawesome' , ?, ?)";
				if( $debug ) echo $sql . "<br/>";
				$stmt = db_prepare($sql);
				$stmt->bind_param("ss",  $options[$x] , $post[ $options[$x] ] );
				$stmt->execute(); 
			}
		}

	}	
	if( $debug ) {
		echo print_r( $post, true );
		echo print_r( $_FILES, true );

		die();
	} 
}

function pdf_logo($custom) { 
	ob_start();
	?>

	<img class="logo" src="<?php echo get_print_logo( $custom )?>?<?php echo time(); ?>" alt="osTicket &mdash; <?php echo __('Customer Support System'); ?>"/> 

	<link type="text/css" rel="stylesheet" href="<?php echo $_SERVER['DOCUMENT_ROOT'] . ROOT_PATH ?>osta/opt/logo/logo-options-<?php  echo $custom["logo-options"]  ?>.css">
	<style type="text/css">
	.logo { max-height:40px; }
	</style>
	<?php
	return ob_get_clean();
}

function ie_check($config) { 
	if ( osTicket::is_ie() && $config["ie-redirect"] == "true" && $config["ie-redirect-url"] != "" ) { 
		$req = parse_url($_SERVER['REQUEST_URI']);
		$redirect = rtrim($config["ie-redirect-url"], "/") . (strpos($req['path'], "/scp/") !== false ? "/scp" : "" ) . "/";
		header("Location: " . $redirect);
		die();
	}
}

function error_handler($errno, $errstr, $errfile, $errline)
{
    if (!(error_reporting() & $errno)) {
        // This error code is not included in error_reporting, so let it fall
        // through to the standard PHP error handler
        return false;
	}
	echo "$errfile [line $errline]<br/>\n";
	echo "[$errno] $errstr<br />\n";
	echo "<b>" . __('You are seeing this error because Show PHP Errors is enabled in Theme Options. Please disable it as soon as you are finished testing.') . "</b><br /><br />";
    /* Don't execute PHP internal error handler */
    return true;
}


?>