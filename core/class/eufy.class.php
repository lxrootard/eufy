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

  public static function dependancy_end() {
    $mode = config::byKey('eufy_mode', __CLASS__);
    log::add(__CLASS__, 'info', "Configuration du container, mode sélectionné: " . $mode);
    if ($mode == 'local') {
    	if (shell_exec(system::getCmdSudo() . ' which docker | wc -l') == 0)
		throw new Exception(__('Docker non installé', __FILE__));

    	$compose_version=shell_exec(system::getCmdSudo() . " docker compose version|sed 's:.*version ::g'");
    	if ($compose_version == "")
		throw new Exception(__('plugin docker compose non installé', __FILE__));
    	eufy::updateYaml();
    }
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
        log::add(__CLASS__, 'error', __('Container Eufy non démarré', __FILE__), 'unableStartDeamon');
        event::add('jeedom::alert', array('level' => 'error', 'page' => 'plugin',
                'message' => __('Container Eufy non démarré', __FILE__)));
    //    throw new Exception(__('Container Eufy non démarré!!!', __FILE__));
	return false;
    }
    if (! eufy::testService()) {
	log::add(__CLASS__, 'error', __('Container Eufy non connecté au Cloud', __FILE__), 'unableStartDeamon');
        event::add('jeedom::alert', array('level' => 'error', 'page' => 'plugin',
                'message' => __('Container Eufy non connecté au Cloud', __FILE__)));
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
    sleep(3);

    $level = log::getLogLevel(__CLASS__) > 100 ? 'error' : 'debug' ;
    $params = array('command' => 'driver.set_log_level', 'level' => $level);
    eufy::sendToDaemon($params);
    eufy::initModelTypes();
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

   public static function initModelTypes()
   {
    //log::add(__CLASS__, 'debug','initModelTypes');
    $types = eufyCmd::getCommandsFileContent(__DIR__ . '/../config/devices/eufy_types.json');
    cache::set('eufy::modelTypes',$types);
   }

   public static function syncDevices($stations, $devices)
   {
    log::add(__CLASS__, 'info', 'eufy::syncDevices');
    log::add(__CLASS__, 'debug', 'stations: ' . $stations);
    log::add(__CLASS__, 'debug', 'devices: ' . $devices);
    eufy::syncDevicesByType ($stations,'Station');
    eufy::syncDevicesByType ($devices,'Device');
   }

   public static function syncDevicesByType ($message, $type)
   {
    log::add(__CLASS__, 'info', 'eufy::syncDevicesByType, type: '. $type);
    if (empty($message))
        return;

    $message = str_replace(": True", ": \"True\"", $message);
    $message = str_replace(": False", ": \"False\"", $message);
    $message = str_replace("'", "\"", $message);
    $jsonObjArray = json_decode($message, true);
    $jsonObj = json_decode($message);

    $deviceId = 0;
    foreach($jsonObj as $device)
    {
      $eqLogic = eqLogic::byLogicalId($device->serialNumber, __CLASS__);

      if (!is_object($eqLogic)) {
        log::add(__CLASS__, 'info', 'Creating ' . $device->name . ' serial #' . $device->serialNumber . ' type '. $device->type);
        $eqLogic = new self();
        $eqLogic->setLogicalId($device->serialNumber);
        $eqLogic->setName($device->name);
        $eqLogic->setEqType_name(__CLASS__);
        $eqLogic->setIsEnable(1);
        $eqLogic->setCategory('security', 1);
        $eqLogic->setConfiguration('eufyName', $device->name); // nom app Eufy
        $eqLogic->setConfiguration('eufyModel', $device->model);
        $eqLogic->setConfiguration('serialNumber', $device->serialNumber);
        $eqLogic->setConfiguration('hardwareVersion', $device->hardwareVersion);
        $eqLogic->setConfiguration('softwareVersion', $device->softwareVersion);
	$eqLogic->save();
      }
      else
        log::add(__CLASS__, 'debug', 'Device already exists: ' . $device->name . ' #' . $device->serialNumber);

      try {
	  $fname=substr($device->model, 0, 4) . 'x';
	  log::add(__CLASS__, 'debug', '>>> createCommandsFromConfig, file: ' . $fname);
          $commandsConfig = eufyCmd::getCommandsFileContent(__DIR__ . '/../config/devices/' . $fname . '.json');
	  for ($i=0; $i < count($commandsConfig); $i++)
          	$eqLogic->createCommandsFromConfig($commandsConfig[$i], $jsonObjArray[$deviceId], $commandsConfig[$i]['interface']);
      }
      catch(Exception $e) {
          log::add(__CLASS__, 'warning', $e);
      }

      $eqLogic->refreshDevice();
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
	if (! $this->getIsEnable())
	  return;

	$name = $this->getConfiguration('eufyName');
        $serialNumber= $this->getConfiguration('serialNumber');
        log::add(__CLASS__, 'debug', 'Refresh device: ' . $name .' #'. $serialNumber);

        $itfnames = $this->getConfiguration('interfaces');
        foreach ($itfnames as $itf) {
		log::add(__CLASS__, 'debug', '> refresh interface: ' . $itf);
                $params = array('command' => $itf . '.get_properties', 'serialNumber' => $serialNumber);
		eufy::sendToDaemon($params);
	}
  }

  public static function updateDeviceInfo($serialNumber, $property, $value)
  {
    $eqLogic = eqLogic::byLogicalId($serialNumber, __CLASS__);
    if (! isset($eqLogic)) {
	log::add(__CLASS__, 'error', $serialNumber . ': eqLogic not found');
	return;
    }
    if (! $eqLogic->getIsEnable())
	return;
    $cmd = $eqLogic->getCmd('info', $property);
    if (is_null($cmd) || (! $cmd))
        return;

    //log::add(__CLASS__, 'info', 'eufy::updateDeviceInfo serialNumber: '. $serialNumber . ', property: '
    //	. $property . ', value: ' . $value);

    if ($property == 'type') {
	$types = cache::byKey('eufy::modelTypes')->getValue();
	if (isset($types)) {
		 $eqLogic->setConfiguration('eufyType', $types[$value]);
		 $eqLogic->save();
	}
    }
    if ($property == 'battery')
       $eqLogic->batteryStatus($value);
    if ($property == 'picture') {
       $s1=cache::byKey('eufy::'.$serialNumber)->getValue();
       $value = $eqLogic->extractPicture((array)$value,$serialNumber);
       $s2=cache::byKey('eufy::'.$serialNumber)->getValue();
//       log::add(__CLASS__, 'debug', 'old pic size:' .$s1. ', new pic size: '. $s2);
       if ($s1 == $s2) return;
    }
    if ($cmd->getGeneric_type() == 'LIGHT_COLOR')
       $value = eufy::rgb2hex($value);

    if (eufy::sendEvent($cmd, $value)) {
        log::add(__CLASS__, 'debug', 'device info updated, property: '. $property);
    }
  }

  public static function hex2rgb($hex) {
    $r = hexdec(substr($hex, 1, 2));
    $g = hexdec(substr($hex, 3, 2));
    $b = hexdec(substr($hex, 5, 2));
    return array('red'=>$r, 'green'=>$g, 'blue'=>$b);
  }

  public static function rgb2hex ($json) {
    $a = json_decode($json,true);
    return '#' . sprintf('%02d',dechex($a['red']))
        . sprintf('%02d',dechex($a['green']))
        . sprintf('%02d',dechex($a['blue']));
  }

  public function extractPicture($a,$serialNumber)
  {
  //  log::add(__CLASS__, 'debug', 'array: '. json_encode($a));
    $imgRoot= '/data/tmp/';
  //  $img = $this->getConfiguration('serialNumber') . date("d-m-Y.H:i"). '.jpg';
    $img = $this->getConfiguration('serialNumber') . '.jpg';

    $dirName = __DIR__ . '/../..' . $imgRoot;
    mkdir ($dirName,0755,true);
    $fname = $dirName . $img;
    $urlRoot = '/plugins/eufy';
    $bytes = $a['data']['data'];
 //   log::add(__CLASS__, 'debug', 'img file: ' . $img);
//    log::add(__CLASS__, 'debug', 'fname: ' . $fname);

    if (is_array($bytes)) {
//	log::add(__CLASS__, 'debug', 'pic size: '. count($bytes));
	cache::set('eufy::' . $serialNumber, count($bytes));
	$f = fopen($fname,"wb+");
	$data='';
	foreach ($bytes as $b) {
        	$data = pack("C*",$b);
        	fwrite ($f, $data);
	}
    	fclose ($f);
	return $urlRoot . $imgRoot . $img . '?ts=' . @filemtime($fname);
    }
    else
	return $urlRoot . '/resources/no_snapshot.png';
  }

  public static function sendEvent($cmd, $value) {
    if (is_object($cmd))
	if (($cmd->getLogicalId() == 'picture') ||
		($cmd->execCmd() != $cmd->formatValue($value))) {
      		$cmd->event($value, null);
      		return true;
        }
    return false;
  }

  public function createCommandsFromConfig(array $commands, $values, $itf) {
        $link_cmds = array();
	$itfnames = $this->getConfiguration('interfaces');
	if (empty($itfnames))
		$itfnames = array();
	if (! in_array($itf, $itfnames))
		array_push($itfnames, $itf);

	$this->setConfiguration('interfaces', $itfnames);
        $this->save();

        foreach ($commands["commands"] as $cmdDef) {
                $cmd = $this->getCmd(null, $cmdDef["logicalId"]);
                if (!is_object($cmd)) {
                        $cmd = $this->createCommand ($cmdDef,$itf);
                        // Init value
                        if((isset($values)) and ($values[$cmdDef["type"]] == 'info')) {
//				log::add(__CLASS__, 'debug', '> value: ' .  $values[$cmdDef["logicalId"]]);
                                eufy::sendEvent($cmd, $values[$cmdDef["logicalId"]]);
			}
                        elseif (isset($cmdDef['initialValue'])) {
                                $cmdValue = $cmd->execCmd();
                                if ($cmdValue=='')
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

  public function createCommand ($cmdDef, $itf)
  {
        log::add(__CLASS__, 'info', 'createCommand: '. $cmdDef["logicalId"] . '/' . $cmdDef["name"] . ', interface: ' . $itf);
	$cmd = new eufyCmd();
        $cmd->setLogicalId($cmdDef["logicalId"]);
        $cmd->setEqLogic_id($this->getId());
        $cmd->setName(__($cmdDef["name"], __FILE__));
        if(isset($cmdDef["isHistorized"]))
        	$cmd->setIsHistorized($cmdDef["isHistorized"]);

        if(isset($cmdDef["isVisible"]))
        	$cmd->setIsVisible($cmdDef["isVisible"]);

        if (isset($cmdDef['template']))
        	foreach ($cmdDef['template'] as $key => $value)
                	$cmd->setTemplate($key, $value);

	$cmd->setType($cmdDef["type"]);
	$cmd->setSubType($cmdDef["subtype"]);

        if(isset($cmdDef["generic_type"]))
        	$cmd->setGeneric_type($cmdDef["generic_type"]);

        if (isset($cmdDef['display']))
        	foreach ($cmdDef['display'] as $key => $value) {
                	if ($key=='title_placeholder' || $key=='message_placeholder')
                        	$value = __($value, __FILE__);
                        $cmd->setDisplay($key, $value);
                }

        if(isset($cmdDef["unite"]))
        	$cmd->setUnite($cmdDef["unite"]);

        if (isset($cmdDef['configuration']))
        	foreach ($cmdDef['configuration'] as $key => $value)
			$cmd->setConfiguration($key, $value);

        if (isset($cmdDef['value']))
        	$link_cmds[$cmdDef["logicalId"]] = $cmdDef['value'];

	$cmd->setConfiguration('interface',$itf);
        $cmd->save();
 	return $cmd;
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

 // vérif si container up&running
  public static function checkContainer()
  {
    $ip = config::byKey('containerip', __CLASS__);
    $port = config::byKey('containerport', __CLASS__);
    log::add(__CLASS__, 'debug', '>>> Checking container ' . $ip .':' . $port);
    try {
        $conn = @fsockopen($ip, $port);
        if (is_resource($conn)) {
                log::add(__CLASS__, 'debug', 'Container ' . $ip .':' . $port .' is listening');
                fclose($conn);
                cache::set('eufy::container_ok',true);
                return true;
        }
    }
    catch (Exception $ex) {}
    log::add(__CLASS__, 'debug', 'Container '. $ip . ':'. $port . ' is not responding :(');
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


  public static function testContainer($option)
  {
	$host = config::byKey('containerip', __CLASS__);
	$port = config::byKey('containerport', __CLASS__);
	if ((! isset($host)) or (! isset($port))) {
		$host="127.0.0.1";
		$port="3000";
	}
	$h = "'" . $host . "'";
	$p = "'" . $port . "'";
	// log::add(__CLASS__, 'debug',  '>>> Testing EufyWS service on '. $host . ':' . $port);
	$python='python3';
	$script =  __DIR__ . '/../../resources/test_eufy.py ';
	$rc = shell_exec(system::getCmdSudo() . $python . ' ' . $script . $option . ' -u ' . $h . ':' . $p .' 2>&1');
	// log::add(__CLASS__, 'debug', '*** Test result '. $rc);
	return json_decode($rc);
  }

  public static function testService() {
 	$jsonObj = eufy::testContainer("-v");
	$version = '';
        if (is_object($jsonObj))
		$version = $jsonObj->serverVersion;
	log::add(__CLASS__, 'debug', 'eufy-security-ws image version: ' .   $version);
	cache::set('eufy::version',$version);

	$jsonObj = eufy::testContainer("-t");
        $online = False;
        if (is_object($jsonObj))
                $online = $jsonObj->result->state->driver->connected;
	log::add(__CLASS__, 'debug', 'eufy-security-ws service online: ' . $online);
        cache::set('eufy::online', $online);
	return $online;
  }

        // @Mips
        public static function executeAsync(string $_method, $_option = null, $_date = 'now') {
                if (!method_exists(__CLASS__, $_method))
                        throw new InvalidArgumentException("Method provided for executeAsync does not exist: {$_method}");

                $cron = new cron();
                $cron->setClass(__CLASS__);
                $cron->setFunction($_method);
                if (isset($_option))
                        $cron->setOption($_option);

                $cron->setOnce(1);
                $scheduleTime = strtotime($_date);
                $cron->setSchedule(cron::convertDateToCron($scheduleTime));
                $cron->save();
                if ($scheduleTime <= strtotime('now')) {
                        $cron->run();
			log::add(__CLASS__, 'debug', "Task '{$_method}' executed now");
                } else
                        log::add(__CLASS__, 'debug', "Task '{$_method}' scheduled at {$_date}");
        }


  // init yaml file
  public static function updateYaml ()
  {
        $path= realpath(__DIR__ . '/../..');
        $store_dir = $path . '/data/store';
        $yaml = 'docker-compose.yml';
        $file = $path . '/data/' . $yaml ;

	log::add('eufy', 'debug', "Mise à jour du fichier yaml " . $file);
	$device = config::byKey('devicename', __CLASS__);
	$user = config::byKey('username', __CLASS__);
	$passwd = config::byKey('password', __CLASS__);
	$port = config::byKey('containerport', __CLASS__);
	if (empty($device))
               	throw new Exception(__('Nom du device non renseigné', __FILE__));
	else if (empty($user) or empty($passwd))
 		throw new Exception(__('Login ou password non renseignés', __FILE__));
        if (empty($port))
               	$port = '3000';

	$compose = file_get_contents($path . '/resources/' . $yaml);
	$compose = str_replace('#store#', $store_dir, $compose);
	$compose = str_replace('#device#', $device, $compose);
	$compose = str_replace('#user#', $user, $compose);
	$compose = str_replace('#password#', $passwd, $compose);
	$compose = str_replace('#port#', $port, $compose);
    	mkdir ($store_dir,0755,true);
	file_put_contents($file, $compose);
	return $file;
  }

  public static function installContainer() {
	try {
 	 	if (config::byKey('eufy::opInProgress', __CLASS__, '') == 1)
			throw new Exception(__('Installation du container déjà en cours, merci de patienter', __FILE__));
		config::save('eufy::opInProgress', 1, __CLASS__);
		eufy::setupContainer('install');
		config::save('eufy::opInProgress', 0, __CLASS__);
         } catch (Exception $e) {
		log::add('eufy', 'debug', "Erreur en cours d'installation: " . $e->getMessage());
		config::save('eufy::opInProgress', 0, __CLASS__);
	        event::add('jeedom::alert', array('level' => 'error', 'page' => 'eufy',
               	'message' => $e->getMessage()));
	 }
  }

  // install | uninstall
  public static function setupContainer($action)
  {
	$msg = $action . ' du conteneur eufy merci de patienter';
	log::add('eufy', 'info', "Début de l'opération " . $action . '_container');
	event::add('jeedom::alert', array('level' => 'info', 'page' => 'eufy',
       		'message' => $msg));
	$eufy = 'bropat/eufy-security-ws';
	$yaml = eufy::updateYaml();
	$cid  = shell_exec(system::getCmdSudo() . " docker ps -a | grep -i eufy|awk '{ print $1 }'");
	$image = shell_exec(system::getCmdSudo() . ' docker images | grep ' . $eufy);
	log::add('eufy', 'debug','container id: '. $cid);
	switch ($action) {
	   case 'install':
		if ($cid != "")
			throw new Exception(__("Le container n'est pas arrêté", __FILE__));
		if ($image != "")
			 throw new Exception(__("L'image est déjà installée", __FILE__));
		shell_exec(system::getCmdSudo() . ' docker pull ' . $eufy);
		break;
	   case 'start':
		if ($image == "")
			throw new Exception(__("L'image n'est pas installée", __FILE__));
		if ($cid != "")
                        throw new Exception(__('Le container est déjà démarré', __FILE__));
	        if (!file_exists($yaml))
                	throw new Exception(__('Fichier yaml non trouvé', __FILE__));

		$cid = shell_exec(system::getCmdSudo() . ' docker compose -f '. $yaml .' up -d');
		log::add('eufy', 'debug','container id: '. $cid);
		break;
	   case 'stop':
		if ($cid == "")
			throw new Exception(__("Le container n'est pas démarré", __FILE__));
                if (!file_exists($yaml))
                        throw new Exception(__('Fichier yaml non trouvé', __FILE__));

		shell_exec(system::getCmdSudo() . ' docker compose -f '. $yaml .' down');
		break;
	   case 'uninstall':
		if ($cid != "")
			throw new Exception(__("Le container n'est pas arrêté", __FILE__));
		shell_exec(system::getCmdSudo() . ' docker rmi '. $eufy);
                log::add('eufy', 'debug', "Suppression du fichier " . $yaml);
                shell_exec(system::getCmdSudo() . ' rm -f '. $yaml);
	}
	log::add('eufy', 'info', "Fin de l'opération " . $action . '_container');
	event::add('jeedom::alert', array('level' => 'info', 'page' => 'eufy',
		'message' => "Opération " . $action . " terminée"));
  }
}


class eufyCmd extends cmd {

 /* helper */
  public static function getCommandsFileContent(string $filePath) {
        if (!file_exists($filePath)) {
                throw new RuntimeException("Fichier de configuration non trouvé: {$filePath}");
        }
        $content = file_get_contents($filePath);
        if (!is_json($content)) {
                throw new RuntimeException("Fichier de configuration incorrect: {$filePath}");
        }
        return json_decode($content, true);
  }


  // Exécution d'une commande
  public function execute($_options = array()) {
//  log::add('eufy', 'debug', '>>>> $_options: ' . json_encode($_options));
    $eqLogic = $this->getEqLogic();
    $cmd = $this->getLogicalId();
    $itf =  $this->getConfiguration('interface');
    $serialNumber= $eqLogic->getConfiguration('serialNumber');
    log::add('eufy', 'debug', 'execute: ' . $itf . '.' . $cmd);

    switch ($cmd) {
      case 'refresh':
	$eqLogic->refreshDevice();
      	break;
      default:
        $enable = preg_replace("/:on$/", "", $cmd);
        $disable = preg_replace("/:off$/", "", $cmd);
        $set = preg_replace("/:set.*$/", "", $cmd);
	$str = preg_replace("/:[0-9]/","",$cmd);
	$prop = preg_replace("/:.*/","", $str);
	$action = preg_replace("/.*:/","", $str);
	$value = preg_replace("/[^0-9.]/","", $cmd);

  	if ($enable != $cmd)
		$params = array('command' => $itf . '.set_property', 'serialNumber' => $serialNumber, 'name' => $enable, 'value' => 'True');
   	else if ($disable != $cmd)
                $params = array('command' => $itf . '.set_property', 'serialNumber' => $serialNumber, 'name' => $disable, 'value' => 'False');
	else if ($set != $cmd) {
		if ($value == '')
			$value = $_options['slider']; // slider
		if ($value == '')
			$value = $_options['select']; // combo list
		if ($value == '') {
			$value = $_options['color']; // couleur
			$value = eufy::hex2rgb($value);
		}
                $params = array('command' => $itf . '.set_property', 'serialNumber' => $serialNumber, 'name' => $set, 'value' => $value);
	}
	else if ($value != "") {
		if (is_numeric($value)) $value = intval($value);
		$params = array('command' => $itf . '.' . $action , 'serialNumber' => $serialNumber, $prop => $value);
	}
	else // action command without parms
		$params = array('command' => $itf . '.' . $action, 'serialNumber' => $serialNumber);

   	log::add('eufy', 'debug', 'cmd::execute send to daemon: ' . json_encode($params));
        eufy::sendToDaemon($params);
 //     $eqLogic->checkAndUpdateCmd($info, true);
    }
  }
}
