<?php

require "lib/phpupnp.class.php";

$upnp = new phpUPnP();

$services = $upnp->mSearch();
foreach ($services as $service) {
	$st = $service["st"];

	if (($st == "urn:schemas-upnp-org:device:MediaServer:1") || ($st == "urn:schemas-upnp-org:device:MediaRenderer:1")) {
		$xmlstr = @file_get_contents("http://" . $service["ip"] . ":8090/info");

		try {
			$data = new SimpleXMLElement($xmlstr);

			$margeURL = $data->margeURL->__toString();

			if (($margeURL == "https://streaming.bose.com") && ($allips[$service["ip"]] != "1")) {

				$allips[$service["ip"]] = 1;

				$row["description"] = $data->name->__toString();

				$row["ip"] = $service["ip"];
				$row["logicalId"] = "soundtouch";

				$rows[] = $row;
			}

		} catch (Exception $e) {
		}

	} else {
		// echo $service["ip"] . ": " . $service["st"] . "\n";
	}
}

echo json_encode($rows);

?>