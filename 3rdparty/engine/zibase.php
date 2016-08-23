<?php

// https://market.jeedom.fr/filestore/market/plugin/images/Zeebase_icon.png
// https://market.jeedom.fr/filestore/market/plugin/images/Zibasedom_icon.png
// https://market.jeedom.fr/filestore/market/plugin/images/Jeebase_icon.png

//Create a UDP socket
if (!($sock = socket_create(AF_INET, SOCK_DGRAM, 0))) {
	$errorcode = socket_last_error();
	$errormsg = socket_strerror($errorcode);

	die("Couldn't create socket: [$errorcode] $errormsg \n");
}

// Bind the source address
if (!socket_bind($sock, "0.0.0.0", 19999)) {
	$errorcode = socket_last_error();
	$errormsg = socket_strerror($errorcode);

	die("Could not bind socket : [$errorcode] $errormsg \n");
}

socket_set_option($sock, SOL_SOCKET, SO_BROADCAST, 1);

$header = "ZSIG";
$command = 8;
$reserved1 = null;
$zibaseId = null;
$reserved2 = null;
$param1 = 0;
$param2 = 0;
$param3 = 0;
$param4 = 0;
$myCount = 0;
$yourCount = 0;
$message = null;

$data = $header;

$ltemp = $command;
$data .= pack("n", $ltemp);

$strTemp = $reserved1;
$data .= str_pad($strTemp, 16, chr(0));

$strTemp = $zibaseId;
$data .= str_pad($strTemp, 16, chr(0));

$strTemp = $reserved2;
$data .= str_pad($strTemp, 12, chr(0));

$ltemp = $param1;
$data .= pack("N", $ltemp);

$ltemp = $param2;
$data .= pack("N", $ltemp);

$ltemp = $param3;
$data .= pack("N", $ltemp);

$ltemp = $param4;
$data .= pack("N", $ltemp);

$ltemp = $myCount;
$data .= pack("n", $ltemp);

$ltemp = $yourCount;
$data .= pack("n", $ltemp);

if ($message != null) {
	$strTemp .= $message;
	$data .= str_pad($strTemp, 96, chr(0));
}

socket_sendto($sock, $data, strlen($data), 0, "255.255.255.255", "49999");

while (1) {
	$r = socket_recvfrom($sock, $buf, 512, 0, $remote_ip, $remote_port);

	$row["ip"] = $remote_ip;
	$row["logicalId"] = "jeebase";
	$rows[] = $row;

	$row["ip"] = $remote_ip;
	$row["logicalId"] = "Zibasedom";
	$rows[] = $row;

	$row["ip"] = $remote_ip;
	$row["logicalId"] = "Zeebase";
	$rows[] = $row;

	break;
}

socket_close($sock);

echo json_encode($rows);

?>