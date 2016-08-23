<?php

/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */

try {
	require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
	include_file('core', 'authentification', 'php');

	if (!isConnect('admin')) {
		throw new Exception(__('401 - Accès non autorisé', __FILE__));
	}

	if (init('action') == 'syncEqLogicWithRazberry') {
		dbm6000::syncEqLogicWithRazberry();
		ajax::success();
	}

	if (init('action') == 'execEngine') {
		$engine = init('name');

		$cmd = "php " . dirname(__FILE__) . '/../../3rdparty/engine/' . $engine . ".php";

		// echo $cmd;

		$handle = popen($cmd . " 2>&1", "r");
		$jsonData .= fread($handle, 2096);
		pclose($handle);

		$rows = array();

		$items = json_decode($jsonData);

		foreach ($items as $item) {

			try {
				$market = market::byLogicalId($item->logicalId);

				$default_image = 'core/img/no_image.gif';

				if ($market->getImg('icon') != "") {
					$urlPath = config::byKey('market::address') . '/' . $market->getImg('icon');
					$data = file_get_contents($urlPath);
					if (strlen($data) == 0) {
						$urlPath = config::byKey('market::address') . '/core/img/no_image.gif';
					}
				} else {
					$urlPath = config::byKey('market::address') . '/core/img/no_image.gif';
				}

				$iconImg = '<img class="lazy" src="' . $urlPath . '" height="70" width="63" />';

				if (file_exists(dirname(__FILE__) . '/../../../' . $item->logicalId)) {
					$action = sprintf('<a class="btn btn-success" href="index.php?v=d&p=plugin&id=%s" style="margin : 5px;"><i class="fa fa-check"></i> Configuration</a>', $item->logicalId);
				} else {
					$action = sprintf('<a class="btn btn-default btn-sm tooltips" title="Récupérer du market" style="width : 100%%" onclick="openMarket(%s)"><i class="fa fa-shopping-cart"></i></a>', $market->getId());
				}

				$name = $market->getName();
				$description = $market->getDescription();

			} catch (Exception $e) {
				$urlPath = config::byKey('market::address') . '/core/img/no_image.gif';

				$iconImg = '<img class="lazy" src="' . $urlPath . '" height="70" width="63" />';
				$action = "";

				$name = $item->logicalId;
				$description = "";
			}

			$row = array();
			$row["html"] = sprintf('<tr><th>%s</th><th>%s</th><th>%s<br/><small>%s</small></th><th>%s</th><th>%s</th></tr>', $item->ip, $iconImg, $name, $description, $item->description, $action);
			$row["logicalId"] = $item->logicalId;
			$row["ip"] = $item->ip;
			$rows[] = $row;
		}

		echo json_encode($rows);
		exit;
	}

	throw new Exception(__('Aucune méthode correspondante à : ', __FILE__) . init('action'));
	/*     * *********Catch exeption*************** */
} catch (Exception $e) {
	ajax::error(displayExeption($e), $e->getCode());
}
?>
