<?php
if(!defined('OSTADMININC') || !$thisstaff || !$thisstaff->isAdmin()) die('Access Denied');

require_once $_SERVER['DOCUMENT_ROOT'] . ROOT_PATH . "/osta/php/functions.php"; 
$opt = get_config();
if ( isset( $_SESSION["errors"] ) ) { 
    foreach( $_SESSION["errors"] as $e ) { 
        ?>
        <div style="color: #721c24;background-color: #f8d7da;border-color: #f5c6cb;padding:15px;width:100%;margin-bottom:15px;">
            <?php echo $e ?>
        </div>
        <?php
    }
    unset( $_SESSION["errors"] );
}
?>  

<h2><?php echo __('Theme Options'); ?></h2>
<form action="../osta/css/themes/choose-theme.php" method="post" class="save"
    enctype="multipart/form-data">
<?php csrf_token(); ?>

<input type="hidden" name="t" value="pages" >

<ul class="clean tabs">
    <li class="active"><a href="#color-theme"><i class="icon-asterisk"></i>
        <?php echo __('Color Theme'); ?></a></li>
    <li><a href="#logo-options"><i class="icon-picture"></i>
        <?php echo __('Logo Options'); ?></a></li>
    <li><a href="#text-links"><i class="icon-picture"></i>
        <?php echo __('Custom Text and Links'); ?></a></li>
    <li><a href="#backdrops"><i class="icon-picture"></i>
        <?php echo __('Login Background'); ?></a></li>
    <li><a href="#custom-css"><i class="icon-picture"></i>
        <?php echo __('Custom CSS'); ?></a></li>			
    <li><a href="#theme-info"><i class="icon-picture"></i>
        <?php echo __('Theme Information'); ?></a></li>		
</ul>

	<!--
		
		TAB COLOR THEME
		
	-->
	
<div class="tab_content" id="color-theme">

	<table id="super" class="container" width="100%" border="0" cellspacing="0" cellpadding="0">
	<tbody>      
		<tr>
			<td>
				<div id="dark-mode-message">
					<?php echo __('NOTE: <a href="profile.php#dark-mode-tab">Dark Mode is enabled</a> for your account. Any changes you make here will be visible to Clients, and to other Agents who are not using Dark Mode, but NOT to you.'); ?>
				</div>			
				<div id="osta-settings-title">
				
<!--
	PICK A COLOR THEME
-->			
				
					<h2><?php echo __('Pick a Color Theme'); ?></h2>
				</div>
				<div id="osta-toggle">
					<input type="radio" value="choose-theme-options" id="radio1" name="radio" class="switch theme-group" data-id="choose-theme-options" <?php echo notchk( $opt, "theme", "custom")?>>
					<label for="radio1">&nbsp;</label>
				</div>

				<div id="choose-theme-options">    
					<script type="text/javascript">
						$(document).ready(function() {
						$('#styleOptions').styleSwitcher();
						});
					</script>
					<ul id="styleOptions" title="switch styling" style="list-style: none; margin: 11px 0 0 0; padding: 0;">
					<div class="theme-card">
						<div id="theme-bright" class="theme-card-lid"></div>
						<div class="theme-card-bottom">
							<div class="theme-preview">
								<li>
									<a href="javascript: void(0)" data-theme="bright" class="bright"><?php echo __('Preview'); ?></a>
								</li>
							</div>
							<div class="switch"><label class=""><input type="radio" name="theme" value="bright" <?php echo chk( $opt, "theme", "bright" )?>>  <?php echo __('Switch'); ?></label></div>
						</div>
					</div>					
					<ul id="styleOptions" title="switch styling" style="list-style: none; margin: 0; padding: 0;">
					<div class="theme-card">
						<div id="theme-soft" class="theme-card-lid"></div>
						<div class="theme-card-bottom">
							<div class="theme-preview">
								<li>
									<a href="javascript: void(0)" data-theme="soft" class="soft"><?php echo __('Preview'); ?></a>
								</li>
							</div>
							<div class="switch"><label class=""><input type="radio" name="theme" value="soft"> <?php echo chk( $opt, "theme", "soft" )?> <?php echo __('Switch'); ?></label></div>
						</div>
					</div>
					<ul id="styleOptions" title="switch styling" style="list-style: none; margin: 11px 0 0 0; padding: 0;">
					<div class="theme-card">
						<div id="theme-ice" class="theme-card-lid"></div>
						<div class="theme-card-bottom">
							<div class="theme-preview">
								<li>
									<a href="javascript: void(0)" data-theme="ice" class="ice"><?php echo __('Preview'); ?></a>
								</li>
							</div>
							<div class="switch"><label class=""><input type="radio" name="theme" value="ice" <?php echo chk( $opt, "theme", "ice" )?>> <?php echo __('Switch'); ?></label></div>
						</div>
					</div>
					<ul id="styleOptions" title="switch styling" style="list-style: none; margin: 11px 0 0 0; padding: 0;">
					<div class="theme-card">
						<div id="theme-pink" class="theme-card-lid"></div>
						<div class="theme-card-bottom">
							<div class="theme-preview">
								<li>
									<a href="javascript: void(0)" data-theme="pink" class="pink"><?php echo __('Preview'); ?></a>
								</li>
							</div>
							<div class="switch"><label class=""><input type="radio" name="theme" value="pink" <?php echo chk( $opt, "theme", "pink" )?>> <?php echo __('Switch'); ?></label></div>
						</div>
					</div>
					<ul id="styleOptions" title="switch styling" style="list-style: none; margin: 11px 0 0 0; padding: 0;">
					<div class="theme-card">
						<div id="theme-brown" class="theme-card-lid"></div>
						<div class="theme-card-bottom">
							<div class="theme-preview">
								<li>
									<a href="javascript: void(0)" data-theme="brown" class="brown"><?php echo __('Preview'); ?></a>
								</li>
							</div>
							<div class="switch"><label class=""><input type="radio" name="theme" value="brown" <?php echo chk( $opt, "theme", "brown" )?>> <?php echo __('Switch'); ?></label></div>
						</div>
					</div>
					<ul id="styleOptions" title="switch styling" style="list-style: none; margin: 11px 0 0 0; padding: 0;">
						<div class="theme-card">
							<div id="theme-blue" class="theme-card-lid"></div>
							<div class="theme-card-bottom">
								<div class="theme-preview">
									<li>
										<a href="javascript: void(0)" data-theme="blue" class="blue"><?php echo __('Preview'); ?></a>
									</li>
								</div>
								<div class="switch"><label class=""><input type="radio" name="theme" value="blue" <?php echo chk( $opt, "theme", "blue" )?>> <?php echo __('Switch'); ?></label></div>
							</div>
						</div>
					</ul>
					<ul id="styleOptions" title="switch styling" style="list-style: none; margin: 11px 0 0 0; padding: 0;">
						<div class="theme-card">
							<div id="theme-earth" class="theme-card-lid"></div>
							<div class="theme-card-bottom">
								<div class="theme-preview">
									<li>
										<a href="javascript: void(0)" data-theme="earth" class="earth"><?php echo __('Preview'); ?></a>
									</li>
								</div>
								<div class="switch"><label class=""><input type="radio" name="theme" value="earth" <?php echo chk( $opt, "theme", "earth" )?>> <?php echo __('Switch'); ?></label></div>
							</div>
						</div>
					</ul>	
					<ul id="styleOptions" title="switch styling" style="list-style: none; margin: 11px 0 0 0; padding: 0;">
						<div class="theme-card">
							<div id="theme-cool" class="theme-card-lid"></div>
							<div class="theme-card-bottom">
								<div class="theme-preview">
									<li>
										<a href="javascript: void(0)" data-theme="cool" class="cool"><?php echo __('Preview'); ?></a>
									</li>
								</div>
								<div class="switch"><label class=""><input type="radio" name="theme" value="cool" <?php echo chk( $opt, "theme", "cool" )?>> <?php echo __('Switch'); ?></label></div>
							</div>
						</div>
					</ul>						
				</div>
					
			</td>
		</tr>
	</tbody>
	</table> 
					
	<table id="custom" class="container" width="100%" border="0" cellspacing="0" cellpadding="0">
	<tbody>      
		<tr>
			<td>
<!--
	CREATE A CUSTOM THEME
-->	
		<div id="osta-settings-title">
			<h2><?php echo __('Create a Custom Theme'); ?></h2>
		</div>
		<div id="osta-toggle">
			<input type="radio" value="custom-theme-options" id="radio2" name="radio" class="switch theme-group" data-id="custom-theme-options"  <?php echo chk( $opt, "theme", "custom" , true )?>/>
			<label for="radio2">&nbsp;</label>
		</div>
				<table id="custom-theme" class="container" border="0" cellspacing="0" cellpadding="0">
					<tr>
						<td><br />
							<div id="custom-theme-options">
								<style>
									i.colorpicker-guide {
									display: unset;
									}	
									.colorpicker-element .input-group-addon i {
									border: .5px solid #b6b6b6;
									}
									.colorpicker-saturation .colorpicker-guide {
									border: unset !important;
									}
									.colorpicker-hue {
									width: 24px !important;
									}
									.input-group-addon {
									padding: 4px 6px 10px 6px;
									margin: 0px 0 0 -35px;
									font-size: unset;
									font-weight: 400;
									line-height: unset;
									/* background-color: #e9ecef; */
									/* border: .5px solid #ced4da; */
									border-radius: 0 3px 3px 0;
									position: relative;
									top: -3px;
									}
								</style>
								<link href="../osta/js/colorpicker/dist/css/bootstrap-colorpicker.css" rel="stylesheet">
								<script src="../osta/js/colorpicker/src/js/bootstrap-colorpicker.js"></script>
								<script src="<?php echo ROOT_PATH; ?>osta/js/jquery.columnizer.js"></script>
								<script>
									$(function(){
										$('#custom-column-one').columnize({width: 260});
									
									});
								</script>			
								<?php csrf_token(); ?>  
								
								
								<div class="custom-color-container">
									<?php echo __('Header Background'); ?><br />             
									<div id="cp1" class="input-group colorpicker-component" title="Using input value">
										<input type="text" class="form-control input-lg" name="headerbg" value="<?php echo $opt["headerbg"]; ?>"> 
										<span class="input-group-addon"><i></i></span>
									</div>
									<?php echo __('Header Text'); ?><br />						
									<div id="cp2" class="input-group colorpicker-component" title="Using input value">
										<input type="text" class="form-control input-lg" name="headertitlecolor" value="<?php echo $opt["headertitlecolor"]; ?>"/>
										<span class="input-group-addon"><i></i></span>
									</div>
									<?php echo __('Header Text Hover'); ?><br />						
									<div id="cp2" class="input-group colorpicker-component" title="Using input value">
										<input type="text" class="form-control input-lg" name="headertitlehovercolor" value="<?php echo $opt["headertitlehovercolor"]; ?>"/>
										<span class="input-group-addon"><i></i></span>
									</div>
								</div>
								
								
								<div class="custom-color-container">
									<?php echo __('Navigation Bar Background'); ?><br />
									<div id="cp3" class="input-group colorpicker-component" title="Using input value">
										<input type="text" class="form-control input-lg" name="navbarbg" value="<?php echo $opt["navbarbg"]; ?>"/>
										<span class="input-group-addon"><i></i></span>
									</div>
									<?php echo __('Navigation Bar Link'); ?><br />
									<div id="cp4" class="input-group colorpicker-component" title="Using input value">
										<input type="text" class="form-control input-lg" name="navbarlink" value="<?php echo $opt["navbarlink"]; ?>"/>
										<span class="input-group-addon"><i></i></span>
									</div>
									<?php echo __('Navigation Bar Link Hover'); ?><br />
									<div id="cp4" class="input-group colorpicker-component" title="Using input value">
										<input type="text" class="form-control input-lg" name="navbarlinkhover" value="<?php echo $opt["navbarlinkhover"]; ?>"/>
										<span class="input-group-addon"><i></i></span>
									</div>
								</div>
								
								
								<div class="custom-color-container">

									<?php echo __('Mobile Menu Background'); ?><br />						
									<div id="cp5" class="input-group colorpicker-component" title="Using input value">
										<input type="text" class="form-control input-lg" name="mobilemenubg" value="<?php echo $opt["mobilemenubg"]; ?>"/>
										<span class="input-group-addon"><i></i></span>
									</div>
									<?php echo __('Mobile Menu Link'); ?><br />
									<div id="cp6" class="input-group colorpicker-component" title="Using input value">
										<input type="text" class="form-control input-lg" name="mobilelinkcolor" value="<?php echo $opt["mobilelinkcolor"]; ?>"/>
										<span class="input-group-addon"><i></i></span>
									</div>
									<?php echo __('Sticky Menu Background'); ?><br />		
									<div id="cp7" class="input-group colorpicker-component" title="Using input value">
										<input type="text" class="form-control input-lg" name="stickybar" value="<?php echo $opt["stickybar"]; ?>"/>
										<span class="input-group-addon"><i></i></span>
									</div>
								</div>
								<script>
									$(function () {
									$('#cp1, #cp2, #cp3, #cp4, #cp5, #cp6, #cp7').colorpicker({
									customClass: 'colorpicker-2x',
									useAlpha: false	
									});
									});
								</script> 
							</div>
						</td>
					</tr>
				</table>
				<script>
					$('.theme-group').click(function() {
						$(".theme-group").closest("table").removeClass('highlight');
						$(this).closest("table").addClass("highlight", $(this).is(":checked"));
					});		
					$('input:radio:checked').trigger('click');
				</script>	
<!--
	HEADER BACKGROUND
-->		
				<div id="header-background-title" class="header">
					<h2><?php echo __('Header Background'); ?></h2>
				</div>
				<table id="theme-header-options" style="width:100%">
				<tbody>
					<tr>
						<td class="options-header-image">
							<table id="background-solid-image" class="container" border="0" cellspacing="0" cellpadding="0">
								<tr>
									<td class="left"> 
									  <div class="title"><?php echo __('Use Background Image'); ?></div>
									  <div class="image">
										<!--<?php				
										$file_name = ROOT_DIR ."osta/inc/default-logo.html";
										echo file_get_contents($file_name);
										?>-->
									  </div>
									</td>
									<td class="right"> 
										<div id="osta-toggle">
											<input id="radio10" class="switch header-options-group" type="radio" name="header-options" value="header-background-image" <?php echo chk( $opt, "header-options", "header-background-image" )?>>
											<label for="radio10">&nbsp;</label>	
										</div>	
									</td>
								</tr>
							</table>
						</td>	
						<td class="options-header-color">
							<table id="background-solid-color" class="container" border="0" cellspacing="0" cellpadding="0">
								<tr>
									<td class="left"> 
									  <div class="title"><?php echo __('Use Solid Color'); ?></div>
									  <div class="image">
										<!--<?php				
										$file_name = ROOT_DIR ."osta/inc/default-logo.html";
										echo file_get_contents($file_name);
										?>-->
									  </div>
									</td>
									<td class="right"> 
										<div id="osta-toggle">
											<input id="radio11" class="switch header-options-group" type="radio" name="header-options" value="header-solid-color" <?php echo chk( $opt, "header-options", "header-solid-color" )?>>
											<label for="radio11">&nbsp;</label>	
										</div>	
									</td>
								</tr>
							</table>
						</td>
					</tr>
				</tbody>
				</table>	
				<script>
					$('.header-options-group').click(function() {
						$(".header-options-group").closest("table").removeClass('highlight');
						$(this).closest("table").addClass("highlight", $(this).is(":checked"));
					});		
					$('input:radio:checked').trigger('click');
				</script>
				
				
<!--
	DARK MODE
-->		
				<div id="dark-mode-option">
					<div class="header">
						<h2><?php echo __('Dark Mode'); ?></h2>
					</div>
					<div class="text">				
					NOTE: Dark Mode is a highly experimental feature which may render many aspects of osTicket unusable. But if you really
					want to try Dark Mode you can <a href="profile.php#dark-mode">enable it for your profile</a>.
					</div>
				</div>
				
				
				</td>
			</tr>
		</tbody>
	</table> 	

</div>

	<!--

		TAB LOGO OPTIONS
		
	-->

<div class="hidden tab_content" id="logo-options">
			
	<table id="custom-logo-container" border="0" cellspacing="0" cellpadding="0">
	  <tbody>
		<tr>
		  <td id="logo-type">
			<form action="../osta/opt/logo/logo-options.php" method="post">
			<?php csrf_token(); ?>  	  
<!--
	LOGO OPTIONS
-->			
			<h2><?php echo __('Logo Options'); ?></h2><br>
			<table id="default-logo" class="container" border="0" cellspacing="0" cellpadding="0">
				<tr>
					<td class="left"> 
					  <div class="title"><?php echo __('osTicket Awesome Logo'); ?></div>
					  <div class="image">
						<?php				
						$file_name = ROOT_DIR ."osta/inc/default-logo.html";
						echo file_get_contents($file_name);
						?>
					  </div>
					</td>
					<td class="right"> 
						<div id="osta-toggle">
							<input id="radio4" class="switch logo-group" type="radio" name="logo-options" value="default" <?php echo chk( $opt, "logo-options", "default" )?>>
							<label for="radio4">&nbsp;</label>	
						</div>	
					</td>
				</tr>
			</table>
			<table id="custom-text" class="container" border="0" cellspacing="0" cellpadding="0">
				<tr>
					<td class="left"> 
					  <div class="title"><?php echo __('Custom Text'); ?></div>
					  <div class="image">
						<div id="header-text">
							<div id="header-title">
								<?php echo $custom["title"]; ?>   
							</div>
							<div id="header-subtitle">
								<?php echo $custom["subtitle"]; ?>      
							</div>
						</div>	
					  </div>
					</td>
					<td class="right"> 
						<div id="osta-toggle">			
							<input id="radio5" class="switch logo-group" type="radio" name="logo-options" value="text" <?php echo chk( $opt, "logo-options", "text" )?>>
							<label for="radio5">&nbsp;</label>
						</div>  
					</td>
				</tr>
			</table>
			<table id="custom-logo" class="container" border="0" cellspacing="0" cellpadding="0">
				<tr>
					<td class="left"> 
					  <div class="title"><?php echo __('Custom Logo'); ?></div>
					  <div class="image">
				<img src="<?php echo get_logo( $opt, "staff" )?>?<?php echo strtotime($cfg->lastModified('staff_logo_id')); ?>" alt="osTicket &mdash; <?php echo __('Customer Support System'); ?>"/> 					  
					  </div>
					</td>
					<td class="right"> 
						<div id="osta-toggle">				
							  <input id="radio6" class="switch logo-group" type="radio" name="logo-options" value="image" <?php echo chk( $opt, "logo-options", "image" )?>>
							<label for="radio6">&nbsp;</label>		
						</div>  
					</td>
				</tr>
			</table>  
			<script>// Web Logo
				$('.logo-group').click(function() {
					$(".logo-group").each(function() {
						$(this).closest("table").toggleClass("highlight", $(this).is(":checked"));
					});
				});
				$(document).ready(function(){
				  $('#logo-type input[type="radio"]').click(function(){
                    if ( $(this).closest(".custom-logo-group").length ) return;
					var demovalue = $(this).val(); 
					$("table.custom-logo-group").hide();
					$("#show-"+demovalue).show();
				  });
				$('#logo-type input:radio:checked').trigger('click');  
				});
			</script>
			</form>	


			<table id="print-logo" class="container<?php echo $opt["custom-print-logo-enabled"] == "true" ? " highlight" : ""?>" border="0" cellspacing="0" cellpadding="0">
			<tbody>
				<tr>
					<td class="left"> 
					  <div class="title"><?php echo __('Custom Print Logo'); ?></div>
					  <div class="print-logo">
						<img src="<?php echo get_print_logo( $opt, true)?>?<?php echo strtotime($cfg->lastModified('staff_logo_id')); ?>" alt="osTicket &mdash; <?php echo __('Customer Support System'); ?>"/> 					  
						  </div>
					</td>
					<td class="right">
							
						<div id="osta-toggle">				
							<input type="checkbox" id="checkprintimage" name="custom-print-logo-enabled" value="true" class="switch nullable" <?php echo chk( $opt, "custom-print-logo-enabled", "true" )?> />
							<label for="checkprintimage">&nbsp;</label>	
						</div>

					</td>
				</tr>
				<tr>
					<td class="print-logo-upload" rel="checkprintimagex"> 
						<input type="file" name="custom-print-logo" size="30" value="" />
						<font class="error"><br/><?php echo $errors['custom-print-logo']; ?></font>	 
					</td>
				</tr>
			</tbody>
		</table>	


		<script>// Print Logo
			$('#checkprintimage').click(function() {
				$("#checkprintimage").each(function() {
					$(this).closest("table").toggleClass("highlight", $(this).is(":checked"));
				});
			});
		</script>
		
			
		  </td>
		  <td id="custom-logo-column-outer">
<!--
	CUSTOM LOGO
-->	
				<table id="custom-logo-column-inner">
				  <tbody>
					<tr>
					  <td id="custom-logo-column-inner-title">
						<h2><?php echo __('Use a Custom Logo'); ?>&nbsp;&nbsp;<span class="custom-logo-link"><a href="https://osticketawesome.com/logo"><?php echo __('More information'); ?></a></span></h2>		
						<br>
					  </td>
					</tr>
					<tr>
					  <td id="custom-logo-column" class="images">
						<table id="show-image" class="custom-logo-group" width="100%" border="0" cellspacing="0" cellpadding="0">
						<tbody>      
							<tr>
								<td class="indent"> 

									<?php 
										ob_start();
										$client_logo = false;
										$staff_logo = false;
											foreach( json_decode( $opt["custom-logos"], true )  as $k=>$v ) { 
												if ( $v["client"] == true ) $client_logo = true;
												if ( $v["staff"] == true ) $staff_logo = true;
													?>
													<br/>
												<div class="logo-delete-icon-container">
													<input type="radio" name="selected-logo"
														   style="margin-left: 1em" value="<?php
													echo $k; ?>" <?php
													if ($v["client"] == true )
														echo 'checked="checked"'; ?>/>
													<label>
													<input type="radio" name="selected-logo-scp"
															   style="margin-left: 1em" value="<?php
													echo $k; ?>" <?php
													if ($v["staff"] == true)
														echo 'checked="checked"'; ?>/>
													<div class="logo-image-container">
														<img src="<?php echo $opt["upload-dir"] . $v["image"] ?>"
															 alt="Custom Logo" valign="middle"
															 style="box-shadow: 0 0 0.5em rgba(0,0,0,0.5);
																	vertical-align: middle;"/>
													</div>
													</label>
													<?php if (!$v["client"] && !$v["staff"] ) { ?>
													
													
														<label class="checkbox inline">
															<div class="checkbox-container">
																<input type="checkbox" name="delete-logo[]" value="<?php
																echo $k; ?>"/><i class="icon-trash"></i>
															</div>
														</label>
												</div>
											
												<?php } 
											}
										$custom_logos = ob_get_clean();
										?>
									<input type="radio" name="selected-logo"
										   style="margin-left: 1em" value="-1" <?php
									if ($client_logo == false)
										echo 'checked="checked"'; ?>/>
									<label>
									<input type="radio" name="selected-logo-scp"
											   style="margin-left: 1em" value="-1" <?php
									if ($staff_logo == false)
										echo 'checked="checked"'; ?>/>

									<div class="logo-image-container">
										<img src="../osta/img/ost-logo.png"
											 alt="Default Logo" valign="middle"
											 style="box-shadow: 0 0 0.5em rgba(0,0,0,0.5);
													margin: 0.5em; height: 5em;
													vertical-align: middle"/>
									</div>
									</label>
								
									<?php
									echo $custom_logos;
									
									?>
								</td>
							</tr>				
							<tr>
								<td id="logo-upload" class="indent">
									<div class="title"><?php echo __('Upload a new logo'); ?></div>
								</td>
							</tr>
							<tr>
								<td class="indent">
									<input type="file" name="logo[]" size="30" value="" />
									<font class="error"><br/><?php echo $errors['logo']; ?></font>	 
								</td>
							</tr>				
						</tbody>
						</table>
					  </td>
					</tr>
				  </tbody>
				</table>

		  </td>
		</tr>
		<tr>
			<td id="print-logo-placeholder">
				<!--intentionally empty-->
			</td>
		</tr>
	  </tbody>
	</table>		
	
</div>

	<!--

		TAB CUSTOM TEXT AND LINKS
		
	-->
	
<div class="hidden tab_content" id="text-links">
	<table id="custom-text-and-links-outter" class="container" width="100%" border="0" cellspacing="0" cellpadding="0">
		<tbody>      
			<tr>
				<td class="custom-text-and-links-col1">
					<table id="custom-text-and-links" width="100%" border="0" cellspacing="0" cellpadding="0">
					<tbody>      
						<tr>
				<!--
					CUSTOM TEXT AND LINKS
				-->			
							<td><h2><?php echo __('Custom Text and Links'); ?></h2></td>
						</tr>
						<tr>
							<td class="indent"> 
										
								<div id="row-one">
									
									<div class="custom-text-container">
										<div class="custom-text-title"><?php echo __('Header Title'); ?></div>
										<div class="custom-text-example"><img src="../osta/img/custom-text/01.png"></div>
										<div class="custom-text-input"><input type=text name="title" value="<?php echo $custom["title"]; ?>"></div>
									</div>
									<div class="custom-text-container">
										<div class="custom-text-title"><?php echo __('Header Subtitle'); ?></div>
										<div class="custom-text-example"><img src="../osta/img/custom-text/02.png"></div>
										<div class="custom-text-input"><input type=text name="subtitle" value="<?php echo $custom["subtitle"]; ?>"></div>
									</div>
								</div>
								<div id="row-two">
									
									<div class="custom-text-container">
										<div class="custom-text-title"><?php echo __('Mobile Text'); ?></div>
										<div class="custom-text-example"><img src="../osta/img/custom-text/03.png"></div>
										<div class="custom-text-input"><input type=text name="mobile-text" value="<?php echo $custom["mobile-text"]; ?>"></div>
									</div>
									<div class="custom-text-container">
										<div class="custom-text-title"><?php echo __('Mobile Link'); ?></div>
										<div class="custom-text-example"><img src="../osta/img/custom-text/04.png"></div>
										<div class="custom-text-input"><input type=text name="mobile-link" value="<?php echo $custom["mobile-link"]; ?>"></div>
									</div>
								</div>

							</td>
							</tr>
						</tbody>
					</table>
				</td>
				<td class="custom-text-and-links-col2">
									<table style="width:100%" class="ie-redirect-table">
										<tbody>
											<tr>				
												<td class="ie-redirect-title">
													<img id="ie-icon" src="../osta/img/internet-explorer.png"><?php echo __('Internet Explorer Redirection'); ?>
												</td>
												<td id="ie" class="toggles">
													<table class="container" border="0" cellspacing="0" cellpadding="0">
														<tr>
															<td> 
																<input type="radio"  id="radio89" name="ie-redirect" value="true" class="switch nullable" <?php echo chk( $opt, "ie-redirect", "true" )?> />
																<label for="radio89">&nbsp;</label>
															</td>
														</tr>
													</table>
												</td>
											</tr>
											<tr>				
												<td class="option-ie-redirect-url" colspan="2" rel="radio89" <?php if($custom["ie-redirect"] != "true") echo "style='display:none;'"?>>
													<div id="consent-message-label-container">
														<div class="consent-message-label">
															<?php echo __('URL'); ?>
														</div>
														<div class="consent-message-text-input">
															<input  placeholder="e.g. www.yoursite.com/support-old/" type=text name="ie-redirect-url" value="<?php echo $custom["ie-redirect-url"]; ?>"/>
														</div>
													</div>

												</td>
											</tr>
										</tbody>
									</table>
									
									<table style="width:100%" class="consent-message-table">
										<tbody>
											<tr>				
												<td class="consent-message-title">
													<img id="cookies" src="../osta/img/cookie.png"><?php echo __('Cookie Consent Message'); ?>
												</td>
												<td id="consent" class="toggles">
													<table class="container" border="0" cellspacing="0" cellpadding="0">
														<tr>
															<td> 
																<input type="radio"  id="radio73" name="consent-message-option" value="true" class="switch nullable" <?php echo chk( $opt, "consent-message-option", "true" )?> />
																<label for="radio73">&nbsp;</label>
															</td>
														</tr>
													</table>
												</td>
											</tr>
											<tr>				
												<td rel="radio73" colspan="2" <?php if($custom["consent-message-option"] != "true") echo "style='display:none !important;'"?>>
													<div id="consent-message-text-container">
														<div class="consent-message-label">
															<?php echo __('Message'); ?>
														</div>
														<div class="consent-message-text-input">
														<textarea name="consent-message" rows="3" cols="10" wrap="soft"><?php echo $custom["consent-message"]; ?></textarea>
														</div>
														<div class="consent-message-label">
															<?php echo __('Button'); ?>
														</div>
													</div>
													<div class="consent-message-button-text">
														<div class="consent-message-button-text-input"><input type=text name="consent-message-button" value="<?php echo $custom["consent-message-button"]; ?>"></div>
													</div>
												</td>
											</tr>
										</tbody>
									</table>									
									
									
									
	
									<table style="width:100%" class="scroll-table">
										<tbody>
											<tr>				
												<td class="scroll-title">
													<img id="scroll-icon" src="../osta/img/scroll.png"><?php echo __('Scroll to Top Icon'); ?>
												</td>
												<td id="scroll" class="toggles">
													<table class="container" border="0" cellspacing="0" cellpadding="0">
														<tr>
															<td> 
																<input type="radio"  id="radio55" name="scroll-to-top-option" value="true" class="switch nullable" <?php echo chk( $opt, "scroll-to-top-option", "true" )?> />
																<label for="radio55">&nbsp;</label>
															</td>
														</tr>
													</table>
												</td>
											</tr>
											<tr>				
												<td rel="radio55" colspan="2" <?php if($custom["scroll-to-top-option"] != "true") echo "style='display:none !important;'"?>>



											<table style="width:100%" id="scroll-toggles">
												<tr>
													<td id="scroll-img-background" colspan="4">
														&nbsp;
													</td>
												</tr>
												<tr>
													<td id="scroll-spacer" colspan="4">
														&nbsp;
													</td>
												</tr>
												<tr>				
													<td class="option-desktop-scroll">
														<?php echo __('Desktop'); ?>
													</td>
													<td class="toggles">
														<table class="container" border="0" cellspacing="0" cellpadding="0">
															<tr>
																<td> 
																	<input type="radio"  id="radio99" name="desktop-scroll-option" value="true" class="switch nullable" <?php echo chk( $opt, "desktop-scroll-option", "true" )?> />
																	<label for="radio99">&nbsp;</label>
																</td>
															</tr>
														</table>
													</td>
													<td class="option-mobile-scroll">
														<?php echo __('Mobile'); ?>
													</td>
													<td class="toggles">
														<table class="container" border="0" cellspacing="0" cellpadding="0">
															<tr>
																<td> 
																	<input type="radio"  id="radio98" name="mobile-scroll-option" value="true" class="switch nullable" <?php echo chk( $opt, "mobile-scroll-option", "true" )?> />
																	<label for="radio98">&nbsp;</label>
																</td>
															</tr>
														</table>
													</td>
												</tr>
											</table>



												</td>
											</tr>
										</tbody>
									</table>																			
		
											
	


											

									
									
									
				</td>				
			</tr>
		</tbody>
	</table>
</div>

	<!--

		TAB BACKDROPS
		
	-->
	
<div class="hidden tab_content" id="backdrops">
<!--
	SYSTEM DEFAULT BACKDROP
-->	
	<h2><?php echo __('Default Background Image'); ?></h2>

	<table id="system-backdrops-options" style="width:100%">
		<tbody>
			<tr>
				<td>
				
					<table style="width:100%">
						<tbody>
							<tr>				
								<td class="option-solid">
									<?php echo __('Use Solid Color'); ?>
								</td>
								<td class="toggles">
									<table class="container" border="0" cellspacing="0" cellpadding="0">
										<tr>
											<td> 
											  <input type="radio" id="radio9" name="backdrop-options" value="solid-color" class="switch backdrop-group" <?php echo chk( $opt, "backdrop-options", "solid-color" )?> />
											  <label for="radio9">&nbsp;</label>
											</td>
										</tr>
									</table>
								</td>
								<td class="options-backdrop">
									<?php echo __('Use Background Image'); ?>
								</td>
								<td class="toggles">
									<table class="container" border="0" cellspacing="0" cellpadding="0">
										<tr>
											<td> 
											  <input type="radio" id="radio8" name="backdrop-options" value="backdrop" class="switch backdrop-group" <?php echo chk( $opt, "backdrop-options", "backdrop" )?> />
											  <label for="radio8">&nbsp;</label>
											</td>
										</tr>
									</table>
								</td>
								<td id="spacer">
									&nbsp;
								</td>
							</tr>
						</tbody>
					</table>				

				</td>
			</tr>
		</tbody>
	</table>
	<table id="show-backdrop" class="backdrop" border="0" cellspacing="0" cellpadding="2">
		<tbody>
			<tr>
				<td>
					<table id="system-backdrops" style="width:100%">
						<tbody>
							<tr>
								<td>

								  <label>
									  <input type="radio" name="backdrop" value="01.png" class="switch backdrop-i" <?php echo chk( $opt, "backdrop", "01.png" )?>> 
									  <div id="one" class="outer">
										<div class="inner">
											<div class="top"></div>
										</div>
										<div class="select"></div>
									  </div>		
								  </label>
								  
								  <label>
									  <input type="radio" name="backdrop" value="02.png" <?php echo chk( $opt, "backdrop", "02.png" )?>> 
									  <div id="two" class="outer">
										<div class="inner">
											<div class="top"></div>
										</div>
										<div class="select"></div>
									  </div>	
								  </label>
								  
								  <label>
									  <input type="radio" name="backdrop" value="03.png" <?php echo chk( $opt, "backdrop", "03.png" )?>> 
									  <div id="three" class="outer">
										<div class="inner">
											<div class="top"></div>
										</div>
										<div class="select"></div>
									  </div>	
								  </label>
								  
								  <label>
									  <input type="radio" name="backdrop" value="04.png" <?php echo chk( $opt, "backdrop", "04.png" )?>>  
									  <div id="four" class="outer">
										<div class="inner">
											<div class="top"></div>
										</div>
										<div class="select"></div>
									  </div>
								  </label>
								  
								  <label>
									  <input type="radio" name="backdrop" value="05.jpg" <?php echo chk( $opt, "backdrop", "05.jpg" )?>>  
									  <div id="five" class="outer">
										<div class="inner">
											<div class="top"></div>
										</div>
										<div class="select"></div>
									  </div>
								  </label>
								  
								  <label>
									  <input type="radio" name="backdrop" value="06.jpg" <?php echo chk( $opt, "backdrop", "06.jpg" )?>>  
									  <div id="six" class="outer">
										<div class="inner">
											<div class="top"></div>
										</div>
										<div class="select"></div>
									  </div>
								  </label>
								  
								  <label>
									  <input type="radio" name="backdrop" value="07.jpg" <?php echo chk( $opt, "backdrop", "07.jpg" )?>>  
									  <div id="seven" class="outer">
										<div class="inner">
											<div class="top"></div>
										</div>
										<div class="select"></div>
									  </div>
								  </label>	

								  <label>
									  <input type="radio" name="backdrop" value="08.jpg" <?php echo chk( $opt, "backdrop", "08.jpg" )?>>  
									  <div id="eight" class="outer">
										<div class="inner">
											<div class="top"></div>
										</div>
										<div class="select"></div>
									  </div>
								  </label>
								  
								  <label>
									  <input type="radio" name="backdrop" value="09.jpg" <?php echo chk( $opt, "backdrop", "09.jpg" )?>>  
									  <div id="nine" class="outer">
										<div class="inner">
											<div class="top"></div>
										</div>
										<div class="select"></div>
									  </div>
								  </label>
								  
								  <label>
									  <input type="radio" name="backdrop" value="10.jpg" <?php echo chk( $opt, "backdrop", "10.jpg" )?>>  
									  <div id="ten" class="outer">
										<div class="inner">
											<div class="top"></div>
										</div>
										<div class="select"></div>
									  </div>
								  </label>
								  
								  <label>
									  <input type="radio" name="backdrop" value="11.jpg" <?php echo chk( $opt, "backdrop", "11.jpg" )?>>  
									  <div id="eleven" class="outer">
										<div class="inner">
											<div class="top"></div>
										</div>
										<div class="select"></div>
									  </div>
								  </label>
								  
								  <label>
									  <input type="radio" name="backdrop" value="12.jpg" <?php echo chk( $opt, "backdrop", "12.jpg" )?>>  
									  <div id="twelve" class="outer">
										<div class="inner">
											<div class="top"></div>
										</div>
										<div class="select"></div>
									  </div>
								  </label>							  
								</td>
							</tr>
						</tbody>
					</table>
<!--
	USE A CUSTOM BACKDROP
-->
<?php
	$backdrops = json_decode( $opt["custom-backdrops"], true )  ;
	if (count((array)$backdrops ) ) { 

	?>
					<table id="custom-backdrops" style="width:100%">
						<tbody>
							<tr>
								<th>
									<h2><?php echo __('Use a Custom Background Image');
									?></h2>
								</th>
							</tr>
							<tr>
								<td id="custom-backdrop-container">
									<?php
										foreach( json_decode( $opt["custom-backdrops"], true )  as $k=>$v ) { 
											$custom_url = "../../uploads/" . $v["image"];
									?>		
									<div class="backdrop-delete-icon-container">			
										<label>
										  <input type="radio" name="backdrop"
											style="margin-left: 1em" value="<?php
											echo $custom_url; ?>" 
											<?php echo chk( $opt, "backdrop", $custom_url )?>/>
										  <div class="custom-backdrop-outer">
											<div class="custom-backdrop-inner" style="background-image: url(<?php echo $opt["upload-dir"] . $v["image"]  ?>);" >
												<div class="top"></div>
											</div>
											<div class="select"></div>
										  </div>
										</label>
										<label>									
											<div class="checkbox-container">
												<?php if ($opt["backdrop"] != $custom_url ) { ?>
													<input type="checkbox" name="delete-backdrop[]" value="<?php
													echo $k; ?>"/><i class="icon-trash"></i>
												<?php } ?>
											</div>
										</label>
									</div>
									<?php } ?>
								</td>
							</tr>
						</tbody>
					</table>
	<?php
	}
	?>
<!--
	UPLOAD A NEW BACKDROP
-->				
					<table id="custom-backdrop-upload" style="width:100%">		
						<tbody>
							<tr>
								<th>
									<h2><?php echo __('Upload a New Background Image'); ?></h2>
								</th>
							</tr>
							<tr>
								<td id="upload-backdrop-container">    
									<input type="file" name="backdrop[]" size="30" value="" />
									<font class="error"><br/><?php echo $errors['backdrop']; ?></font>
								</td>
							</tr>
						</tbody>
					</table>								
				</td>
			</tr>
		</tbody>
	</table>
	<script>
	$('.backdrop-group').click(function() {
		$(".backdrop-group").each(function() {
			$(this).closest("table").toggleClass("highlight", $(this).is(":checked"));
		});
	});
	$(document).ready(function(){
	$('#system-backdrops-options input[type="radio"]').click(function(){
		var demovalue = $(this).val(); 
		$("table.backdrop").hide();
		$("#show-"+demovalue).show();
	  });
	  $(document).on("click","input[type='radio'].nullable",function(e) {
		  let checked =  $(e.target).attr('checked') == "checked";
		  $(e.target).prop("checked", !checked );
		  $(e.target).attr('checked', checked ? false  : "checked" )
		 // $('body').find('[rel=' + $(e.target).attr("id") + ']').css('display', !checked ? 'table-cell': 'none')
		  $('body').find('[rel=' + $(e.target).attr("id") + ']').attr('style', !checked ? 'display:table-cell;': 'display:none !important;')
	  })

	
	$('#system-backdrops-options input:radio:checked').trigger('click');  
	});	
	</script>
</div>

	<!--

		ADD CUSTOM CSS
		
	-->
	
<div class="hidden tab_content" id="custom-css">
    <table id="css-container" border="0" cellspacing="0" cellpadding="0">
        <tbody>
            <tr>
                <td class="css-column">
                    <table id="theme-css">
                        <tbody>
                            <tr>
                                <td>
									
									<div id="osta-settings-title">
<!--
	ADD CUSTOM CSS 
-->			
										<h2><?php echo __('Add Custom CSS'); ?></h2>
									</div>
									
									<div id="osta-toggle">
										<input type="checkbox" value="true" id="custom-css-toggle" name="custom-css-enabled" class="switch nullable" <?php echo chk( $opt, "custom-css-enabled", "true")?>>
										<label for="custom-css-toggle">&nbsp;</label>
									</div>

                                </td>
                            </tr>
                            <tr>
                                <td class="css container<?php echo $opt["custom-css-enabled"] == "true" ? " highlight" : ""?>">
								
								<link rel="stylesheet" type="text/css" href="../osta/js/codemirror/css/codemirror.css">
								<link rel="stylesheet" href="../osta/js/codemirror/css/lucario.css">
								<style>
								#custom-css-container {
									width: 95%;
									height: 500px !important;
									margin: 24px auto 50px auto;
								}
								#css-container {
									width: 100%;
								}
								#theme-css {
									width: 100%;
								}
								.CodeMirror {
									max-width: 1102px;
									height: 500px;
									padding: 10px;
									border: 1px solid #ddd;
									border-radius: 5px;	
								}
								td.css.container {
									opacity: 0.7;
								}
								td.css.container.highlight {
									opacity: 1;
								}
								.CodeMirror-sizer {
									position: relative;
									border-right: 0;
								}
								</style>

								<div id="custom-css-container">
									<textarea class="codemirror-textarea" name="custom-css"><?php echo $opt["custom-css"]?></textarea>
								</div>

								<script type="text/javascript" src="../osta/js/codemirror/js/codemirror.js"></script>
								<script src="../osta/js/codemirror/js/css.js"></script>
								<script src="../osta/js/codemirror/plugin/autorefresh.js"></script>

								<script>
								$(document).ready(function() {
									var code = $(".codemirror-textarea")[0]; 
									var editor = CodeMirror.fromTextArea(code, {
									theme: "lucario",
									autoRefresh: true,
									lineWrapping: true,
									scrollbarStyle: "native"
									});
								});
								</script>
								<script>// Custom CSS
								$('#custom-css-toggle').click(function() {
									$("#custom-css-toggle").each(function() {
										$("td.css.container").toggleClass("highlight", $(this).is(":checked"));
									});
								});
								</script>
					

                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
        </tbody>
    </table>
</div>

	<!--

		TAB THEME INFO
		
	-->
	
<div class="hidden tab_content" id="theme-info">
    <table id="support-info-container" border="0" cellspacing="0" cellpadding="0">
        <tbody>
            <tr>
                <td class="info-column">
                    <table id="theme3">
                        <tbody>
						
                            <tr>
                                <td>
<!--
	THEME INFORMATION
-->								
                                    <h2><?php echo __('Theme Information'); ?></h2>
                                </td>
                            </tr>

                            <tr>
                                <td>
                                    <br />
                                    <span class="code-green osticket">osTicket 
                                    <?php echo sprintf("%s", THIS_VERSION); ?></span>	
                                    <span class="forslash">//</span>		
                                    <span class="code-green awesome"><?php $themev = ROOT_DIR ."osta/version"; echo file_get_contents($themev); ?></span><br /><br />
									
                                    <?php echo __('The current version'); ?>:
									<span class="current-version"><?php $currentv = "https://osticketawesome.com/release/current-version.txt";
                                        echo file_get_contents($currentv); ?></span>
										
									<div id="version-msg"><a href='https://osticketawesome.com/downloads/'><?php echo __('Download'); ?> <?php $currentv = "https://osticketawesome.com/release/current-version";
                                        echo file_get_contents($currentv); ?></a></div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
				<td rowspan="2" id="brace"><img src="../osta/svg/bracket.svg"></td>
                <td rowspan="2" id="get-support-container">
                    <table id="get-support" width="100%" border="0" cellspacing="0" cellpadding="0">
                        <tr>
                            <td>
                                <button id="copy-clipboard-btn" class="btn" data-clipboard-text="osTicket <?php echo sprintf("%s", THIS_VERSION); ?>  //  <?php $themev = ROOT_DIR ."osta/version"; echo file_get_contents($themev);?><?php echo "\r\n"; ?>PHP <?php echo phpversion(); ?>  //  MySQL <?php echo db_version(); ?>  //  <?php echo $_SERVER['SERVER_SOFTWARE']; ?> web server">
                                <i class="icon-copy-clipboard"></i><?php echo __('Copy to Clipboard'); ?>
                                </button>
                                <script src="../osta/js/clipboard.min.js"></script>	
                                <script>
                                    var clip = new Clipboard('.btn');
                                </script>				
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <a id="osta-settings-support" href="https://osticketawesome.com/forums/"><i class="icon-copy-support"></i><?php echo __('Get Support'); ?></a>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td class="info-column">
                    <table id="theme4">
                        <tbody>
                            <tr>
                                <td colspan="2">
<!--
	YOUR SOFTWARE ENVIRONMENT
-->								
                                    <h2><?php echo __('Your Software Environment'); ?></h2>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <br />		
                                    <span class="code-green">PHP <?php echo phpversion(); ?></span>
                                    <span class="forslash">//</span>	
                                    <span class="code-green">MySQL <?php echo db_version(); ?></span>
                                    <span class="forslash">//</span>	
                                    <span class="code-green"><?php echo $_SERVER['SERVER_SOFTWARE']; ?> web server</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
            <tr>
                <td colspan="3">
                    <table id="theme6">
                        <tbody>
                            <tr>
                                <td colspan="2">
<!--
	YOUR OLD OSTICKET
-->
                                    <h2><?php echo __('Your Old osTicket'); ?></h2>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <br>
                                    <?php echo __('If you are having trouble, you should try using
									old osTicket to see if the problem persists.'); ?><br><br>
									
									<script>
									function loadClientPage() {
										location.href = window.location.href.split('#')[0].replace('/scp/theme.php', '/osta/old/');
									}
									function loadStaffPage() {
										location.href = window.location.href.split('#')[0].replace('/scp/theme.php', '/osta/old/scp/');
									}
									</script>
									 
									<button id="old-client" type="button" onclick="loadClientPage();"><i class="icon-client-portal"></i><?php echo __('Client Portal'); ?></button>
									<button id="old-staff" type="button" onclick="loadStaffPage();"><i class="icon-staff-panel"></i><?php echo __('Staff Panel'); ?></button>								
																		
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>	
            <tr>
                <td colspan="3">
                    <table id="theme7">
                        <tbody>
                            <tr>
                                <td colspan="2">
<!--
	KEYBOARD SHORTCUTS
-->
                                    <h2><?php echo __('Keyboard Shotcuts'); ?></h2>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <br>
                                    <?php echo __('Use the link below to see a list of keyboard shortcuts are are available in the Staff Panel.'); ?><br><br>
	
									<script>
										function openKeyboardModal() {
											$('#help').modal('toggle');
										}
									</script>		
	
									<button id="keyboard-shortcuts" type="button" onclick="openKeyboardModal();"><i class="icon-keyboard-shortcuts"></i><?php echo __('Keyboard Shotcuts'); ?></button>								

                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>				
            <tr>
                <td colspan="3">
                    <table id="theme5">
                        <tbody>
                            <tr>
                                <td colspan="2">
<!--
	ACKNOWLEDGMENTS
-->
                                    <h2><?php echo __('Acknowledgements'); ?></h2>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <br>
                                    <?php echo __('The following packages have been integrated into osTicket Awesome'); ?>:<br><br>
                                    <a href="https://github.com/farbelous/bootstrap-colorpicker/">Bootstrap Colorpicker 3</a><span class="forslash">//</span>
                                    <a href="https://codemirror.net/">CodeMirror</a><span class="forslash">//</span>
									<a href="https://github.com/camsjams/jquery-style-switcher">jQuery Style Switcher</a><span class="forslash">//</span>
                                    <a href="https://www.berriart.com/sidr/">SIDR jQuery side menu</a><span class="forslash"></span>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <div id="thank-you"><?php echo __('Thank you for supporting osTicket Awesome'); ?></div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
        </tbody>
    </table>

<div id="show-errors">

	<table style="width:100%" class="show-errors-table">
		<tbody>
			<tr>				
				<td class="show-php-errors">
					<div id="show-errors-title"><i class="icon-bug"></i><?php echo __('Show PHP Errors'); ?></div>
				</td>
				<td id="ie" class="toggles">
					<table class="container" border="0" cellspacing="0" cellpadding="0">
						<tr>
							<td> 
								<input type="radio"  id="radio91" name="show-errors" value="true" class="switch nullable" <?php echo chk( $opt, "show-errors", "true" )?> />
								<label for="radio91">&nbsp;</label>
							</td>
						</tr>
					</table>
				</td>
			</tr>
			<tr>				
				<td colspan="2" class="show-php-errors-text">
					<?php echo __('If you are trying to track down an osTicket issue it may help to turn this on temporarily.'); ?>
				</td>
			</tr>			
		</tbody>
	</table>



</div>
	
</div>
</form>
<p style="text-align:center;">
    <input class="button" type="submit" name="submit-button" value="<?php
    echo __('Save Changes'); ?>">
    <input class="button" type="reset" name="reset" value="<?php
    echo __('Reset Changes'); ?>">
</p>
</form>
<div style="display:none;" class="dialog" id="confirm-action">
    <h3><?php echo __('Please Confirm'); ?></h3>
    <a class="close" href=""><i class="icon-remove-circle"></i></a>
    <hr/>
    <p class="confirm-action" id="delete-confirm">
        <font color="red"><strong><?php echo sprintf(
        __('Are you sure you want to DELETE %s?'),
        _N('selected image', 'selected images', 2)); ?></strong></font>
        <br/><br/><?php echo __('Deleted data CANNOT be recovered.'); ?>
    </p>
    <div><?php echo __('Please confirm to continue.'); ?></div>
    <hr style="margin-top:1em"/>
    <p class="full-width">
        <span class="buttons pull-left">
            <input type="button" value="<?php echo __('No, Cancel'); ?>" class="close">
        </span>
        <span class="buttons pull-right">
            <input type="button" value="<?php echo __('Yes, Do it!'); ?>" class="confirm">
        </span>
     </p>
    <div class="clear"></div>
</div>
<script type="text/javascript">
$(function() {
    $('#save input:submit.button').bind('click', function(e) {
        var formObj = $('#save');
        if ($('input:checkbox:checked', formObj).length) {
            e.preventDefault();
            $('.dialog#confirm-action').undelegate('.confirm');
            $('.dialog#confirm-action').delegate('input.confirm', 'click', function(e) {
                e.preventDefault();
                $('.dialog#confirm-action').hide();
                $('#overlay').hide();
                formObj.submit();
                return false;
            });
            $('#overlay').show();
            $('.dialog#confirm-action .confirm-action').hide();
            $('.dialog#confirm-action p#delete-confirm')
            .show()
            .parent('div').show().trigger('click');
            return false;
        }
        else return true;
    });
});
if ( $(".code-green.awesome").text() == $(".current-version").text() ) {
	$( ".current-version" ).addClass( "checked" );		
	$( "#version-msg" ).hide();
} else { 
	$( ".current-version" ).addClass( "highlight" );	
	$( "#version-msg" ).show();	
}
function myFunction(x) {
  if (x.matches) { // If media query matches
	$("#print-logo").addClass("xxx");
	$("#print-logo-placeholder").append($("#print-logo.xxx")); 
  } else {
	$("#print-logo").removeClass("xxx");
	$("#logo-type").append($("#print-logo"))
  }
}
var x = window.matchMedia( "(max-width: 976px)" )
myFunction(x) // Call listener function at run time
x.addListener(myFunction) // Attach listener function on state changes
</script>
	<script type="text/javascript">
	<!--
		document.write('<img src="https://osticketawesome.com/cgi-bin/axs2/ax.cgi?mode=img&ref=');
		document.write( escape( document.referrer ) );
		document.write('" height="1" width="1" style="display:none" alt="" />');
	// -->
	</script><noscript>
		<img src="https://osticketawesome.com/cgi-bin/axs2/ax.cgi?mode=img" height="1" width="1" style="display:none" alt="" />
	</noscript>