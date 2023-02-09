<?php
try {
     require_once dirname(__FILE__) . "/../../../../core/php/core.inc.php";

     if (!jeedom::apiAccess(init('apikey'), 'eufy')) {
        	echo __('Vous n\'etes pas autorisé à effectuer cette action', __FILE__);
        	die();
     }
     if (init('test') != '') {
        	echo 'OK';
        	die();
     }
     $result = json_decode(file_get_contents("php://input"), true);
     if (!is_array($result)) 
        	die();

     if(isset($result['type'])) {
          // lxrootard check connexion Eufy-WS et création des types Station et WebCam
          if ($result['type'] == 'connexion') {
              log::add('eufy', 'debug', '>>> Connexion info received from daemon');
	      eufy::setOnlineStatus ($result['online']);
	  }

	  if ($result['type'] == 'stations') {
              log::add('eufy', 'debug', '>>> Stations received from daemon');
              eufy::syncDevices($result['stations'],'Station');
          }

          if ($result['type'] == 'devices') {
              log::add('eufy', 'debug', '>>> Devices received from daemon');
              eufy::syncDevices($result['devices'],'WebCam');
          }

          if ($result['type'] == 'event') {
              log::add('eufy', 'debug', '>>> Event received from daemon: serialNumber: '. $result['serialNumber'] . ', property: ' 
		. $result['property'] . ', value: ' . $result['value']);
	      eufy::updateDeviceInfo($result['serialNumber'], $result['property'], $result['value']);
          }
    }
} 
catch (Exception $e) {
    log::add('eufy', 'error', displayException($e));
}
?>
