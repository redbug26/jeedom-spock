<?php

error_reporting(E_ERROR | E_WARNING | E_PARSE);

libxml_use_internal_errors(true);

function xml_attribute($object, $attribute) {
	if (isset($object[$attribute])) {
		return (string) $object[$attribute];
	}
}

function isDomoticz($ip) {
	$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

	socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => 0, 'usec' => 100));
	socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array('sec' => 0, 'usec' => 100));

	$timeout = 0.1;
	$port = 8080;

	if (@fsockopen($ip, $port, $err, $err_string, $timeout)) {

		$url = "http://" . $ip . ":8080/json.htm?type=command&param=getSunRiseSet";

		// echo "(" . $url . ")";

		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, false);

		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, 1000); // Give up after 2s
		curl_setopt($ch, CURLOPT_TIMEOUT_MS, 1000); // Give up after 2s

		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.0; en-US; rv:1.7.12) Gecko/20050915 Firefox/1.0.7");

		$output = curl_exec($ch);

		curl_close($ch);

		if ($output == "") {
			return false;
		}

		$json = json_decode($output);

		if ($json->title == "") {
			return false;
		}

		return true;
	}
	return false;
}

for ($ipLong = ip2long("172.16.130.1"); $ipLong <= ip2long("172.16.131.255"); $ipLong++) {
	$ip = long2ip($ipLong);

	// echo "\nTesting ip: " . $ip . " - ";

	if (isDomoticz($ip)) {
		$row["ip"] = $ip;
		$row["logicalId"] = "domoticz";

		$rows[] = $row;
	}

	// exit;
	flush();

}

echo json_encode($rows);

?>