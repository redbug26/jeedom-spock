<?php

$tempFile = "/tmp/output.xml";

$network = "172.16.130.0/23";
shell_exec("sudo nmap -sn -oX " . $tempFile . " " . $network);

$xmlstr = file_get_contents($tempFile);

$data = new SimpleXMLElement($xmlstr);

print_r($data);

?>