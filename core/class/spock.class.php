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

/* * ***************************Includes********************************* */
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
require_once dirname(__FILE__) . '/../../3rdparty/spock.inc.php';

class spock extends eqLogic {

	/*     * *************************Attributs****************************** */

	/*     * ***********************Methode static*************************** */

	/*
	 * Fonction exécutée automatiquement toutes les minutes par Jeedom
	public static function cron() {

	}
	 */

	/*
	 * Fonction exécutée automatiquement toutes les heures par Jeedom
	public static function cronHourly() {

	}
	 */

	/*
	 * Fonction exécutée automatiquement tous les jours par Jeedom
	public static function cronDayly() {

	}
	 */

	/*     * *********************Méthodes d'instance************************* */

	public function preInsert() {

	}

	public function postInsert() {

	}

	public function preSave() {

	}

	public function postSave() {

	}

	public function preUpdate() {

	}

	function addCommandKey($key, $name, $icon = "", $visible = true) {

		$logicalId = "KEY_" . $key;

		$ZeebaseCmd = "";
		foreach ($this->getCmd() as $liste_cmd) {
			if ($liste_cmd->getLogicalId() == $logicalId) {
				$ZeebaseCmd = $liste_cmd;
				break;
			}
		}

		log::add('spock', "debug", "add commandkey");

//      $ZeebaseCmd = spock::byLogicalId($logicalId, 'spock');
		if (!is_object($ZeebaseCmd)) {
			log::add('spock', "debug", "cmd not found");

			$ZeebaseCmd = new spockCmd();
			$ZeebaseCmd->setLogicalId($logicalId);

			log::add('spock', "debug", "name: " . $name);
			log::add('spock', "debug", "logicalId: " . $logicalId);

			$ZeebaseCmd->setName($name);
			$ZeebaseCmd->setType('action');
			$ZeebaseCmd->setSubType('other');

			$ZeebaseCmd->setOrder($this->cmd_order);
			$this->cmd_order++;

			$ZeebaseCmd->setEqLogic_id($this->getId());

			$ZeebaseCmd->setConfiguration('commandType', "key");
			$ZeebaseCmd->setConfiguration('commandName', $key);

			$ZeebaseCmd->setIsVisible($visible ? 1 : 0);

			if ($icon != "") {
				$ZeebaseCmd->setDisplay('icon', '<i class="fa ' . $icon . '"></i>');
			}

			$ZeebaseCmd->save();
		}
	}

	public function postUpdate() {

		$this->cmd_order = 0;

		$this->addCommandKey("POWER", "power", "fa-power-off");
		$this->addCommandKey("AUX_INPUT", "aux");

		$this->addCommandKey("LOCK", "lock", "fa-lock");

		$this->addCommandKey("PLAY", "play", "fa-play");
		$this->addCommandKey("PAUSE", "pause", "fa-pause");

		$this->addCommandKey("VOLUME_UP", "vol up", "");
		$this->addCommandKey("VOLUME_DOWN", "vol down", "");

		$this->addCommandKey("PREV_TRACK", "prev track", "fa-fast-backward");
		$this->addCommandKey("NEXT_TRACK", "next track", "fa-fast-forward");

	}

	public function preRemove() {

	}

	public function postRemove() {

	}

	/*     * **********************Getteur Setteur*************************** */
}

class spockCmd extends cmd {
	/*     * *************************Attributs****************************** */

	/*     * ***********************Methode static*************************** */

	/*     * *********************Methode d'instance************************* */

	/*
	 * Non obligatoire permet de demander de ne pas supprimer les commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
	public function dontRemoveCmd() {
	return true;
	}
	 */

	public function execute($_options = array()) {
		$spock = $this->getEqLogic();

		$spock_ip = $spock->getConfiguration('addr');
		$username = $spock->getConfiguration('username');
		$password = $spock->getConfiguration('password');

		$commandType = $this->getConfiguration('commandType');
		$commandName = $this->getConfiguration('commandName');

		if ($commandType == "key") {
			sendKeyCommand($spock_ip, $commandName, $username, $password);
		}

	}

	/*     * **********************Getteur Setteur*************************** */
}

?>
