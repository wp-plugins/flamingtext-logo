<?php
require_once('./../../../wp-admin/admin.php');
	// Decide udpate or delete preset
	function decider() {
		list($mod, $string) = split("&", $_SERVER["QUERY_STRING"], 2);	
		if ($mod=='save') {
			return savePreset($string);
		} else if ($mod=='delete') {
			return deletePreset($string);
		}
		return "{\"error\":\"".__('invalid QUERY_STRING given, please DO NOT modify FlamingText plugin without authorization!','flamingtext')."\"}";
	}

	// Deleting preset
	function deletePreset($string) {
		list($userId, $preset) = split("&", $string, 2);
		if (!$userId) return "{\"error\":\"no userId for deleting preset\"}";
		if (!$preset) return "{\"error\":\"no preset name to delete\"}";

		$preset = preg_replace('/%([0-9a-f]{2})/ie', "chr(hexdec('\\1'))", $preset);
		global $wpdb;
                $sql = $wpdb->prepare("DELETE FROM ".$wpdb->prefix."ft_presets 
				WHERE userId = %d AND preset = %s",
                                $userId, $wpdb->escape($preset), $wpdb->escape($querystring));
		$wpdb->show_errors();
                $res = $wpdb->query($sql);

		if ($res==1) return "{\"sts\":\"".__('preset deleted successfully.','flamingtext')."\"}";
		else if ($res==0) return "{\"sts\":\"".__('no preset to delete.','flamingtext')."\"}";
		else return "{\"error\":\"".__('errors occur during delete preset from wordpress database.','flamingtext')."\"}";

	}

        // Inserting/updating preset
        function savePreset($string) {
		list($userId, $preset, $querystring) = split("&", $string, 3);
		if (!$userId) return "{\"error\":\"no userId for saving preset\"}";
		if (!$preset) return "{\"error\":\"no preset name for save\"}";
		if (!$querystring) return "{\"error\":\"no querystring for save\"}";

		$preset = preg_replace('/%([0-9a-f]{2})/ie', "chr(hexdec('\\1'))", $preset);
		$querystring = preg_replace('/%([0-9a-f]{2})/ie', "chr(hexdec('\\1'))", $querystring);
                global $wpdb;
                $sql = $wpdb->prepare("INSERT INTO ".$wpdb->prefix."ft_presets (
                                userId, preset, querystring)
                                VALUES (%d, %s, %s)
				ON DUPLICATE KEY UPDATE querystring = %s",
                                $userId, $wpdb->escape($preset), $wpdb->escape($querystring), $wpdb->escape($querystring));
		$wpdb->show_errors();
                $res = $wpdb->query($sql);

		if ($res==1) return "{\"sts\":\"".__('preset saved successfully.','flamingtext')."\"}";
		else if ($res==0) return "{\"sts\":\"".__('preset updated successfully.','flamingtext')."\"}";
		else return "{\"error\":\"".__('errors occur during save preset to wordpress database.','flamingtext')."\"}";
	}
?>
<?php echo decider();?>
