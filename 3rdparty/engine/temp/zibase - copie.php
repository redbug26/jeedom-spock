<?php

exit;

libxml_use_internal_errors(true);

function xml_attribute($object, $attribute) {
	if (isset($object[$attribute])) {
		return (string) $object[$attribute];
	}
}

function isZibase($ip) {

	$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

	socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => 0, 'usec' => 100));
	socket_set_option($socket, SOL_SOCKET, SO_SNDTIMEO, array('sec' => 0, 'usec' => 100));

	$connection = @socket_connect($socket, $ip, 80);
	if ($connection) {
		socket_close($socket);
	} else {
		return false; // Pas de connection sur le port 80
	}

	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, "http://" . $ip . "/sensors.xml");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT_MS, 1000); // Give up after 2s
	curl_setopt($ch, CURLOPT_TIMEOUT_MS, 1000); // Give up after 2s

	$xmlstr = curl_exec($ch);
	curl_close($ch);

	try {
		$data = new SimpleXMLElement($xmlstr);

		if ($data->plat == "zibase.net") {
			return true;
		}
	} catch (Exception $e) {
		//	echo "- $ip - ($xmlstr)\n";
	}

	return false;
}

$tempFile = "/tmp/output.xml";
$xmlstr = file_get_contents($tempFile);

$data = new SimpleXMLElement($xmlstr);

foreach ($data->host as $host) {
	$ip = "";

	foreach ($host->address as $address) {
		if (xml_attribute($address, 'addrtype') == "ipv4") {
			$ip = xml_attribute($address, 'addr');
		}
	}

	// $ip = "172.16.131.153";

	if (isZibase($ip)) {
		echo $ip . ": ok ***************************\n";
	} else {
		echo $ip . ": not ok\n";

	}

	// exit;
	flush();

}

exit;

?>