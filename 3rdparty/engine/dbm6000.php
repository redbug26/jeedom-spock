<?php

echo json_encode($rows);exit;

error_reporting(E_ERROR | E_WARNING | E_PARSE);

libxml_use_internal_errors(true);

function xml_attribute($object, $attribute) {
	if (isset($object[$attribute])) {
		return (string) $object[$attribute];
	}
}

function scanIP($ip) {
	$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

	socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => 0, 'usec' => 100));
	socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array('sec' => 0, 'usec' => 100));

	$timeout = 0.1;
	$port = 8080;

	if (@fsockopen($ip, $port, $err, $err_string, $timeout)) {

		return true;
	}
	return false;
}

for ($ipLong = ip2long("172.16.130.1"); $ipLong <= ip2long("172.16.131.255"); $ipLong++) {
	$ip = long2ip($ipLong);

	// echo "\nTesting ip: " . $ip . " - ";

	scanIP($ip);
}

$cmd = "arp -an";

$handle = popen($cmd . " 2>&1", "r");
while (!feof($handle)) {
	$buffer = fgets($handle);

	$column = explode(" ", $buffer);

	$ip = str_replace(")", "", str_replace("(", "", $column[1]));
	$mac = strtolower($column[3]);

	if (substr($mac, 0, 8) == "00:1d:eb") {
		$row["ip"] = $ip;
		$row["logicalId"] = "dbm6000";

		$rows[] = $row;
	}
}
pclose($handle);

echo json_encode($rows);

?>