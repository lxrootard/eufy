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
    require_once dirname(__FILE__) . '/../class/eufyUtils.php';
    require_once dirname(__FILE__) . '/../class/eufy.class.php';
    include_file('core', 'authentification', 'php');

    if (!isConnect('admin')) {
        throw new Exception(__('401 - Accès non autorisé', __FILE__));
    }

  /* Fonction permettant l'envoi de l'entête 'Content-Type: application/json'
    En V3 : indiquer l'argument 'true' pour contrôler le token d'accès Jeedom
    En V4 : autoriser l'exécution d'une méthode 'action' en GET en indiquant le(s) nom(s) de(s) action(s$
  */

    ajax::init(array('uploadPicture'));

    if (init('action') == 'sync') {
	eufy::refreshAllDevices();
    	ajax::success();
    }

    if (init('action') == 'disco') {
        eufy::discoverItfs();
        ajax::success();
    }

    if (init('action') == 'uploadPicture') {
        $file = $_FILES['file'];
//        log::add('eufy', 'debug', 'ajax: uploadPicture for: '. init('model') . ' icon: ' . $file['name']);
        eufyUtils::uploadPicture ($file,init('model'));
        ajax::success();
    }

    if (init('action') == 'getPicture') {
        $model = init('model');
//	log::add('eufy', 'debug', 'ajax: getPicture for: '. $model);
	$img = eufyUtils::getPicture($model);
        ajax::success($img);
    }

    if (init('action') == 'resetPicture') {
//        log::add('eufy', 'debug', 'ajax: resetPicture for: '. init('model'));
        eufyUtils::resetPicture (init('model'));
        ajax::success();
    }

    if (init('action') == 'installEufy') {
	eufyUtils::installDocker();
	eufyUtils::setupContainer('install');
	eufyUtils::setupContainer('start');
	ajax::success();
    }
    if (init('action') == 'restartEufy') {
        eufyUtils::setupContainer('stop');
        eufyUtils::setupContainer('start');
        ajax::success();
    }
    if (init('action') == 'uninstallEufy') {
	eufyUtils::setupContainer('stop');
        eufyUtils::setupContainer('uninstall');
        ajax::success();
    }
    if (init('action') == 'upgradeEufy') {
        eufyUtils::setupContainer('stop');
	eufyUtils::setupContainer('uninstall');
	eufyUtils::setupContainer('install');
	eufyUtils::setupContainer('start');
        ajax::success();
    }
    if (init('action') == 'testEufy') {
	eufyUtils::checkContainer();
        eufyUtils::testService();
        ajax::success();
    }
    throw new Exception(__('Aucune méthode correspondante à', __FILE__) . ' : ' . init('action'));
}
catch (Exception $e) {
    ajax::error('eufy.ajax.php: ' . displayException($e), $e->getCode());
}
