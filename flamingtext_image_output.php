<?php 
require_once('./../../../wp-admin/admin.php');

	function image_output() {
		if(!function_exists("curl_init")) error_log("cURL extension is not installed");
		$toSave = false;
		list($saveOrPreview, $querystring) = split("&", $_SERVER["QUERY_STRING"], 2);
		if ($saveOrPreview == "save") $toSave = true;
		
		$url = "http://www.flamingtext.com/net-fu/image_output.cgi?".$_SERVER["QUERY_STRING"];
		$url.="&wp=Ax7_b";
		$ch = curl_init($url);

                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                $r=curl_exec($ch);
                curl_close($ch);

		if ($toSave) {
			if (preg_match("/\"src\"\:\"(.*\/coollogo_com-(.*\.(?:png|gif|jpg)))\"/", $r, $matches)==1) {
				$image = $matches[1];
                        	$filename = $matches[2];
       	                 	$dir = wp_upload_dir();
                        	$imgPath = $dir[path]."/flamingtext_com_".$filename;
                        	$imgUrl = $dir[url]."/flamingtext_com_".$filename;

                        	while (file_exists($imgPath)) {
                                	$imgPath = $imgPath."(1)";
                                	$imgUrl = $imgUrl."(1)";         
                        	}
				
				$ih=curl_init($image);
                		curl_setopt($ih, CURLOPT_RETURNTRANSFER, true);
				$i=curl_exec($ih);
                        	$fh = fopen($imgPath, 'w') or error_log("can't open file");
                        	fwrite($fh, $i);
                        	fclose($fh);
                        	return "{\"src\":\"".$imgUrl."\"}";     
			} 
                }
		if ($r) return $r;
		return "{\"error\":\"no response from server, please try again later.\"}";
	}
?>
<?php echo image_output();?>
