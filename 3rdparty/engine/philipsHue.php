<?php

require "lib/phpupnp.class.php";

$allips = array();

$upnp = new phpUPnP();

$services = $upnp->mSearch();
foreach ($services as $service) {
	$xmlstr = @file_get_contents($service["location"]);

	try {
		$data = new SimpleXMLElement($xmlstr);

		$modelURL = $data->device->modelURL->__toString();

		if (($modelURL == "http://www.meethue.com") && ($allips[$service["ip"]] != "1")) {

			$allips[$service["ip"]] = 1;

			$row["description"] = $data->device->friendlyName->__toString();

			$row["ip"] = $service["ip"];
			$row["logicalId"] = "philipsHue";

			$rows[] = $row;
		}

	} catch (Exception $e) {
	}

}

echo json_encode($rows);

?>