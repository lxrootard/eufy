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

  /* Fonction permettant l'envoi de l'entête 'Content-Type: application/json'
    En V3 : indiquer l'argument 'true' pour contrôler le token d'accès Jeedom
    En V4 : autoriser l'exécution d'une méthode 'action' en GET en indiquant le(s) nom(s) de(s) action(s$
  */
    ajax::init();

    if (init('action') == 'sync') {
	$params = array('command' => 'syncDevices');
	eufy::sendToDaemon($params);
    	ajax::success();
    }
    if (init('action') == 'installEufy') {
	//eufy::executeAsync ('installImage');
	eufy::installDocker();
	eufy::setupContainer('install');
	eufy::setupContainer('start');
	ajax::success();
    }
    if (init('action') == 'restartEufy') {
        eufy::setupContainer('stop');
        eufy::setupContainer('start');
        ajax::success();
    }
    if (init('action') == 'uninstallEufy') {
	eufy::setupContainer('stop');
        eufy::setupContainer('uninstall');
        ajax::success();
    }
    if (init('action') == 'upgradeEufy') {
        eufy::setupContainer('stop');
	eufy::setupContainer('uninstall');
	eufy::setupContainer('install');
	eufy::setupContainer('start');
        ajax::success();
    }
    if (init('action') == 'testEufy') {
	eufy::checkContainer();
        eufy::testService();
        ajax::success();
    }
    throw new Exception(__('Aucune méthode correspondante à', __FILE__) . ' : ' . init('action'));
}
catch (Exception $e) {
    ajax::error('eufy.ajax.php: ' . displayException($e), $e->getCode());
}
