<?php 
/***************************************************************************

Plugin Name:  FlamingText Logo
Plugin URI:   http://www.flamingtext.com/plugin/wordpress/
Description:  A plugin to easily add FlamingText images as headings into your posts.
Version:      1.0
Author:       Raymond Zhao
Email:	      raymond@flamingtext.com
Author URI:   http://www.flamingtext.com
License:      GPLv2

**************************************************************************

Copyright (C) - All right reserved by FlamingText.com Pty Ltd 1999-2011.

**************************************************************************/
class FlamingText {
	var $version = '0.3';
	var $settings = array();
	var $defaultsettings = array();
	var $presets = array();
	var $standardcss;
	var $cssalignments;
	var $wpheadrun = FALSE;
	var $adminwarned = FALSE;

	// Class initialization
	function FlamingText() {
		global $wpmu_version;

		// For debugging (this is limited to localhost installs since it's not nonced)
		if ( !empty($_GET['resetalloptions']) && 'localhost' == $_SERVER['HTTP_HOST'] && is_admin() && 'flamingtext' == $_GET['page'] ) {
			update_option( 'title', array() );
			wp_redirect( admin_url( 'options-general.php?page=flamingtext&defaults=true' ) );
			exit();
		}

		// Create default settings array
		$this->defaultsettings = apply_filters( 'ft_defaultsettings', array(
			'flickrslideshow' => array(
				'button'          => 1,
			),
			'userID'			=> '',
			'groupID'           => '',
			'transparency'		=> 0,
			'width'				=> 450,
			'height'			=> 500,
			'alignment'			=> 'left',
			'tinymceline'		=> 3,
		) );
		
		// Setup the settings by using the default as a base and then adding in any changed values
		// This allows settings arrays from old versions to be used even though they are missing values
		$usersettings = (array) get_option('ft_options');
		$this->settings = $this->defaultsettings;
		if ( $usersettings !== $this->defaultsettings ) {
			foreach ( (array) $usersettings as $key1 => $value1 ) {
				if ( is_array($value1) ) {
					foreach ( $value1 as $key2 => $value2 ) {
						$this->settings[$key1][$key2] = $value2;
					}
				} else {
					$this->settings[$key1] = $value1;
				}
			}
		}
		
		$usersettings = (array) get_option('ft_options');
		foreach ( $this->defaultsettings as $type => $setting ) {
			if ( !is_array($this->defaultsettings[$type]) ) continue;
			if ( isset($usersettings[$type]['button']) )
				unset($usersettings[$type]['button']); // Reset buttons
		}
		$usersettings['version'] = $this->version;
		update_option( 'ft_options', $usersettings );
		
		// Register general hooks

//		add_filter( 'plugin_action_links', array(&$this, 'AddPluginActionLink'), 10, 2 );
		
		add_action( 'delete_user', array(&$this, 'dropPresets') );
		add_action( 'admin_post_tssettings', array(&$this, 'POSTHandler') );
		add_action( 'wp_head', array(&$this, 'Head') );
		add_action( 'admin_head', array(&$this, 'Head') );
		/*
		if ( 'update.php' == basename( $_SERVER['PHP_SELF'] ) && 'upgrade-plugin' == $_GET['action'] && FALSE !== strstr( $_GET['plugin'], 'vipers-video-quicktags' ) )
			add_action( 'admin_notices', array(&$this, 'AutomaticUpgradeNotice') );
		*/

		// Register editor button hooks
		add_filter( 'tiny_mce_version', array(&$this, 'tiny_mce_version') );
		add_filter( 'mce_external_plugins', array(&$this, 'mce_external_plugins') );
		add_action( 'edit_form_advanced', array(&$this, 'getPresets') );
		add_action( 'edit_page_form', array(&$this, 'getPresets') );
		add_action( 'edit_form_advanced', array(&$this, 'AddQuicktagsAndFunctions') );
		add_action( 'edit_page_form', array(&$this, 'AddQuicktagsAndFunctions') );
		add_action( 'wp_insert_post', array(&$this, 'add_link') );
		if ( 1 == $this->settings['tinymceline'] )
			add_filter( 'mce_buttons', array(&$this, 'mce_buttons') );
		else
			add_filter( 'mce_buttons_' . $this->settings['tinymceline'], array(&$this, 'mce_buttons') );
		
		// Register scripts and styles
		if ( is_admin() ) {
			// Editor pages only
			if ( in_array( basename($_SERVER['PHP_SELF']), apply_filters( 'ts_editor_pages', array('post-new.php', 'page-new.php', 'post.php', 'page.php') ) ) ) {
				add_action( 'admin_head', array(&$this, 'EditorCSS') );
				add_action( 'admin_footer', array(&$this, 'OutputjQueryDialogDiv') );
/* using jquery 1.8.4 instead of defatul 1.7.3
        	                wp_deregister_script( 'jquery-ui-resizable' );
        	                wp_deregister_script( 'jquery-ui-draggable' );
        	                wp_deregister_script( 'jquery-ui-sortable' );
        	                wp_deregister_script( 'jquery-ui-dialog' );
                                wp_enqueue_script( 'jquery-ui-core', plugins_url('/flamingtext/resources/jquery-ui/jquery.ui.core.min.js'), ('jquery'), '1.8.4' );
                                wp_enqueue_script( 'jquery-ui-resizable', plugins_url('/flamingtext/resources/jquery-ui/jquery.ui.resizable.min.js'), array('jquery-ui-core', 'jquery-ui-mouse', 'jquery-ui-widget'), '1.8.4' );
                                wp_enqueue_script( 'jquery-ui-draggable', plugins_url('/flamingtext/resources/jquery-ui/jquery.ui.draggable.min.js'), array('jquery-ui-core'), '1.8.4' );
				wp_enqueue_script( 'jquery-ui-dialog', plugins_url('/flamingtext/resources/jquery-ui/jquery.ui.dialog.min.js'), array('jquery-ui-core', 'jquery-ui-draggable', 'jquery-ui-resizable'), '1.8.4' );
				wp_enqueue_script( 'jquery-ui-widget', plugins_url('/flamingtext/resources/jquery-ui/jquery.ui.widget.min.js'), '1.8.4' );
				wp_enqueue_script( 'jquery-ui-mouse', plugins_url('/flamingtext/resources/jquery-ui/jquery.ui.mouse.min.js'), array('jquery-ui-widget'), '1.8.4' );
*/
				wp_enqueue_script( 'jquery-ui-dialog' );
				wp_enqueue_script( 'jquery-ui-slider', plugins_url('/flamingtext/resources/jquery-ui/ui.slider.js'), array('jquery-ui-core'), '1.7.3' );
				wp_enqueue_style( 'ft-jquery-ui', plugins_url('/flamingtext/resources/jquery-ui/ft-jquery-ui.css'), array(), $this->version, 'screen' );
			}
		}
	}
	
	
	// Add a link to the settings page to the plugins list
	function AddPluginActionLink( $links, $file ) {
		static $this_plugin;
		
		if( empty($this_plugin) ) $this_plugin = plugin_basename(__FILE__);

		if ( $file == $this_plugin ) {
			$settings_link = '<a href="' . admin_url( 'options-general.php?page=tylr-slidr' ) . '">' . __('Settings', 'flamingtext') . '</a>';
			array_unshift( $links, $settings_link );
		}

		return $links;
	}

	function add_link( $postId ) {
		global $wpdb;
		$content = $wpdb->get_var("select post_content from wp_posts where id=".$postId);
		if (preg_match("/<img src=\"[^ ]+\/flamingtext_com_\w+\.(?:gif|png)\"(?![\d\D]*\blogo by <a href=\"http:\/\/www.flamingtext.com\/)/", $content)) {
			$content.="<div style=\"font-style: italic; text-align: right; color: grey; color: grey; font-size: small;\" class=\"post-meta\">logo by <a href=\"http://www.flamingtext.com/\" target=\"_blank\">FlamingText.com</a></div>";
			$wpdb->query($wpdb->prepare("update wp_posts set post_content =%s where id =%d", $content, $postId));
		}
	}

/*
	// Output the settings page
	function SettingsPage() {
		global $wpmu_version;

		$tab = ( !empty($_GET['tab']) ) ? $_GET['tab'] : 'general';

		if ( !empty($_GET['defaults']) ) : ?>
<div id="message" class="updated fade"><p><strong><?php _e('Settings for this tab reset to defaults.', 'tylr-slidr'); ?></strong></p></div>
<?php endif; ?>

<div class="wrap">

	<h2 style="position:relative">
<?php

	_e("Tylr Slidr", 'tylr-slidr');

?>
	</h2>


	<ul class="subsubsub">
<?php
		$tabs = array(
			'credits'     => __('Credits', 'tylr-slidr'),
		);
		$tabhtml = array();

		// If someone wants to remove a tab (for example on a WPMU intall)
		$tabs = apply_filters( 'ts_tabs', $tabs );

		$class = ( 'general' == $tab ) ? ' class="current"' : '';
		$tabhtml[] = '		<li><a href="' . admin_url( 'options-general.php?page=tylr-slidr' ) . '"' . $class . '>' . __('General', 'tylr-slidr') . '</a>';

		foreach ( $tabs as $stub => $title ) {
			$class = ( $stub == $tab ) ? ' class="current"' : '';
			$tabhtml[] = '		<li><a href="' . admin_url( 'options-general.php?page=tylr-slidr&amp;tab=' . $stub ) . '"' . $class . ">$title</a>";
		}

		echo implode( " |</li>\n", $tabhtml ) . '</li>';
?>

	</ul>

	<form id="tssettingsform" method="post" action="admin-post.php">

	<?php wp_nonce_field('tylr-slidr'); ?>

	<input type="hidden" name="action" value="tssettings" />

	<script type="text/javascript">
	// <![CDATA[
		jQuery(document).ready(function() {
			// Show items that need to be hidden if Javascript is disabled
			// This is needed for pre-WordPress 2.7
			jQuery(".hide-if-no-js").removeClass("hide-if-no-js");

			// Confirm pressing of the "reset tab to defaults" button
			jQuery("#ft-defaults").click(function(){
				var areyousure = confirm("<?php echo js_escape( __("Are you sure you want to reset this to the default settings?", 'tylr-slidr') ); ?>");
				if ( true != areyousure ) return false;
			});
		});
	// ]]>
	</script>

<?php
	// Figure out which tab to output
	switch ( $tab ) {
		case 'credits': ?>
	<p><?php _e('This plugin uses many scripts and packages written by others. They deserve credit too, so here they are in no particular order:', 'vipers-video-quicktags'); ?></p>

	<ul>
		
		<li><?php printf( __('<strong><a href="%1$s">Alex aka ViperBond007</a></strong> for writing <a href="%2$s">Vipers Video Quicktags</a> which was the basis for this plugin. It taught me everything i know regarding how to write a WP Plugin.', 'tyler-slider'), 'http://www.viper007bond.com/', 'http://www.viper007bond.com/wordpress-plugins/vipers-video-quicktags/' ); ?></li>
		<li><?php printf( __('The authors of and contributors to <a href="%s">jQuery</a>, the awesome Javascript package used by WordPress.', 'tylr-slidr'), 'http://jquery.com/' ); ?></li>
		<li><?php printf( __("Everyone who's helped create <a href='%s'>WordPress</a> as without it and it's excellent API, this plugin obviously wouldn't exist.", 'tylr-slidr'), 'http://jquery.com/' ); ?></li>
		<li><?php _e('Everyone who has provided bug reports and feature suggestions for this plugin.', 'vipers-video-quicktags'); ?></li>
	</ul>

<?php
			break; // End credits

		default;
?>




	<p><?php _e('Obtain the Flickr user ID from <a href="http://www.idgettr.com">www.idgettr.com</a>.', 'tylr-slidr'); ?></p>

	<input type="hidden" name="ft-tab" value="general" />

	<table class="form-table">
		<tr valign="top">
			<th scope="row"><label for="ft-userID"><?php _e('Default User ID', 'tylr-slidr'); ?></label></th>
			<td>
				<input type="text" name="ft-userID" id="ft-width" value="<?php echo attribute_escape($this->settings['userID']); ?>" size="50" class="tswide" /><br />
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="ft-groupID"><?php _e('Default Group ID', 'tylr-slidr'); ?></label></th>
			<td>
				<input type="text" name="ft-groupID" id="ft-groupID" value="<?php echo attribute_escape($this->settings['groupID']); ?>" size="50" class="tswide" /><br />
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="ft-groupID"><?php _e('Transparency', 'tylr-slidr'); ?></label></th>
			<td>
				<input type="checkbox" name="ft-transparency" id="ft-transparency" value="1" <?php if(attribute_escape($this->settings['transparency']) == '1'){ echo 'checked';} ?> />
				Set the wmode of the slideshow to opaque. This will allow HTML elements (like a drop down navigation) to appear on top of the slideshow
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="ft-width"><?php _e('Default Width', 'tylr-slidr'); ?></label></th>
			<td>
				<input type="text" name="ft-width" id="ft-width" value="<?php echo attribute_escape($this->settings['width']); ?>" size="50" class="tswide" /><br />
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="ft-height"><?php _e('Default Height', 'tylr-slidr'); ?></label></th>
			<td>
				<input type="text" name="ft-height" id="ft-height" value="<?php echo attribute_escape($this->settings['height']); ?>" size="50" class="tswide" /><br />
			</td>
		</tr>
		<tr valign="top">
			<th scope="row"><label for="ft-tinymceline"><?php _e('Show Button In Editor On Line Number', 'tylr-slidr'); ?></label></th>
			<td>
				<select name="ft-tinymceline" id="ft-tinymceline">
<?php
					$alignments = array(
						1 => __('1', 'tylr-slidr'),
						2 => __('2 (Kitchen Sink Toolbar)', 'tylr-slidr'),
						3 => __('3 (Default)', 'tylr-slidr'),
					);
					foreach ( $alignments as $alignment => $name ) {
						echo '					<option value="' . $alignment . '"';
						selected( $this->settings['tinymceline'], $alignment );
						echo '>' . $name . "</option>\n";
					}
?>
				</select>
			</td>
		</tr>
	</table>
<?php
			// End General tab
	}
?>

<?php if ( 'help' != $tab && 'credits' != $tab ) : ?>
	<p class="submit">
		<input type="submit" name="ft-submit" value="<?php _e('Save Changes'); ?>" />
		<input type="submit" name="ft-defaults" id="ft-defaults" value="<?php _e('Reset Tab To Defaults', 'tylr-slidr'); ?>" />
	</p>
<?php endif; ?>

	</form>
</div>

<?php

	}
*/

	// Add default presets
	function addDefaultPresets($userId) {
		global $wpdb;
		$wpdb->query("insert into wp_ft_presets
		 values (".$userId.", 'Chrominium', 'script=chrominium-logo&fontname=Ethnocentric&fontsize=25&text=your text')");
		$wpdb->query("insert into wp_ft_presets
		 values (".$userId.", 'Flaming', 'script=flaming-logo&fontname=futura_poster&fontsize=50&text=your text')"); 
		$wpdb->query("insert into wp_ft_presets
		 values (".$userId.", 'Mosaic', 'script=mosaic-logo&fontname=cooper&fontsize=55&text=your text')");
		$wpdb->query("insert into wp_ft_presets
		 values (".$userId.", 'Aurora', 'script=aurora-logo&fontname=baskerville&fontsize=40&text=your text')"); 
		$wpdb->query("insert into wp_ft_presets
		 values (".$userId.", 'Wood', 'script=wood&fontname=cooper&fontsize=55&text=your text')"); 
	} 

	// Drop presets for deleted user
	function dropPresets($userId) {
		if(get_user_meta($userId, 'rich_editing', true)==true) {
			global $wpdb;
			$wpdb->query("delete from wp_ft_presets where userId =".$userId);
		}
	}	

	// Get presets for current user, if there is none preset at all, add default presets
	function getPresets() {
		global $wpdb;
		$userId = get_current_user_id();
		if ($wpdb->get_var("select count(*) from ".$wpdb->prefix."ft_presets where userId=".$userId)==0) {
			$this->addDefaultPresets($userId);	
		}
		$this->presets = $wpdb->get_results("select preset, querystring from ".$wpdb->prefix."ft_presets where userId=".$userId, ARRAY_A);	
	}	 
	
	// Output the <div> used to display the dialog box
	function OutputjQueryDialogDiv() { 
?>
<div class="hidden">
	<div id="save-preset">
		<?php _e('Give a name to new FlamingText preset:', 'flamingtext') ?>
		<p><label><b><?php _e('Name', 'flamingtext') ?></b>: </label><input type="text" id="save-preset-name" style="width:200px" />
	</div>
</div>

<div class="hidden">
	<div id="overwrite-preset">
		<?php _e('Warning: a preset with name ', 'flamingtext') ?><label id="overwrite-preset-name"></label> <?php _e('already exists.', 'flamingtext') ?> 
		<p><center><b><?php _e('Overwrite?', 'flamingtext') ?></b></center><p>
	</div>
</div>

<div class="hidden">
	<div id="delete-preset">
		<?php _e('please choose a preset to delete:', 'flamingtext') ?>
		<p><center><form><select id ="preset-list" ></select></form></center><p>
	</div>
</div>

<div class="hidden">
	<div id="ft-dialog">
		<div class="ft-dialog-content">
			<div id="ft-dialog-message"><h3>
				<p><?php _e('Enter your text and press Okay button to insert an image:', 'flamingtext') ?></p>
				<label id="logo-text"><b><?php _e('Text', 'flamingtext') ?></b>:<input type="text" id="ft-dialog-text"  class="ft-dialog-dim" style="width:260px" onKeypress=textPressed() value="<?php _e('your text') ?>"/></label>
				<label id="script"><b><?php _e('Style', 'flamingtext') ?></b>: 
<select id="ft-dialog-script" name="script" onKeyPress=onChange() onChange=onChange()><option value="colored2-logo" selected="selected"><?php _e('Doodle', 'flamingtext') ?></option><option value="alien-glow-anim-logo"><?php _e('Alien Glow', 'flamingtext') ?></option><option value="aurora-logo"><?php _e('Aurora Borealis', 'flamingtext') ?></option><option value="flaming-logo"><?php _e('FlamingText', 'flamingtext') ?></option><option value="chrominium-logo"><?php _e('Chrominium', 'flamingtext') ?></option><option value="burn-in-anim-logo"><?php _e('Burn-In', 'flamingtext') ?></option><option value="frosty-logo"><?php _e('Frosty', 'flamingtext') ?></option><option value="glow-logo"><?php _e('Glow', 'flamingtext') ?></option><option value="drop-shadow-logo"><?php _e('Drop Shadow', 'flamingtext') ?></option><option value="mosaic-logo"><?php _e('Mosaic', 'flamingtext') ?></option><option value="whirl-anim-logo"><?php _e('Whirl', 'flamingtext') ?></option><option value="blue-fire"><?php _e('Blue-Flame', 'flamingtext') ?></option><option value="wood"><?php _e('Wood', 'flamingtext') ?></option></label>
				<table border="0" cellspacing="0" cellpadding="0"><tr><td><label id="font-size"><b><?php _e('Size', 'flamingtext') ?></b>: <input id="ft-dialog-fontsize" value="40" maxlength="2" size="3" onKeyPress=onChange() onChange=onChange() /></label></td><td width="200"><div id="ft-fontsizeSlider"></div></td></tr></table>
				<table border="0" cellspacing="0" cellpadding="0">
					<tr><td><label><b><?php _e('Font', 'flamingtext') ?></b>:
<select id="ft-dialog-fontname" name="fontname" onKeyPress="changeFont(this.options[this.selectedIndex].value)" onchange="changeFont(this.options[this.selectedIndex].value)">
<option value="agate" selected="selected">Agate</option><option value="alfreddrake">Alfred Drake</option><option value="apollo">Apollo</option><option value="arnoldboecklin">Arnold Boecklin</option><option value="baskerville">Baskerville</option><option value="becker">Becker</option><option value="blackforest">Black Forest</option><option value="blippo">Blippo</option><option value="bodidly">Bodidly</option><option value="bodoni">Bodoni</option><option value="capri">Capri</option><option value="comicscartoon">Comics Cartoon</option><option value="cooper">Cooper</option><option value="cracklingfire">Crackling Fire</option><option value="crillee">Crillee</option><option value="cuneifontlight">Cunei Font Light</option><option value="dragonwick">Dragonwick</option><option value="engraver">Engraver</option><option value="frizquadrata">Friz Quadrata</option><option value="futura_poster">Futura Poster</option><option value="romeo">Romeo</option><option value="roostheavy">Roost Heavy</option><option value="tribeca">Tribeca</option><option value="victoriassecret">Victorias Secret</option></select></label></td>
					<td width = "10"></td>
					<td colspan="3"><input type="image" width="300" height="40" id="ft-dialog-fontimage" src="http://cdn1.ftimg.com/fonts/preview/agate-p.gif" alt='Font Preview' border="1"/></td></tr>
				</table>
				<br/>
				<div id="out-put-img"><center><img id="ft-preview-logo" src="" alt="" border="0" > <br/></center></div>
				<br/>
				<div><center><font color="red" id=ft-dialog-error></font></center></div>
				</h3></div>
			<div style="position: absolute; bottom: 90px; left: 260px;"><img id="ft-busy-logo" src="<?php echo plugins_url('/flamingtext/buttons/busy.gif') ?>" ></div>
			<div style="position: absolute; bottom: 80px; right: 25px"><a href="#" onclick="editPopup();"><?php _e('edit preset', 'flamingtext') ?></a>&nbsp;|&nbsp;<a href="#" onclick='savePopup();'><?php _e('save preset', 'flamingtext') ?></a></div>
			<div style="position: absolute; bottom: 65px;"><small>Powered By <a href=http://www.flamingtext.com/ target="_blank">FlamingText.com</a></small></p></div>
			<div style="position: absolute; bottom: 65px; right: 20px"><font id=preset-indicator></font></div>
		</div>
	</div>
</div>

<?php
}

// Hide TinyMCE buttons the user doesn't want to see + some misc editor CSS
function EditorCSS() {
	echo "<style type='text/css'>\n	#ft-precacher { display: none; }\n";

		// Attempt to match the dialog box to the admin colors
		$color = ( 'classic' == get_user_option('admin_color', $user_id) ) ? '#CFEBF7' : '#EAF3FA';
		$color = apply_filters( 'ft_titlebarcolor', $color ); // Use this hook for custom admin colors
		echo "	.ui-dialog-titlebar { background: $color; }\n";

		echo "</style>\n";
	}
	
		// Output the head stuff
	function Head() {
		$this->wpheadrun = TRUE;

		echo "<!-- FlamingText plugin v" . $this->version . " | http://www.flamingtext.com-->\n<style type=\"text/css\">\n";
		$aligncss = str_replace( '\n', ' ', $this->cssalignments[$this->settings['alignment']] );
		
		$standardcss = $this->StringShrink( $this->standardcss );
		echo strip_tags( str_replace( '/* alignment CSS placeholder */', $aligncss, $standardcss ) );

		// WPMU can't use this to avoid them messing with the theme
		if ( empty($wpmu_version) ) echo ' ' . strip_tags( $this->StringShrink( $this->settings['customcss'] ) );

		echo "\n</style>\n";
		
		?>
<?php
	}
			
	// Break the browser cache of TinyMCE
	function tiny_mce_version( $version ) {
		return $version . '-ts' . $this->version . 'line' . $this->settings['tinymceline'];
	}
	
	// Load the custom TinyMCE plugin
	function mce_external_plugins( $plugins ) {
		$plugins['flamingtext'] = plugins_url('/flamingtext/resources/tinymce3/editor_plugin.js');
		return $plugins;
	}

	// Add the custom TinyMCE buttons
	function mce_buttons( $buttons ) {
		array_push( $buttons, 'ftsplitbutton');
		return $buttons;
	}
	
	// Add the old style buttons to the non-TinyMCE ,editor views and output all of the JS for the button function + dialog box
	function AddQuicktagsAndFunctions() {
		global $wp_version;

		$types = array('flaming-text' => array('Flickr Slideshow', 'FlamingText Logo Creator'),);

		$buttonhtml = $datajs = '';
		foreach ( $types as $type => $strings ) {
			// HTML for quicktag button
			if ( 1 == $this->settings[$type]['button'] )
				$buttonshtml .= '<input type="button" class="ed_button" onclick="TSButtonClick()" title="' . $strings[1] . '" value="' . $strings[0] . '" />';

			// Create the data array
			$datajs .= "	FTData['$type'] = {\n";
			$datajs .= '		title: "' . $this->js_escape( ucwords( $strings[1] ) ) . '"';
			$datajs .= ",\n".'		userID: "' . $this->settings['userID'] . '"';
			$datajs .= ",\n".'	groupID: "' . $this->settings['groupID'] . '"';
			
			if ( !empty($this->settings['width']) && !empty($this->settings['height']) ) {
				$datajs .= ",\n		width: " . $this->settings['width'] . ",\n";
				$datajs .= '		height: ' . $this->settings['height'];
			}
			$datajs .= "\n	};\n";
		}
?>


<script type="text/javascript">

function log(msg) {
    setTimeout(function() {
        throw new Error(msg);
    }, 0);
}

var FTData = {};
<?php echo $datajs; ?>
	var TSDialogDefaultHeight = 400;
	var TSDialogDefaultExtraHeight = 500;
	var userId = "<?php echo get_current_user_id() ?>";
	var $presets;
	var $presetslistname = "<?php _e('Presets', 'flamingtext') ?>";
	var $splitbuttonname = "<?php _e('Insert / edit FlamingText logos', 'flamingtext') ?>";
	var $customlogoname = "<?php _e('Custom Logos...', 'flamingtext') ?>";
	var inputText;
	var notification = false;
	
	// This function iss run when a button is clicked. It creates a dialog box for the user to input the data.
	function TSButtonClick() {
		// Calculate the height/maxHeight (i.e. add some height for Blip.tv)
		TSDialogHeight = TSDialogDefaultHeight;
		TSDialogMaxHeight = TSDialogDefaultHeight + TSDialogDefaultExtraHeight;

		// Open the dialog while setting the width, height, title, buttons, etc. of it
		var buttons = { "<?php _e('Okay', 'flamingtext'); ?>": TSButtonOkay, "<?php _e('Cancel', 'flamingtext'); ?>": TSDialogClose };
		var title = '<img src="<?php echo plugins_url('/flamingtext/buttons/'); ?>' +  'flamingtext.png" width="20" height="20" /> ' + "<?php _e('FlamingText Logo Creator', 'flamingtext') ?>";
		jQuery("#ft-dialog").dialog({ autoOpen: false, width: 540, minWidth: 540, height: TSDialogHeight, minHeight: TSDialogHeight, maxHeight: TSDialogMaxHeight, zIndex: 999999, title: title, buttons: buttons, resize: TSDialogResizing });

		// Reset the dialog box incase it's been used before
		jQuery("#ft-dialog-slide-header").removeClass("selected");

		// Style the jQuery-generated buttons by adding CSS classes and add second CSS class to the "Okay" button
		jQuery("#ft-dialog button").addClass("button").each(function(){
			if ( "<?php _e('Okay', 'flamingtext'); ?>" == jQuery(this).html() ) jQuery(this).addClass("button-highlighted");
		}); 

		if (!notification) {
			jQuery(".ui-dialog button").parent().append("<div style=\"color: grey; color: grey; font-size: small;\" class=\"post-meta\">" + "<?php _e('Pressing Okay will insert \"logo by FlamingText.com\" into your post.', 'flamingtext') ?>" + "</div>");
			jQuery(".ui-dialog button").parent().height(45);
			notification = true;
		}

		jQuery(".ft-dialog-slide").removeClass("hidden");
		//jQuery("#ft-dialog-width").val(FTData[tag]["width"]);
		//jQuery("#ft-dialog-height").val(FTData[tag]["height"]);
		jQuery("#ft-busy-logo").hide();
		jQuery("#ft-dialog-error").text("");
		jQuery("#preset-indicator").text("");
                // fontsizeSlider();   --- don't include it now cause it contains link with current jQuery version (1.7.3, 1.8.4), very easy to open a new page by sldering the slider - which we don't want.

                if ( typeof tinyMCE != 'undefined' && ( ed = tinyMCE.activeEditor ) && !ed.isHidden() ) {
                	if (txt = getSelectText()) jQuery("#ft-dialog-text").val(txt);
                }
		inputText = jQuery("#ft-dialog-text").val();
		checkOkayButton();
                onChange();

		// Do some hackery on any links in the message -- jQuery(this).click() works weird with the dialogs, so we can't use it
		jQuery("#ft-dialog-message a").each(function(){
			jQuery(this).attr("onclick", 'window.open( "' + jQuery(this).attr("href") + '", "_blank" );return false;' );
		});

		// Show the dialog now that it's done being manipulated
		jQuery("#ft-dialog").dialog("open");

		// Focus the input field
		jQuery("#ft-dialog-text").focus();
		jQuery("#ft-dialog-text").select();
	}

	function fontsizeSlider() {
		jQuery("#ft-fontsizeSlider").slider({
			slide: function(event,ui) {
				jQuery('#ft-dialog-fontsize').val(ui.value);
			}, value: jQuery('#ft-dialog-fontsize').val(), min: 5, max: 99, step: 1
		});
	}

	// Close + reset
	function TSDialogClose() {
		jQuery(".ui-dialog").height(TSDialogDefaultHeight);
		jQuery("#ft-dialog-text").val("<?php _e('your text','flamingtext') ?>");
		jQuery("#ft-dialog").dialog("close");
	}

	function loadPresets() {
                var $allPresets = "<?php echo $this->get_presets() ?>";
                $presets = $allPresets.split("\t"); 
	}
	
	function addPreset($preset) {
		var i = jQuery.inArray($preset, $presets);
		if(i == -1) {
			$presets.push($preset);
			$presets.push(getQueryString());
		} else {
			$presets[i+1] = getQueryString();
		}
	}

	function returnPresetnames() {
		var $presetnames = new Array();
		for (i=0; i<$presets.length; i+=2) {
			$presetnames[i/2] = $presets[i];
		}
		return $presetnames;	
	}

	function textPressed() {
		setTimeout('checkOkayButton()',0);
		setTimeout('onChangeText()',1000);
	}

	function checkOkayButton() {
		if (jQuery("#ft-dialog-text").val()) enableOkayButton();
		else disableOkayButton();
	}

	function onChangeText() {
		if (jQuery("#ft-dialog-text").val()!=inputText) {
			onChange();
			inputText = jQuery("#ft-dialog-text").val();
		}
	}

	function savePreset($preset) {
		jQuery("#overwrite-preset").dialog("close");

		var http_request = new XMLHttpRequest();
		http_request.open( "GET", ftSavePresetString($preset), true );
		http_request.onreadystatechange = function() {
  			if (http_request.readyState == 4) {
				if (http_request.status == 200){
					var text = http_request.responseText;
					json_statusObject = !(/[^,:{}\[\]0-9.\-+Eaeflnr-u \n\r\t]/.test(text.replace(/"(\\.|[^"\\])*"/g, '')))&& eval('(' + text + ')'); 
					if (json_statusObject.sts) {
						addPreset($preset);
						rebuildButtonMenu();
						// close dialog if preset saved successfully
						TSDialogClose();
					} else if (json_statusObject.error) {
						jQuery("#preset-indicator").text(json_statusObject.error);
					} else {
						jQuery("#preset-indicator").text("<?php _e('error during saving preset: bad response from database','flamingtext') ?>");
					}
				} else {
					jQuery("#preset-indicator").text("error:"+http_request.status+":"+http_request.responseText);
				}
			}
		}
		http_request.send(null);
		jQuery("#save-preset").dialog("close");
	}

	function editPreset() {
		var $deletePreset = jQuery("#preset-list option:selected").text();
                var http_request = new XMLHttpRequest();
                http_request.open( "GET", ftDeletePresetString($deletePreset), true );
                http_request.onreadystatechange = function() {
                        if (http_request.readyState == 4) {
                                if (http_request.status == 200){
                                        var text = http_request.responseText;
                                        json_statusObject = !(/[^,:{}\[\]0-9.\-+Eaeflnr-u \n\r\t]/.test(text.replace(/"(\\.|[^"\\])*"/g, '')))&& eval('(' + text + ')'); 
                                        if (json_statusObject.sts) {
                                                jQuery("#preset-indicator").text(json_statusObject.sts);
						$presets.splice(jQuery.inArray($deletePreset, $presets), 2);
                                                rebuildButtonMenu();
                                        } else if (json_statusObject.error) {
                                                jQuery("#preset-indicator").text(json_statusObject.error);
                                        } else {
                                                jQuery("#preset-indicator").text("<?php _e('error during deleting preset: bad response from database','flamingtext') ?>");
                                        }
                                } else {
                                        jQuery("#preset-indicator").text("error:"+http_request.status+":"+http_request.responseText);
                                }
                        }
                }
                http_request.send(null);
		jQuery("#delete-preset").dialog("close");
	}	

	function editPopup() {
		if($presets.length==0) {
                	jQuery("#preset-indicator").text("<?php _e('no preset for editing, refresh page to restore default','flamingtext') ?>");
			return;
		} 

		var buttons = { "<?php _e('Delete', 'flamingtext') ?>" : editPreset };
		var title = '<img src="<?php echo plugins_url('/flamingtext/buttons/'); ?>' +  'flamingtext.png" width="20" height="20" /> ' + "<?php _e('Deleting preset...', 'flamingtext') ?>";
		jQuery("#delete-preset").dialog({ autoOpen: false, width: 320, height: 170,  title: title, buttons: buttons, resizable: false, draggable : false });
		jQuery("#preset-list").empty();
		for (i=0; i<$presets.length; i+=2) {
    			jQuery('#preset-list').append("<option value=" + $presets[i] + ">" + $presets[i] + "</option>");
		}
		jQuery("#ft-dialog").dialog("close");
		jQuery("#delete-preset").bind("dialogclose", function() {jQuery("#ft-dialog").dialog("open");});	
		jQuery("#delete-preset").dialog("open");
	}

	function overwritePopup($preset) {
		var buttons = { "<?php _e('yes', 'flamingtext') ?>" : savePreset, "<?php _e('no', 'flamingtext') ?>" : function() {jQuery("#overwrite-preset").dialog("close")}};
		var title = '<img src="<?php echo plugins_url('/flamingtext/buttons/'); ?>' +  'flamingtext.png" width="20" height="20" /> ' + "<?php _e('Saving preset...', 'flamingtext') ?>";
		jQuery("#overwrite-preset").dialog({ autoOpen: false, width: 320, height: 170,  title: title, buttons: buttons, resizable: false, draggable : false });
		jQuery("#overwrite-preset-name").text($preset);
		jQuery("#overwrite-preset").dialog("open");
	}

	function savePopup() {
		if (jQuery("#ft-dialog-text").val().length>50) {
			jQuery("#preset-indicator").text("<?php _e('text string is too long for save','flamingtext') ?>");
			return;
		}

		var button = { "<?php _e('Save', 'flamingtext') ?>" : savePresetButton };
		var title = '<img src="<?php echo plugins_url('/flamingtext/buttons/'); ?>' +  'flamingtext.png" width="20" height="20" /> ' + "<?php _e('Saving preset...', 'flamingtext') ?>";
		jQuery("#save-preset").dialog({ autoOpen: false, width: 320, height: 170,  title: title, buttons: button, resizable: false, draggable : false });

		// Reset the dialog box incase it's been used before
		jQuery("#ft-dialog").dialog("close");
		jQuery("#save-preset").dialog("open");
		jQuery("#save-preset").bind("dialogclose", function() {jQuery("#ft-dialog").dialog("open");});	
//		jQuery("#ft-dialog").dialog( "option", "stack", false );
		jQuery("#save-preset-name").val("");
		jQuery("#save-preset-name").focus();
	}

	function savePresetButton() {
		var $preset = jQuery("#save-preset-name").val();
		if (!$preset) return;
		if (jQuery.inArray($preset, $presets) == -1) {
			savePreset($preset);
		} else {
			overwritePopup($preset);
		}	
	}	

	function rebuildButtonMenu() {
		var m = tinyMCE.activeEditor.controlManager.get('content_ftsplitbutton_menu');
		if (!m) return;
		m.removeAll();
                m.add({title : 'Presets', 'class' : 'mceMenuItemTitle'}).setDisabled(1);

    	        for (i=0; i<$presets.length; i+=2) {
               		var $name=$presets[i];
                        m.add({title : $name, onclick : function(ev) {
	                        if (!tinymce.isIE) spButtonClick(ev.textContent);
                                else spButtonClick(ev.innerText);
                        }});
                }
/*              m.addSeparator();
                m.add({title : 'Revert to text', onclick : function() {
                	RevertImage();
                }});
*/
                m.addSeparator();
                m.add({title : $customlogoname, onclick : function() {
                	if ( typeof TSButtonClick == 'undefined' ) return;
                        else TSButtonClick();
                }});

		m.update();
	}

	function setPresetForPopup(set) {
		var $preset;
                for (i=0; i<$presets.length; i++) {
                        if ($presets[i] == set) $preset = $presets[i+1];
                }
		var $tokens = $preset.split("&");
		for (i=0; i<$tokens.length; i++) {
			if ($tokens[i].match("^fontsize=")) jQuery("#ft-dialog-fontsize").val($tokens[i].substr(9, $tokens[i].length));
			if ($tokens[i].match("^fontname=")) jQuery("#ft-dialog-fontname option[value=" +  $tokens[i].substr(9, $tokens[i].length) + "]").attr("selected", "selected");
			if ($tokens[i].match("^script=")) jQuery("#ft-dialog-script option[value=" +  $tokens[i].substr(7, $tokens[i].length) + "]").attr("selected", "selected");
			if ($tokens[i].match("^text=")) {
				if ($tokens[i].length>5) jQuery("#ft-dialog-text").val(decodeURIComponent($tokens[i].substr(5, $tokens[i].length)));
				else jQuery("#ft-dialog-text").val("");
			}
		}
	}

	function spButtonClick (set) {
		if ( typeof tinyMCE != 'undefined' && ( ed = tinyMCE.activeEditor ) && !ed.isHidden() ) {
			ed.focus();
			if (tinymce.isIE) ed.selection.moveToBookmark(tinymce.EditorManager.activeEditor.windowManager.bookmark);
			var txt = getSelectText();
			if (!txt) {
				setPresetForPopup(set);
				TSButtonClick();
			}
			else {
				jQuery("#ft-dialog-text").val(txt);
				var http_request = new XMLHttpRequest();
				http_request.open( "GET", ftSplitbuttonString(set), true );
				http_request.onreadystatechange = function () {
  					if (http_request.readyState == 4) {
						if (http_request.status == 200){
							var json_imgObject = {}; 
							var text = http_request.responseText;
							json_imgObject = !(/[^,:{}\[\]0-9.\-+Eaeflnr-u \n\r\t]/.test(text.replace(/"(\\.|[^"\\])*"/g, '')))&& eval('(' + text + ')'); 
							if (json_imgObject.src) {
								var imgUrl = json_imgObject.src;
								if ( typeof tinyMCE != 'undefined' && ( ed = tinyMCE.activeEditor ) && !ed.isHidden() ) {
									ed.focus();
									if (tinymce.isIE) ed.selection.moveToBookmark(tinymce.EditorManager.activeEditor.windowManager.bookmark);
									ed.execCommand('mceInsertContent', false, "<img alt='" + txt +"' src=" + imgUrl + " />");
								}
							} else if (json_imgObject.error) {
								 tinyMCE.activeEditor.windowManager.alert(json_imgObject.error);
							} else {
								 tinyMCE.activeEditor.windowManager.alert("<?php _e('bad response from server','flamingtext') ?>");
							}
						} else {					
							tinyMCE.activeEditor.windowManager.alert("error:"+http_request.status+":"+http_request.responseText);
						}
					}
				}
			http_request.send(null);
			}
		}
	}

	function getSelectText() {
		if (tinymce.isIE) {
			var a = tinyMCE.activeEditor.selection.getContent();	
			var b = a.replace(/<img[^>]*?\balt="([^"]*)"[^>]*>/g, "$1");
			return b.replace(/<[^>]*>/g, "");
		} else return tinyMCE.activeEditor.selection.getContent({format : 'text'});
	}

	// Callback function for the "Okay" button
	function TSButtonOkay() {
                var txt = jQuery("#ft-dialog-text").val();
                if (!txt) return TSDialogClose();

                disableOkayButton();
		jQuery("#ft-busy-logo").show();
		var http_request = new XMLHttpRequest();
		http_request.open( "GET", ftPopupString("save"), true );
		http_request.onreadystatechange = function () {
  			if (http_request.readyState == 4) {
				if (http_request.status == 200) {
					var json_imgObject = {}; 
					var text = http_request.responseText;
					json_imgObject = !(/[^,:{}\[\]0-9.\-+Eaeflnr-u \n\r\t]/.test(text.replace(/"(\\.|[^"\\])*"/g, '')))&& eval('(' + text + ')'); 
					if (json_imgObject.src) {
						var imgUrl = json_imgObject.src;
						if ( typeof tinyMCE != 'undefined' && ( ed = tinyMCE.activeEditor ) && !ed.isHidden() ) {
							ed.focus();
							if (tinymce.isIE) ed.selection.moveToBookmark(tinymce.EditorManager.activeEditor.windowManager.bookmark);
							ed.execCommand('mceInsertContent', false, "<img alt='" + txt + "' src=" + imgUrl + " />");
						}
						jQuery("#ft-busy-logo").hide();
						TSDialogClose();
					} else if (json_imgObject.error) {
                                                jQuery("#ft-busy-logo").hide();
						jQuery("#ft-dialog-error").text(json_imgObject.error);
					} else {
						jQuery("#ft-dialog-error").text("<?php _e('bad response from server','flamingtext') ?>");
						jQuery("#ft-busy-logo").hide();
					}
				} else {					
					jQuery("#ft-dialog-error").text("http error:"+http_request.status);					
					jQuery("#ft-busy-logo").hide();
				}
			}
		}
		http_request.send(null);
	}

	function disableOkayButton() {
		jQuery("#ft-dialog").parent().find("button").each(function() {
		        if( jQuery(this).text() == 'Okay' ) {
       		     		jQuery(this).attr('disabled', true);
        		}
		})
	}	

	function enableOkayButton() {
		jQuery("#ft-dialog").parent().find("button").each(function() {
		        if( jQuery(this).text() == 'Okay' ) {
       		     		jQuery(this).attr('disabled', false);
        		}
		})
	}	

	function onChange() {
		var text = jQuery("#ft-dialog-text").val();
		if (!text) {
			jQuery("#ft-preview-logo").attr("src","");
			return;
		}

		jQuery("#ft-dialog-error").text("");
		jQuery("#ft-busy-logo").show();
		var http_request = new XMLHttpRequest();
		http_request.open( "GET", ftPopupString(), true );
		http_request.onreadystatechange = function() {
  			if (http_request.readyState == 4) {
				if (http_request.status == 200){
					var json_imgObject = {}; 
					var text = http_request.responseText;
					json_imgObject = !(/[^,:{}\[\]0-9.\-+Eaeflnr-u \n\r\t]/.test(text.replace(/"(\\.|[^"\\])*"/g, '')))&& eval('(' + text + ')'); 
					if (json_imgObject.src) {
						var imgUrl = json_imgObject.src;
						jQuery("#ft-preview-logo").attr("src",imgUrl);
						jQuery("#ft-busy-logo").hide();
					} else if (json_imgObject.error) {
						jQuery("#ft-busy-logo").hide();
						jQuery("#ft-preview-logo").attr("src","<?php echo plugins_url('/flamingtext/buttons/fail.gif') ?>");
						jQuery("#ft-dialog-error").text(json_imgObject.error);
					} else {
						jQuery("#ft-dialog-error").text("<?php _e('bad response from server','flamingtext') ?>");
						jQuery("#ft-busy-logo").hide();
					}
				} else {
					jQuery("#ft-dialog-error").text("error:"+http_request.status+":"+http_request.responseText);
					jQuery("#ft-busy-logo").hide();
				}
			}
		}
		http_request.send(null);
	}	

	// Generate query string for external php program to save preset
	function ftSavePresetString(preset) {
		var queryString = "<?php echo plugins_url('/flamingtext/ft_db.php?save&') ?>";
		queryString += userId + "&";
		queryString += encodeURIComponent(preset) + "&";
		queryString += getQueryString();
		return queryString;
	}

	// Generate query string for external php program to delete preset
	function ftDeletePresetString(preset) {
		var queryString = "<?php echo plugins_url('/flamingtext/ft_db.php?delete&') ?>";
		queryString += userId + "&";
		queryString += encodeURIComponent(preset);
		return queryString;
	}

	function ftPopupString (toSave) {
		var queryString = "<?php echo plugins_url('/flamingtext/flamingtext_image_output.php?') ?>";
		if (toSave == "save") queryString += "save&"; 
		else queryString += "preview&"; 
                queryString += getQueryString();
		return queryString; 
	} 

	function ftSplitbuttonString(set) {
		var queryString = "<?php echo plugins_url('/flamingtext/flamingtext_image_output.php?save&') ?>";
                for (i=0; i<$presets.length; i++) {
                        if ($presets[i] == set) queryString += $presets[i+1];
                }
		queryString = queryString.replace(/text=[^&]*(?:$|&)/, "text=");
		queryString += encodeURIComponent(jQuery("#ft-dialog-text").val());
		return queryString; 
	}

	function getQueryString() {
		var querScript = "";
		queryString = "script=" + jQuery("#ft-dialog-script").val(); 
		queryString += "&fontname=" + jQuery("#ft-dialog-fontname").val(); 
		queryString += "&fontsize=" + jQuery("#ft-dialog-fontsize").val();
		queryString += "&text=" + encodeURIComponent(jQuery("#ft-dialog-text").val());
		return queryString;
	}	

	function changeFont(xxx) {
	        yyy="http://cdn1.ftimg.com/fonts/preview/"; 
		for (i=0;i<xxx.length;i++) {
    		if (xxx.charAt(i)==' ')
        		yyy = yyy+'+';
    		else
     			yyy = yyy+xxx.charAt(i);
    		}

		yyy += "-p.gif";

        	jQuery("#ft-dialog-fontimage").attr("src",yyy);
		onChange();
	}

	// Convert selected image back to text
//TODO: move cursor after reverting to fix image selection problem for tinyMCE
	function RevertImage() {
		if ( typeof tinyMCE != 'undefined' && ( ed = tinyMCE.activeEditor ) && !ed.isHidden() ) {
			ed.focus();
			if (tinymce.isIE) ed.selection.moveToBookmark(tinymce.EditorManager.activeEditor.windowManager.bookmark);
			if (txt = getSelectText()) {
		 		ed.execCommand('mceInsertContent', false,  txt);
			}
		}
	}

	// This function is called while the dialog box is being resized.
	function TSDialogResizing( test ) {
		if ( jQuery(".ui-dialog").height() > TSDialogHeight ) {
			jQuery("#ft-dialog-slide-header").addClass("selected");
		} else {
			jQuery("#ft-dialog-slide-header").removeClass("selected");
		}
	}

	// On page load...
	jQuery(document).ready(function(){
		// Add the buttons to the HTML view
		jQuery("#ed_toolbar").append('<?php echo $this->js_escape( $buttonshtml ); ?>');

		// Make the "Dimensions" bar adjust the dialog box height
		jQuery("#ft-dialog-slide-header").click(function(){
			if ( jQuery(this).hasClass("selected") ) {
				jQuery(this).removeClass("selected");
				jQuery(this).parents(".ui-dialog").animate({ height: TSDialogHeight });
			} else {
				jQuery(this).addClass("selected");
				jQuery(this).parents(".ui-dialog").animate({ height: TSDialogMaxHeight });
			}
		});

		// If the Enter key is pressed inside an input in the dialog, do the "Okay" button event
		jQuery("#ft-dialog :input").keyup(function(event){
			if ( 13 == event.keyCode ) // 13 == Enter
				TSButtonOkay();
		});

		// Make help links open in a new window to avoid loosing the post contents
		jQuery("#ft-dialog-slide a").each(function(){
			jQuery(this).click(function(){
				window.open( jQuery(this).attr("href"), "_blank" );
				return false;
			});
		});
	});
// ]]>
</script>

<?php
	}
	
	// Handle FlamingText shortcodes
	function shortcode_tylrslidr( $atts, $content = '' ) {
		
		$content = $this->wpuntexturize( $content );
		
		// Handle WordPress.com shortcode format
		if ( isset($atts[0]) ) {
			$atts = $this->attributefix( $atts );
			$content = $atts[0];
			unset($atts[0]);
		}

		if ( empty($content) ) return $this->error('No user or group ID was passed to the BBCode');

		if ( is_feed() ) return $this->postlink();

		// Set any missing $atts items to the defaults
		$atts = shortcode_atts(array(
			'width'    => $this->settings['width'],
			'height'   => $this->settings['height'],
			'userid'   => "",
			'groupid'   => ""
		), $atts);

		// Allow other plugins to modify these values (for example based on conditionals)
		$atts = apply_filters( 'ts_shortcodeatts', $atts, 'tylr-slidr' );
		
		$objectid = uniqid('ts');

		// KEEP BACKWARDS COMPATIBLE WITH V1.0
		$oldContent = split('user_id=', $content);

		if(sizeOf($oldContent) > 1){
			$fallbacklink = 'http://www.flickr.com/slideShow/index.gne?' . $content;	
			return '<iframe src="'.$fallbacklink.'" frameBorder="0" width="'.$atts['width'].'" height="'.$atts['height'].'" scrolling="no"></iframe>';
		}
		
		return '<span class="tsbox tsflash" style="width:' . $atts['width'] . 'px;height:' . $atts['height'] . 'px;"><span id="' . $objectid . '"><em>' . sprintf( __('Please <a href="%1$s">enable Javascript</a> and <a href="%2$s">Flash</a> to view this %3$s video.', 'flamingtext'), 'http://www.google.com/support/bin/answer.py?answer=23852', 'http://www.adobe.com/shockwave/download/download.cgi?P1_Prod_Version=ShockwaveFlash', 'Flash' ) . '</em></span></span>';
	}
	
	// Return a link to the post for use in the feed
	function postlink() {
		global $post;

		if ( empty($post->ID) ) return ''; // This should never happen (I hope)

		$text = ( !empty($this->settings['customfeedtext']) ) ? $this->settings['customfeedtext'] : '<em>' . __( 'Click here to view the embedded slideshow.', 'flamingtext' ) . '</em>';

		return apply_filters( 'ts_feedoutput', '<a href="' . get_permalink( $post->ID ) . '">' . $text . '</a>' );
	}
	
	// WordPress' js_escape() won't allow <, >, or " -- instead it converts it to an HTML entity. This is a "fixed" function that's used when needed.
	function js_escape($text) {
		$safe_text = addslashes($text);
		$safe_text = preg_replace('/&#(x)?0*(?(1)27|39);?/i', "'", stripslashes($safe_text));
		$safe_text = preg_replace("/\r?\n/", "\\n", addslashes($safe_text));
		$safe_text = str_replace('\\\n', '\n', $safe_text);
		return apply_filters('js_escape', $safe_text, $text);
	}

	// Replaces tabs, new lines, etc. to decrease the characters
	function StringShrink( $string ) {
		if ( empty($string) ) return $string;
		return preg_replace( "/\r?\n/", ' ', str_replace( "\t", '', $string ) );
	}
	
	// Reverse the parts we care about (and probably some we don't) of wptexturize() which gets applied before shortcodes
	function wpuntexturize( $text ) {
		$find = array( '&#8211;', '&#8212;', '&#215;', '&#8230;', '&#8220;', '&#8217;s', '&#8221;', '&#038;' );
		$replace = array( '--', '---', 'x', '...', '``', '\'s', '\'\'', '&' );
		return str_replace( $find, $replace, $text );
	}

	function get_presets() {
		$presetstring = "";
		foreach ($this->presets as $preset) {
			$presetstring = $presetstring.$preset[preset]."\t".$preset[querystring]."\t";
		}
		return trim($presetstring);
	}
}

// Start this plugin once all other plugins are fully loaded
add_action( 'plugins_loaded', 'FlamingText' ); 

// Load text domain
load_plugin_textdomain('flamingtext', null, dirname( plugin_basename( __FILE__ ) ) . '/languages/');

function FlamingText() { global $FlamingText; $FlamingText = new FlamingText(); }

// Initial presets table to db
function install_ft_tables() {
	global $wpdb;
	$table_name = $wpdb->prefix."ft_presets";
	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
		$sql = "CREATE TABLE ".$table_name." (
				userId bigint(20) unsigned NOT NULL,
 				preset varchar(20) NOT NULL,
				querystring VARCHAR(1000) NOT NULL,
  				PRIMARY KEY (userId, preset))
				DEFAULT CHARSET=utf8;";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
		add_option("ft_db_version", $ft_db_version);
/* 
		$wpdb->query("insert into wp_ft_presets
			 select user_id, 'Chrominium', 'script=chrominium-logo&fontname=Ethnocentric&fontsize=25&text=your text'
			 from wp_usermeta 
			 where meta_key = 'rich_editing'
		       	 and meta_value = 'true'");
		$wpdb->query("insert into wp_ft_presets
			 select user_id, 'Flaming', 'script=flaming-logo&fontname=futura_poster&fontsize=50&text=your text' 
			 from wp_usermeta 
			 where meta_key = 'rich_editing'
		       	 and meta_value = 'true'");
		$wpdb->query("insert into wp_ft_presets
			 select user_id, 'Glow', 'script=alien-glow-logo&fontname=futura_poster&fontsize=40&text=your text'
			 from wp_usermeta 
			 where meta_key = 'rich_editing'
		       	 and meta_value = 'true'");
		$wpdb->query("insert into wp_ft_presets
			 select user_id, 'Greyman', 'script=greyman&fontname=arnoldboecklin&fontsize=40&text=your text' 
			 from wp_usermeta 
			 where meta_key = 'rich_editing'
		       	 and meta_value = 'true'");
*/
	}
}

// Drop table when plugin gets deleted
function uninstall_ft_tables() {
	global $wpdb, $ft_db_version;
	$ft_db_version = '0.1';
	$table_name = $wpdb->prefix."ft_presets";
	if($wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name) {
		$sql = "DROP TABLE ".$table_name;
		$wpdb->query($sql);
		delete_option("ft_db_version");
	}
}	

register_activation_hook(__FILE__, 'install_ft_tables');
register_uninstall_hook(__FILE__, 'uninstall_ft_tables');
?>
