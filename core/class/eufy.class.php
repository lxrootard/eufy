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
require_once __DIR__  . '/../../../../core/php/core.inc.php';

class eufy extends eqLogic {

  public static function deamonRunning() {
		return true;
  }

  public static function deamon_info() {
    $rc = array();
    $rc['log'] = __CLASS__;
    $rc['state'] = 'nok';
    $pid_file = jeedom::getTmpFolder(__CLASS__) . '/deamon.pid';
    if (file_exists($pid_file)) {
        if (@posix_getsid(trim(file_get_contents($pid_file)))) {
            $rc['state'] = 'ok';
        } else {
            shell_exec(system::getCmdSudo() . 'rm -rf ' . $pid_file . ' 2>&1 > /dev/null');
        }
    }
    $rc['launchable'] = 'ok';
    $containerip = config::byKey('containerip', __CLASS__); 
    $containerport = config::byKey('containerport', __CLASS__); 
    if ($containerip == '') {
        $rc['launchable'] = 'nok';
        $rc['launchable_message'] = __('L\'IP du container n\'est pas configurée', __FILE__);
    }
    elseif ($containerport == '') {
        $rc['launchable'] = 'nok';
        $rc['launchable_message'] = __('Le port n\'est pas configuré', __FILE__);
    }
    return $rc;
  }

  public static function deamon_start() {
    self::deamon_stop();
    $deamon_info = self::deamon_info();
    if ($deamon_info['launchable'] != 'ok') {
        throw new Exception(__('Veuillez vérifier la configuration', __FILE__));
    }

    if (! eufy::checkContainer()) {
//       log::add(__CLASS__, 'error', __('Container Eufy non démarré', __FILE__), 'unableStartDeamon');
        event::add('jeedom::alert', array('level' => 'error', 'page' => 'plugin',
                'message' => __('Container Eufy non démarré', __FILE__)));
    //    throw new Exception(__('Container Eufy non démarré!!!', __FILE__));
	return false;
    }

    $path = realpath(dirname(__FILE__) . '/../../resources/eufyd'); // répertoire du démon
    $cmd = 'python3 ' . $path . '/eufyd.py'; // nom du démon
    $cmd .= ' --loglevel ' . log::convertLogLevel(log::getLogLevel(__CLASS__));
    $cmd .= ' --socketport ' . config::byKey('socketport', __CLASS__, '60600');
    $cmd .= ' --callback ' . network::getNetworkAccess('internal', 'proto:127.0.0.1:port:comp') . '/plugins/eufy/core/php/jeeeufy.php';
    $cmd .= ' --containerip "' . trim(str_replace('"', '\"', config::byKey('containerip', __CLASS__))) . '"'; // on rajoute les paramètres utiles à votre démon
    $cmd .= ' --containerport "' . trim(str_replace('"', '\"', config::byKey('containerport', __CLASS__))) . '"'; // second parametre
    $cmd .= ' --apikey ' . jeedom::getApiKey(__CLASS__); // l'apikey pour authentifier les échanges suivants
    $cmd .= ' --pid ' . jeedom::getTmpFolder(__CLASS__) . '/deamon.pid'; // et on précise le chemin vers le pid file (ne pas modifier)
    log::add(__CLASS__, 'info', 'Lancement démon');
    $result = exec($cmd . ' >> ' . log::getPathToLog('eufy_daemon') . ' 2>&1 &'); // nommer votre log en commençant par le pluginid pour que le fichier apparaisse dans la page de config
    $i = 0;
    while ($i < 10) {
        $deamon_info = self::deamon_info();
        if ($deamon_info['state'] == 'ok')
            break;
        sleep(1);
        $i++;
    }
    if ($i >= 10) {
        log::add(__CLASS__, 'error', __('Impossible de lancer le démon, vérifiez le log', __FILE__), 'unableStartDeamon');
        return false;
    }
    sleep(1);
    if (! eufy::isOnline()) {
        event::add('jeedom::alert', array('level' => 'error', 'page' => 'plugin',
                'message' => __('Container Eufy non connecté', __FILE__)));
        // throw new Exception(__('Container Eufy non connecté !!!', __FILE__));
	return false;
    }
    eufy::refreshAllDevices();

    message::removeAll(__CLASS__, 'unableStartDeamon');
    log::add(__CLASS__, 'info', 'Démon Eufy lancé');
    return true;
  }

  public static function deamon_stop() {
    $pid_file = jeedom::getTmpFolder(__CLASS__) . '/deamon.pid'; // ne pas modifier
    if (file_exists($pid_file)) {
        $pid = intval(trim(file_get_contents($pid_file)));
        system::kill($pid);
    }
    system::kill('eufyd.py');
    cache::set('eufy::container_ok',false);
    cache::set('eufy::online',false);
    sleep(1);
  }


  public static function sendToDaemon($params) {
	$deamon_info = self::deamon_info();
	if ($deamon_info['state'] != 'ok') {
		throw new Exception("Le démon n'est pas démarré");
	}
	$params['apikey'] = jeedom::getApiKey(__CLASS__);
        $payLoad = json_encode($params);
	log::add(__CLASS__, 'debug', "sendToDaemon: " . $payLoad);

	$socket = socket_create(AF_INET, SOCK_STREAM, 0);
	socket_connect($socket, '127.0.0.1', config::byKey('socketport', __CLASS__, '60600'));
	socket_write($socket, $payLoad, strlen($payLoad));
	socket_close($socket);
  }

  public static function syncDevices($message, $type){
    log::add(__CLASS__, 'info', 'Sync devices');
    log::add(__CLASS__, 'debug', $message);
    if (empty($message))
	return;

    $message = str_replace(": True", ": \"True\"", $message);
    $message = str_replace(": False", ": \"False\"", $message);
    $message = str_replace("'", "\"", $message);
    $jsonObjArray = json_decode($message, true);
    $jsonObj = json_decode($message);

    $deviceId = 0;

    $serialStations = [];
    foreach($jsonObj as $device)
    {
      $eqLogic = eqLogic::byLogicalId($device->serialNumber, __CLASS__);

      if (!is_object($eqLogic)) {
        log::add(__CLASS__, 'info', 'Creating (' . $device->name . ' - ' . $device->serialNumber . ')');
        $eqLogic = new self();
        $eqLogic->setLogicalId($device->serialNumber);
       	$eqLogic->setName($device->name);
     	$eqLogic->setEqType_name(__CLASS__);
     	$eqLogic->setIsEnable(1);
        $eqLogic->setCategory('security', 1);

	if ($type == 'Station') 
		$station = $device->serialNumber;
	else
		$station = $device->stationSerialNumber;
	$eqLogic->setConfiguration('eufyType', $type); // WebCam ou Station 
        $eqLogic->setConfiguration('eufyName', $device->name); // nom app Eufy
        $eqLogic->setConfiguration('model', $device->model);
        $eqLogic->setConfiguration('serialNumber', $device->serialNumber);
        $eqLogic->setConfiguration('stationSerialNumber', $station);
        $eqLogic->setConfiguration('hardwareVersion', $device->hardwareVersion);
	$eqLogic->setConfiguration('softwareVersion', $device->softwareVersion);
	$eqLogic->save();

        try{
          $commandsConfig = eufy::getCommandsFileContent(__DIR__ . '/../config/' . $device->model . '.json');
          $eqLogic->createCommandsFromConfig($commandsConfig, $jsonObjArray[$deviceId]);
        }
        catch(Exception $e) {
          log::add(__CLASS__, 'warning', $e);
        }

        if(!in_array($device->stationSerialNumber, $serialStations))
          $serialStations[] = $device->stationSerialNumber;

        $eqLogic->refreshDevice();
      }
      else
          log::add(__CLASS__, 'debug', 'Device already exists: ' . $device->name . '-' . $device->serialNumber);

      $deviceId = $deviceId + 1;
    }
  }


  public static function refreshAllDevices()
  {
    foreach (self::byType('eufy', true) as $eqLogic)
        $eqLogic->refreshDevice();
  }

  public function refreshDevice()
  {
        $type = $this->getConfiguration('eufyType');
        $serialNumber= $this->getConfiguration('serialNumber');
        log::add(__CLASS__, 'debug', 'Refresh device: ' . $serialNumber . ' type: ' . $type);
        if ($type == 'Station')
                $params = array('command' => 'station.get_properties', 'serialNumber' => $serialNumber);
        else
                $params = array('command' => 'device.get_properties', 'serialNumber' =>  $serialNumber);
        eufy::sendToDaemon($params);
  }

  public static function updateDeviceInfo($serialNumber, $property, $value) 
  {
    $eqLogic = eqLogic::byLogicalId($serialNumber, __CLASS__);
    if (! isset($eqLogic)) {
	log::add(__CLASS__, 'error', $serialNumber . ': eqLogic not found');
	return;
    }
    $cmd = $eqLogic->getCmd('info', $property);
    if (eufy::sendEvent($cmd, $value)) {
        log::add(__CLASS__, 'debug', 'device info updated, property: '. $property);
	if ($property == 'battery')
		$eqLogic->batteryStatus($value);
    }
  }

  public static function sendEvent($cmd, $value){
    if ($cmd->execCmd() != $cmd->formatValue($value)) {
      $cmd->event($value, null);
      return true;
     }
     return false;
  }

  /* helper */
  private static function getCommandsFileContent(string $filePath) {
		if (!file_exists($filePath)) {
			throw new RuntimeException("Fichier de configuration non trouvé:{$filePath}");
		}
		$content = file_get_contents($filePath);
		if (!is_json($content)) {
			throw new RuntimeException("Fichier de configuration incorrecte:{$filePath}");
		}
		return json_decode($content, true);
	}

	public function createCommandsFromConfigFile(string $filePath, string $commandsKey) {
		$commands = self::getCommandsFileContent($filePath);
		$this->createCommandsFromConfig($commands[$commandsKey], null);
	}

	public function createCommandsFromConfig(array $commands, $values) {
		$link_cmds = array();
		foreach ($commands as $cmdDef){
			$cmd = $this->getCmd(null, $cmdDef["logicalId"]);
			if (!is_object($cmd)) {
				log::add(__CLASS__, 'debug', 'create:'.$cmdDef["logicalId"].'/'.$cmdDef["name"]);
				$cmd = new cmd();
				$cmd->setLogicalId($cmdDef["logicalId"]);
				$cmd->setEqLogic_id($this->getId());
				$cmd->setName(__($cmdDef["name"], __FILE__));
				if(isset($cmdDef["isHistorized"])) {
					$cmd->setIsHistorized($cmdDef["isHistorized"]);
				}
				if(isset($cmdDef["isVisible"])) {
					$cmd->setIsVisible($cmdDef["isVisible"]);
				}
				if (isset($cmdDef['template'])) {
					foreach ($cmdDef['template'] as $key => $value) {
						$cmd->setTemplate($key, $value);
					}
				}
			}
			$cmd->setType($cmdDef["type"]);
			$cmd->setSubType($cmdDef["subtype"]);
			if(isset($cmdDef["generic_type"])) {
				$cmd->setGeneric_type($cmdDef["generic_type"]);
			}
			if (isset($cmdDef['display'])) {
				foreach ($cmdDef['display'] as $key => $value) {
					if ($key=='title_placeholder' || $key=='message_placeholder') {
						$value = __($value, __FILE__);
					}
					$cmd->setDisplay($key, $value);
				}
			}
			if(isset($cmdDef["unite"])) {
				$cmd->setUnite($cmdDef["unite"]);
			}

			if (isset($cmdDef['configuration'])) {
				foreach ($cmdDef['configuration'] as $key => $value) {
					$cmd->setConfiguration($key, $value);
				}
			}

			if (isset($cmdDef['value'])) {
				$link_cmds[$cmdDef["logicalId"]] = $cmdDef['value'];
			}

			$cmd->save();

      // Init value
      if(isset($values))
        eufy::sendEvent($cmd, $values[$cmdDef["logicalId"]]);
      elseif (isset($cmdDef['initialValue'])) {
		  		$cmdValue = $cmd->execCmd();
				if ($cmdValue=='') {
					$this->checkAndUpdateCmd($cmdDef["logicalId"], $cmdDef['initialValue']);
				}
			}
		}

		foreach ($link_cmds as $cmd_logicalId => $link_logicalId) {
			$cmd = $this->getCmd(null, $cmd_logicalId);
			$linkCmd = $this->getCmd(null, $link_logicalId);

			if (is_object($cmd) && is_object($linkCmd)) {
				$cmd->setValue($linkCmd->getId());
				$cmd->save();
			}
		}
	}

  // Fonction exécutée automatiquement avant la création de l'équipement
  public function preInsert() {
  }

  // Fonction exécutée automatiquement après la création de l'équipement
  public function postInsert() {
  }

  // Fonction exécutée automatiquement avant la mise à jour de l'équipement
  public function preUpdate() {
  }

  // Fonction exécutée automatiquement après la mise à jour de l'équipement
  public function postUpdate() {
  }

  // Fonction exécutée automatiquement avant la sauvegarde (création ou mise à jour) de l'équipement
  public function preSave() {
  }

  // Fonction exécutée automatiquement après la sauvegarde (création ou mise à jour) de l'équipement
  public function postSave() {
	$refresh = $this->getCmd(null, 'refresh');
	if (!is_object($refresh)) {
		$refresh = new eufyCmd();
		$refresh->setName(__('Rafraichir', __FILE__));
	}
	$refresh->setEqLogic_id($this->getId());
	$refresh->setLogicalId('refresh');
	$refresh->setType('action');
	$refresh->setSubType('other');
	$refresh->save();

        $present = $this->getCmd(null, 'present');
        if (!is_object($present)) {
                $present = new eufyCmd();
                $present->setName(__('Présent', __FILE__));
        }
        $present->setEqLogic_id($this->getId());
        $present->setLogicalId('present');
        $present->setType('info');
        $present->setSubType('binary');
        $present->setTemplate('dashboard', 'line');
        $present->setTemplate('mobile', 'line');
        $present->save();

  }

  // Fonction exécutée automatiquement avant la suppression de l'équipement
  public function preRemove() {
  }

  // Fonction exécutée automatiquement après la suppression de l'équipement
  public function postRemove() {
  }

  /*
  * Permet de crypter/décrypter automatiquement des champs de configuration des équipements
  * Exemple avec le champ "Mot de passe" (password)
  public function decrypt() {
    $this->setConfiguration('password', utils::decrypt($this->getConfiguration('password')));
  }
  public function encrypt() {
    $this->setConfiguration('password', utils::encrypt($this->getConfiguration('password')));
  }
  */

  /*
  * Permet de modifier l'affichage du widget (également utilisable par les commandes)
  public function toHtml($_version = 'dashboard') {}
  */

  /*
  * Permet de déclencher une action avant modification d'une variable de configuration du plugin
  * Exemple avec la variable "param3"
  public static function preConfig_param3( $value ) {
    // do some checks or modify on $value
    return $value;
  }
  */

  /*
  * Permet de déclencher une action après modification d'une variable de configuration du plugin
  * Exemple avec la variable "param3"
  public static function postConfig_param3($value) {
    // no return value
  }
  */

 // lxrootard
  public static function checkContainer()
  {
    $ip = config::byKey('containerip', __CLASS__);
    $port = config::byKey('containerport', __CLASS__);
    log::add(__CLASS__, 'debug', 'Checking container ' . $ip .':' . $port);
    try {
        $conn = @fsockopen($ip, $port);
        if (is_resource($conn)) {
                log::add(__CLASS__, 'debug', 'Service ' . $ip .':' . $port .' listening');
                fclose($conn);
                cache::set('eufy::container_ok',true);
                return true;
        }
    }
    catch (Exception $ex) {}
    log::add(__CLASS__, 'debug', 'Service '. $ip . ':'. $port . ' not responding :(');
    cache::set('eufy::container_ok',false);
    return false;
  }

  public static function isListening()
  {
    $b = cache::byKey('eufy::container_ok')->getValue();
    log::add(__CLASS__, 'debug', 'isListening: ' .  $b);
    return $b;
  }

  public static function setOnlineStatus($s)
  {
	log::add(__CLASS__, 'debug', 'setOnlineStatus: ' . $s);
	if ($s == 'True')
		 cache::set('eufy::online', true);
	else
	 	 cache::set('eufy::online', false);
  }

  public static function isOnline ()
  {
	$online = cache::byKey('eufy::online')->getValue();
        log::add(__CLASS__, 'debug', 'isOnline: ' . $online);
	return $online ;
  }

  // install | uninstall
  public static function setupEufy($action)
  {
	$device = config::byKey('devicename', __CLASS__);
        $user = config::byKey('username', __CLASS__);
	$passwd = config::byKey('password', __CLASS__);
	$port = config::byKey('containerport', __CLASS__);

	$log = __DIR__ . '/../../../../log/eufy_service_setup';
        $script =  __DIR__ . '/../../resources/eufyctl.sh' ;
	$msg = 'Lancement du service Eufy: ' . $action ;

        log::add(__CLASS__, 'warning', $msg);

        if (($action == 'install') and (empty($device) or empty($passwd)))
                 throw new Exception(__('Nom du device non renseigné', __FILE__));

	if (($action == 'install') and (empty($user) or empty($passwd)))
		 throw new Exception(__('Login ou password non renseignés', __FILE__));

	if (empty($port))
		$port = '3000';

        if (shell_exec(system::getCmdSudo() . 'test -f ' . $script) != 0)
                throw new Exception(__('Script non trouvé: ' . $script, __FILE__));

    	if (shell_exec(system::getCmdSudo() . ' which docker | wc -l') == 0)
		throw new Exception(__('Docker non installé', __FILE__));

	event::add('jeedom::alert', array('level' => 'warning', 'page' => 'plugin', 
		'message' => __($msg, __FILE__) ));

	$cmdline = $script . ' ' . $action . ' ' . $device . ' ' . $user . ' ' . $passwd . ' ' . $port;
	shell_exec($cmdline . ' > ' . $log);

	$result = shell_exec('grep OK '. $log);
	log::add(__CLASS__, 'debug', 'result= '. $result);

	if (is_null($result)) {
               $msg=$action . ': Echec vérifier la log';
                event::add('jeedom::alert', array('level' => 'error', 'page' => 'plugin',
                'message' => __($msg, __FILE__)));
	} else {
		$msg=$action . ': Succès';
                event::add('jeedom::alert', array('level' => 'warning', 'page' => 'plugin',
                'message' => __($msg, __FILE__)));
	}
        log::add(__CLASS__, 'warning', $msg);
  }

}


class eufyCmd extends cmd {
  /*     * *************************Attributs****************************** */

  /*
  public static $_widgetPossibility = array();
  */

  /*     * ***********************Methode static*************************** */


  /*     * *********************Methode d'instance************************* */

  /*
  * Permet d'empêcher la suppression des commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
  public function dontRemoveCmd() {
    return true;
  }
  */

  // Exécution d'une commande
  public function execute($_options = array()) {
    $eqlogic = $this->getEqLogic();
    $cmd = $this->getLogicalId();
    log::add('eufy', 'debug', 'execute: ' . $cmd);
    $serialNumber= $eqlogic->getConfiguration('serialNumber');
    switch ($cmd) {
      case 'refresh': 
	$eqlogic->refreshDevice();
      	break;
      case 'start_rtsp':
        $params = array('command' => 'device.set_property', 'serialNumber' => $serialNumber, 'name' => 'rtspStream', 'value' => 'True');
        eufy::sendToDaemon($params);
        $params = array('command' => 'device.start_rtsp_livestream', 'serialNumber' => $serialNumber);
        eufy::sendToDaemon($params);
        break;
      case 'stop_rtsp': 
        $params = array('command' => 'device.set_property', 'serialNumber' => $serialNumber, 'name' => 'rtspStream', 'value' => 'False');
        eufy::sendToDaemon($params);
        $params = array('command' => 'device.stop_rtsp_livestream', 'serialNumber' => $serialNumber);
        eufy::sendToDaemon($params);
        break;
      case 'away':
        $params = array('command' => 'station.set_property', 'serialNumber' => $serialNumber, 'name' => 'guardMode', 'value' => '0');
        eufy::sendToDaemon($params);
        break;
      case 'home':
        $params = array('command' => 'station.set_property', 'serialNumber' => $serialNumber, 'name' => 'guardMode', 'value' => '1');
        eufy::sendToDaemon($params);
        break;
      case 'scheduled':
        $params = array('command' => 'station.set_property', 'serialNumber' => $serialNumber, 'name' => 'guardMode', 'value' => '2');
        eufy::sendToDaemon($params);
        break;
      case 'custom1':
        $params = array('command' => 'station.set_property', 'serialNumber' => $serialNumber, 'name' => 'guardMode', 'value' => '3');
        eufy::sendToDaemon($params);
        break;
      case 'custom2':
        $params = array('command' => 'station.set_property', 'serialNumber' => $serialNumber, 'name' => 'guardMode', 'value' => '4');
        eufy::sendToDaemon($params);
	break;
      case 'custom3':
        $params = array('command' => 'station.set_property', 'serialNumber' => $serialNumber, 'name' => 'guardMode', 'value' => '5');
        eufy::sendToDaemon($params);
        break;
      case 'geolocalized':
        $params = array('command' => 'station.set_property', 'serialNumber' => $serialNumber, 'name' => 'guardMode', 'value' => '47');
        eufy::sendToDaemon($params);
        break;
      case 'disarmed':
        $params = array('command' => 'station.set_property', 'serialNumber' => $serialNumber, 'name' => 'guardMode', 'value' => '63');
        eufy::sendToDaemon($params);
        break;
      case 'chime':
        $params = array('command' => 'station.chime', 'serialNumber' => $serialNumber);
        eufy::sendToDaemon($params);
        break;
      case 'reboot':
        $params = array('command' => 'station.reboot', 'serialNumber' => $serialNumber);
        eufy::sendToDaemon($params);
        break;
      case 'trigger_alarm':
        $params = array('command' => 'station.trigger_alarm', 'serialNumber' => $serialNumber);
        eufy::sendToDaemon($params);
        break;
      case 'reset_alarm':
        $params = array('command' => 'station.reset_alarm', 'serialNumber' => $serialNumber);
        eufy::sendToDaemon($params);
        break;
      case 'enableNightvision':
        $params = array('command' => 'station.set_property', 'serialNumber' => $serialNumber, 'name' => 'nightvision', 'value' => '1');
        eufy::sendToDaemon($params);
        break;
      case 'disableNightvision':
        $params = array('command' => 'station.set_property', 'serialNumber' => $serialNumber, 'name' => 'nightvision', 'value' => '0');
        eufy::sendToDaemon($params);
        break;
      default:
	$info = preg_replace("/enable/", "", $cmd);
   	if ($info != $cmd)
        	$value = 'True';
   	else {
        	$info = preg_replace("/disable/", "", $cmd);
        	$value = 'False';
   	}
   	$info = lcfirst($info);
//   	log::add('eufy', 'debug', 'commande: ' . $cmd . " info: ". $info . " value: ". $value);
	$params = array('command' => 'device.set_property', 'serialNumber' => $serialNumber, 'name' => $info, 'value' => $value);
        eufy::sendToDaemon($params);
 //     $eqlogic->checkAndUpdateCmd($info, true);
    }
  }
}
