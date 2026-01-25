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
require_once __DIR__  . '/eufyUtils.php';

class eufy extends eqLogic {

  public static function backupExclude() {
    return [ 'resources/python_venv' ];
  }

  public static function deamon_info() {
    $rc = array();
    $rc['log'] = __CLASS__;
    $rc['state'] = 'nok';
    $pid_file = jeedom::getTmpFolder(__CLASS__) . '/deamon.pid';
    if (file_exists($pid_file)) {
        if (@posix_getsid(trim(file_get_contents($pid_file))))
            $rc['state'] = 'ok';
        else
            shell_exec(system::getCmdSudo() . 'rm -rf ' . $pid_file . ' 2>&1 > /dev/null');
    }
    $rc['launchable'] = 'ok';
    $mode = config::byKey('eufyMode', __CLASS__);
    if ($mode == 'local') {
	$compose_version=shell_exec(system::getCmdSudo() . " docker compose version|sed 's:.*version ::g'");
        if (shell_exec(system::getCmdSudo() . ' which docker | wc -l') != 1) {
	   $rc['launchable'] = 'nok';
	   $rc['launchable_message'] = __("Docker non installé, lancez l'installation des dépendances", __FILE__);
	}
        elseif ($compose_version == "") {
	   $rc['launchable'] = 'nok';
	   $rc['launchable_message'] = __("Module docker compose non installé, lancez l'installation des dépendances", __FILE__);
	}
    }

    $container = config::byKey('container', __CLASS__,array());
    if ($container['host'] == '') {
        $rc['launchable'] = 'nok';
        $rc['launchable_message'] = __('L\'IP du container n\'est pas configurée', __FILE__);
    }
    elseif ($container['port'] == '') {
        $rc['launchable'] = 'nok';
        $rc['launchable_message'] = __('Le port n\'est pas configuré', __FILE__);
    }
    return $rc;
  }

  public static function deamon_start() {
    self::deamon_stop();
    $deamon_info = self::deamon_info();
    if ($deamon_info['launchable'] != 'ok')
        throw new Exception(__('Veuillez vérifier la configuration', __FILE__));

    $pyExec = eufyUtils::getPyPath();
    if (! eufyUtils::checkContainer()) {
        log::add(__CLASS__, 'error', __('Container Eufy non démarré', __FILE__), 'unableStartDeamon');
        event::add('jeedom::alert', array('level' => 'error', 'page' => 'plugin',
                'message' => __('Container Eufy non démarré', __FILE__)));
	return false;
    }
    if (! eufyUtils::testService()) {
	log::add(__CLASS__, 'error', __('Container Eufy non connecté au Cloud', __FILE__), 'unableStartDeamon');
        event::add('jeedom::alert', array('level' => 'error', 'page' => 'plugin',
                'message' => __('Container Eufy non connecté au Cloud', __FILE__)));
	return false;
    }
    log::add(__CLASS__, 'info', 'Lancement du démon eufyd');
    $cmd = eufyUtils::getPyCmdLine();
    $result = exec($cmd . ' >> ' . log::getPathToLog('eufy_daemon') . ' 2>&1 &');
    $i = 0;
    while ($i < 5) {
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
    if ($deamon_info['state'] != 'ok')
	throw new Exception("Le démon n'est pas démarré");
    $params['apikey'] = jeedom::getApiKey(__CLASS__);
    $payload = json_encode($params);
    log::add(__CLASS__, 'debug',  '['. __FUNCTION__ . '] msg: ' . $payload);

    $socket = socket_create(AF_INET, SOCK_STREAM, 0);
    socket_connect($socket, '127.0.0.1', config::byKey('socketPort', __CLASS__, '60600'));
    socket_write($socket, $payload, strlen($payload));
    socket_close($socket);
  }

  public static function setDevicesList($stations, $devices)
  {
    log::add(__CLASS__, 'info',  '['. __FUNCTION__ . ']');
    log::add(__CLASS__, 'info', '>>> stations: ' . json_encode($stations));
    log::add(__CLASS__, 'info', '>>> devices: ' . json_encode($devices));
    cache::set('eufy::stations', eufy::updDevicesConfig($stations));
    cache::set('eufy::devices', eufy::updDevicesConfig($devices));
  }

  public static function updDevicesConfig ($msg)
  {
    $msg2 = array();
    foreach ($msg as $dev) {
	$sno = $dev['serialNumber'];
	$msg2[$sno] = $dev;
    }
    // log::add(__CLASS__, 'debug', 'msg2: ' .  json_encode($msg2));
    return $msg2;
  }

  public static function discoverItfs()
  {
    log::add(__CLASS__, 'debug',  '['. __FUNCTION__ . ']');
    eufy::discoverItf (cache::byKey('eufy::stations')->getValue(),'station');
    eufy::discoverItf (cache::byKey('eufy::devices')->getValue(),'device');
    eufy::refreshAllDevices();
  }

  public static function discoverItf($list, $itf)
  {
    log::add(__CLASS__, 'debug',  '['. __FUNCTION__ . '] devices: ' . json_encode($list) . ' itf: ' . $itf);
    foreach ($list as $sno => $dev) {
	$eqLogic = eqLogic::byLogicalId($sno, __CLASS__);
    	if (is_object($eqLogic) && (! $eqLogic->getIsEnable()))
	    continue;
	log::add(__CLASS__, 'info', 'discovering device: ' . $dev['name'] . ' s/no: ' . $sno . ' interface: ' . $itf);
	$id = ($itf == 'device')? 'd' : 's';
	$params = array('messageId' => $sno . $id, 'command' => $itf . '.get_properties_metadata', 'serialNumber' => $sno);
        eufy::sendToDaemon($params);
	sleep(2);
    }
  }

  public static function handleResult($msg)
  {
    log::add(__CLASS__, 'debug', '[' . __FUNCTION__ . '] msg received: ' . json_encode($msg));
    $result = $msg['result'];
    if ((!isset($result)) || (! $msg['success'])) {
	log::add(__CLASS__, 'warning', '[' . __FUNCTION__ . '] unexpected error: ' . $msg['messageId'] . ': ' . $msg['errorCode']);
	return;
    }
    if (isset($result['state'])) {
        log::add(__CLASS__, 'debug','>>> State message received');
        $online = ($result['state']['driver']['pushConnected'] == 'true');
        eufy::setOnlineStatus ($online);
        if ((isset($result['state']['stations'])) || (isset($result['state']['devices'])))
             eufy::setDevicesList($result['state']['stations'],$result['state']['devices']);
    }
    else if (isset($result['properties'])) {
        $list = $result['properties'];
        if (strpos($msg['messageId'],'d')!== false) {
             log::add('eufy', 'debug','>>> Device metadata message received');
             eufy::updMetadata ($msg, 'device');
        }
        else if (strpos($msg['messageId'],'s')!== false) {
            log::add('eufy', 'debug','>>> Station metadata message received');
            eufy::updMetadata ($msg, 'station');
        }
        else {
            log::add('eufy', 'debug','>>> Properties update message received');
            foreach ($list as $prop => $val)
                eufy::updateDeviceInfo($list['serialNumber'], $prop, $val);
        }
    }
  }

  public static function handleEvent($event)
  {
     if (isset($event['source'])) {
        log::add('eufy', 'debug','[' . __FUNCTION__ . '] msg received: ' .  json_encode($event));
        if (($event['source'] == 'station') || ($event['source'] == 'device')) {
            if (($event['event'] == 'connection error') || ($event['event'] == 'disconnected'))
                eufy::updateDeviceInfo($event['serialNumber'], 'present', false);
	    else if ($event['event'] == 'connected')
		eufy::updateDeviceInfo($event['serialNumber'], 'present', true);
            else if ($event['event'] == 'property changed')
                eufy::updateDeviceInfo($event['serialNumber'], $event['name'],$event['value']);
        }
        else if ($event['source'] == 'driver') {
            log::add('eufy', 'debug','>>> driver message received: ' . $event['event']);
            if (($event['event'] == 'push disconnected') || ($event['event'] == 'disconnected'))
                eufy::setOnlineStatus (false);
        }
     } else
	log::add(__CLASS__, 'warning', '[' . __FUNCTION__ . '] unexpected error: ' . json_encode($event));
  }

  public static function updMetadata ($msg, $itf)
  {
     $devs = cache::byKey('eufy::'.$itf.'s')->getValue();
     $id = str_replace(['s','d'], '', $msg['messageId']);
     log::add(__CLASS__, 'debug', '['. __FUNCTION__ . '] s/no: ' . $id . ' interface: '. $itf);
     if (array_key_exists($id, $devs)) {
	$metadata = $msg['result']['properties'];
	$devs[$id]['metadata'] =  $metadata;
	$types = cache::byKey('eufy::modelTypes')->getValue();
	if (! is_array($types))
	   cache::set('eufy::modelTypes', $metadata['type']['states']);
	eufy::syncDevice ($devs[$id], $itf);
     }
  }

  public static function syncDevice ($msg, $itf)
  {
    $sno = $msg['serialNumber'];
    log::add(__CLASS__, 'debug',  '['. __FUNCTION__ . '] s/no: ' .$sno . ' itf: ' . $itf);
    $eqLogic = eqLogic::byLogicalId($sno, __CLASS__);
    if (!is_object($eqLogic)) {
	log::add(__CLASS__, 'info', '['. __FUNCTION__ . '] Creating new device: ' . $msg['name'] . ' s/no: ' . $sno);
	$eqLogic = new self();
	$eqLogic->setLogicalId($sno);
	$eqLogic->setName($msg['name']);
	$eqLogic->setEqType_name(__CLASS__);
	$eqLogic->setIsEnable(1);
	$eqLogic->setIsVisible(1);
	$eqLogic->setCategory('security', 1);

	$eqLogic->setConfiguration('serialNumber', $sno);
        $eqLogic->setConfiguration('eufyName', $msg['name']); // nom dans l'app Eufy
        $eqLogic->setConfiguration('eufyModel', $msg['model']);
        $eqLogic->setConfiguration('hardwareVersion', $msg['hardwareVersion']);
        $eqLogic->setConfiguration('softwareVersion', $msg['softwareVersion']);
        $eqLogic->save();
    }
    else {
        log::add(__CLASS__, 'info', 'Device already exists: ' . $eqLogic->getName() . ' s/no: ' . $sno);
	if (! $eqLogic->getIsEnable())
	    return;
    }

    $itfnames = $eqLogic->getConfiguration('interfaces');
    if (empty($itfnames))
	$itfnames = array();
    if (! in_array($itf, $itfnames)) {
	array_push($itfnames, $itf);
	$eqLogic->setConfiguration('interfaces', $itfnames);
	$eqLogic->save();
    }
    foreach ($msg['metadata'] as $prop)
	$eqLogic->createProperty($prop, $itf);
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
    log::add(__CLASS__, 'debug',  '['. __FUNCTION__ . ']: ' . $name .' #'. $serialNumber);

    $itfnames = $this->getConfiguration('interfaces');
    foreach ($itfnames as $itf) {
	log::add(__CLASS__, 'debug', '>>> refresh interface: ' . $itf);
	$params = array('messageId' => $serialNumber, 'command' => $itf . '.get_properties', 'serialNumber' => $serialNumber);
	eufy::sendToDaemon($params);
    }
  }

  public static function updateDeviceInfo($sno, $property, $value)
  {
    $eqLogic = eqLogic::byLogicalId($sno, __CLASS__);
    if (! isset($eqLogic)) {
	log::add(__CLASS__, 'error', $sno . ': eqLogic not found');
	return;
    }
    if (! $eqLogic->getIsEnable())
	return;

    $cmd = $eqLogic->getCmd('info', $property);
    if (is_null($cmd) || (! $cmd))
        return;

    $val = ($property == 'picture')? 'object' : json_encode($value);
    log::add(__CLASS__, 'info', '['. __FUNCTION__ . '] device: '. $eqLogic->getName() .' s/no: '. $sno . ' property: '
    	. $property . ' value: ' . $val);

    $eqLogic->checkAndUpdateCmd('present', true);

    if (($property == 'type') && ($eqLogic->getConfiguration('eufyType') == null)) {
	$types = cache::byKey('eufy::modelTypes')->getValue();
	if (is_array($types)) {
	   $eqLogic->setConfiguration('eufyType',$types[$value]);
	   $eqLogic->save();
	}
    }

    if ($property == 'battery')
       $eqLogic->batteryStatus($value);
    if ($property == 'picture') {
	$s1=cache::byKey('eufy::'.$sno)->getValue();
	$value = $eqLogic->extractSnapshot((array)$value, $sno);
	$s2=cache::byKey('eufy::'.$sno)->getValue();
	if ($s1 == $s2) return;
    }
    if ($cmd->getGeneric_type() == 'LIGHT_COLOR')
       $value = eufyUtils::rgb2hex($value);

    if ($cmd->sendEvent($value)) {
//	log::add(__CLASS__, 'debug', 'device info updated, property: '. $property);
    }
  }

  public function extractSnapshot($a,$serialNumber)
  {
  //  log::add(__CLASS__, 'debug', 'array: '. json_encode($a));
    $imgRoot= '/data/tmp/';
  //  $img = $this->getConfiguration('serialNumber') . date("d-m-Y.H:i"). '.jpg';
    $img = $this->getConfiguration('serialNumber') . '.jpg';

    $dirName = __DIR__ . '/../..' . $imgRoot;
    if (!file_exists($dirName))
    	mkdir ($dirName,0755,true);
    $fname = $dirName . $img;
    $urlRoot = '/plugins/eufy';
    $bytes = $a['data']['data'];

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
	return $urlRoot . '/data/no_snapshot.png';
  }

  public function getImage() {
    $model = substr($this->getConfiguration('eufyModel'), 0, 5);
    return eufyUtils::getPicture($model);
  }

  public function createProperty($prop, $itf)
  {
   // log::add(__CLASS__, 'debug', '['. __FUNCTION__ . ']  prop: ' . json_encode($prop) . ' itf: '.$itf);
   $cmd = $this->getCmd(null, $prop['name']);
   if (!is_object($cmd)) {
	$id = $prop['name'];
	$type = $prop['writeable'] ? 'action' : 'info';
//	log::add(__CLASS__, 'debug', '['. __FUNCTION__ . ']  type: ' . $type . ' prop: ' .  json_encode($prop));
	$info = $this->createCmd('info', $prop, $itf, $id, $prop['label'], $prop['unit']);
	if ($type == 'action') {
	    if ($prop['type'] == 'boolean') {
		$this->createActionCmd($prop, $itf, $id.':on', $prop['label'] .' On', $info);
		$this->createActionCmd($prop, $itf, $id.':off', $prop['label'] .' Off', $info);
	    } else if ($prop['type'] == 'number') {
		$this->createActionCmd($prop, $itf, $id.':set', 'Set '. $prop['label'], $info);
		if ($id != 'guardMode') {
		    $info->setIsVisible(0);
		    $info->save();
		}
	    }
	}
   }
  }

  public function createActionCmd($prop, $itf, $id, $name, $info)
  {
   // log::add(__CLASS__, 'debug', '['. __FUNCTION__ . '] id= '. $id);
   $cmd = $this->createCmd('action', $prop, $itf, $id, $name);
   if (! is_object($info))
	return;
   $cmd->setValue($info->getId());
   $cmd->save();
  }

  public function createCmd($type, $prop, $itf, $id, $name, $unit=null)
  {
   $cmd = new eufyCmd();
   $cmd->setLogicalId($id); // key
   $cmd->setEqLogic_id($this->getId());
   $cmd->setConfiguration('interface',$itf);
   $cmd->setName($name);
   $cmd->setType($type);
   $cmd->setIsVisible(1);
   $cmd->setIsHistorized(0);
   if ($type == 'info') {
	$cmd->setTemplate('dashboard','line');
	$cmd->setTemplate('mobile','line');
	if ($prop['type'] == 'number') {
	    $subtype = 'numeric';
	    if ($id == 'guardMode')
		$cmd->setCustomTemplate('cmd.info.numeric','EufyCam_lxr');
	}
	else if ($prop['type'] == 'boolean') {
	    $subtype = 'binary';
	    $cmd->setIsVisible(0); // on masque l'info
	} else {
	    $subtype = 'string';
	    if ($id == 'picture') {
		cache::set('eufy::' . $this->getConfiguration('serialNumber'), 0);
		$cmd->setCustomTemplate('cmd.info.string','ImageViewer_lxr');
	    }
	}
   } else {
	$subtype = 'other';
	if (($prop['type']== 'number') && (! is_null($prop['states']))) {
            $subtype ='select';
            $cmd->setConfiguration('listValue',eufyUtils::list2enum($prop['states']));
	    $cmd->setCustomTemplate('cmd.action.select','Liste_lxr');
	}
	else if (($prop['type']== 'object') && (strpos(strtolower($id),'color') !== false))
	    $subtype = 'color';
	if ((isset($prop['min'])) && (isset($prop['max'])))
	    $subtype = 'slider';
	if ($prop['type'] == 'boolean') {
	    $cmd->setTemplate('dashboard','core::binarySwitch');
	    $cmd->setTemplate('mobile','core::binarySwitch');
	    $cmd->setCustomTemplate('cmd.action.other','BinarySwitch_lxr');
	}
   }
   log::add(__CLASS__, 'info', '['. __FUNCTION__ . '] device: ' . $this->getName() . ' s/no: ' . $this->getLogicalId() .
	' id: ' . $id .' / name: '.$name . ' / type: '.$type . ' / subtype: '.$subtype.' / itf: '.$itf);
   $cmd->setSubType($subtype);
   if ($subtype == 'slider') {
	$cmd->setConfiguration('minValue', $prop['min']);
	$cmd->setConfiguration('maxValue', $prop['max']);
   }
   if (isset($unit))
	$cmd->setUnite($unit);
   $cmd->setGeneric_type($cmd->getGenericType());
   $cmd->save();

   return $cmd;
  }

  public function createCustomCmd($type, $stype, $itf, $id, $name, $icon=null) {
   $cmd = $this->getCmd(null, $id);
   if (is_object($cmd))
	return;

   $cmd = $this->createCmd($type, array('type' => $stype),$itf, $id,$name);
   $cmd->setConfiguration('other',1);
   $cmd->setDisplay('icon', '<i class="icon ' . $icon . '"><i>');
   $cmd->save();
  }

  public function createPanTiltCmds() {
   log::add(__CLASS__, 'debug', '['. __FUNCTION__ . '] ');
   $this->createCustomCmd('action','other','device','direction:pan_and_tilt:1','Left','fas fa-chevron-left');
   $this->createCustomCmd('action','other','device','direction:pan_and_tilt:2','Right','fas fa-chevron-right');
   $this->createCustomCmd('action','other','device','direction:pan_and_tilt:3','Up','fas fa-chevron-up');
   $this->createCustomCmd('action','other','device','direction:pan_and_tilt:4','Down','fas fa-chevron-down');
   $this->createCustomCmd('action','other','device','calibrate','Calibrate','fas fa-expand-arrows-alt');
  }

  public function createStationCmds() {
   log::add(__CLASS__, 'debug', '['. __FUNCTION__ . '] ');
   $this->createCustomCmd('action','other','station','chime','Chime','fas fa-concierge-bell');
   $this->createCustomCmd('action','other','station','trigger_alarm','Trigger alarm','fas fa-bell');
   $this->createCustomCmd('action','other','station','reset_alarm','Reset alarm','fas fa-bell-slash');
   $this->createCustomCmd('action','other','station','reboot','Reboot','kiko-reload-arrow');
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
   // log::add(__CLASS__, 'debug', '['. __FUNCTION__ . '] for: '. $this->getName());
   $stationCmds = $this->getConfiguration('stationCmds');
   $ptCmds = $this->getConfiguration('panTiltCmds');
   if ($stationCmds == true)
	$this->createStationCmds();
   $ptCmds = $this->getConfiguration('panTiltCmds');
   if ($ptCmds == true)
	$this->createPanTiltCmds();
   $this->createCustomCmd('action','other','other', 'refresh','Rafraichir');
   $this->createCustomCmd('info','boolean','other', 'present','Présent');

  }

  // Fonction exécutée automatiquement avant la suppression de l'équipement
  public function preRemove() {
  }

  // Fonction exécutée automatiquement après la suppression de l'équipement
  public function postRemove() {
  }

  public static function isListening()
  {
    $b = cache::byKey('eufy::container_ok')->getValue();
//  log::add(__CLASS__, 'debug', 'isListening: ' .  $b);
    return $b;
  }

  public static function setOnlineStatus($b)
  {
//  log::add(__CLASS__, 'debug', 'online status: ' . $b);
    cache::set('eufy::online', $b);
  }

  public static function isOnline ()
  {
    $online = cache::byKey('eufy::online')->getValue();
//  log::add(__CLASS__, 'debug', 'isOnline: ' . $online);
    return $online ;
  }

  public static function installImage() {
    eufyUtils::setupContainer('install');
  }

}

class eufyCmd extends cmd {

  public function setCustomTemplate($type, $id) {
   $itfs = array('dashboard','mobile');
   $baseDir =  __DIR__ . '/../../../../data/customTemplates/';
   foreach ($itfs as $itf) {
	$tpl = $baseDir . $itf .'/' . $type . '.'. $id . '.html';
	if(file_exists($tpl))
		$this->setTemplate($itf,'customtemp::' . $id);
   }
  }

  public function sendEvent($value) {
   if (($this->getLogicalId() == 'picture') || ($this->execCmd() != $this->formatValue($value))) {
	$this->event($value, null);
	return true;
   }
   return false;
  }

  public function getGenericType () {
   $type = $this->getType();
   $id = $this->getLogicalId();
   if ($type == 'info') {
        if ($id == 'battery')
            return 'BATTERY';
        else if (($id == 'guardMode') || ($id == 'currentMode'))
            return 'ALARM_MODE';
        else if ($id == 'enabled')
            return 'ALARM_ARMED';
        else if (stripos($id, 'volume') !== false)
            return 'VOLUME';
	else if ((stripos($id, 'alarm') !== false) || (stripos($id, 'state') !== false))
	    return 'ALARM_STATE';
        else if (stripos($id, 'url') !== false)
            return 'CAMERA_URL';
        else if (stripos($id, 'recording') !== false)
            return 'CAMERA_RECORD';
        else if (stripos($id, 'power') !== false)
            return 'POWER';
        else
            return 'GENERIC_INFO';
   } else {
        if (stripos($id, 'guardMode') !== false)
            return 'ALARM_SET_MODE';
        else if (stripos($id, 'volume') !== false)
            return 'SET_VOLUME';
        else
            return 'GENERIC_ACTION';
   }
  }

  // Exécution d'une commande
  public function execute($_options = array()) {
//  log::add('eufy', 'debug', '>>>> $_options: ' . json_encode($_options));
    $eqLogic = $this->getEqLogic();
    $cmd = $this->getLogicalId();
    $itf =  $this->getConfiguration('interface');
    $serialNumber= $eqLogic->getConfiguration('serialNumber');
    log::add('eufy', 'debug',  '['. __FUNCTION__ . '] ' . $itf . '.' . $cmd);

    if ($cmd == 'refresh')
	$eqLogic->refreshDevice();
    else {
        $enable = preg_replace("/:on$/", "", $cmd);
        $disable = preg_replace("/:off$/", "", $cmd);
        $set = preg_replace("/:set.*$/", "", $cmd);
	$str = preg_replace("/:[0-9]/","",$cmd);
	$prop = preg_replace("/:.*/","", $str);
	$action = preg_replace("/.*:/","", $str);
	$value = preg_replace("/[^0-9.]/","", $cmd);

  	if ($enable != $cmd)
	   $params = array('messageId' => $serialNumber,'command' => $itf . '.set_property', 'serialNumber' => $serialNumber, 'name' => $enable, 'value' => 'true');
   	else if ($disable != $cmd)
           $params = array('messageId' => $serialNumber,'command' => $itf . '.set_property', 'serialNumber' => $serialNumber, 'name' => $disable, 'value' => 'false');
	else if ($set != $cmd) {
	   switch($this->getSubType()) {
		case 'slider':
		   $value = $_options['slider']; // slider
		   break;
		case 'select':
		   $value = $_options['select']; // combo list
		   break;
		case 'color':
		   $value = $_options['color']; // couleur
		    $value = eufyUtils::hex2rgb($value);
		   break;
		case 'other':
		    $value = intval($this->getConfiguration('value'));
	   }
           $params = array('messageId' => $serialNumber, 'command' => $itf . '.set_property', 'serialNumber' => $serialNumber, 'name' => $set, 'value' => $value);
	}
	else if ($value != "") {
	   if (is_numeric($value)) $value = intval($value);
	   $params = array('messageId' => $serialNumber,'command' => $itf . '.' . $action , 'serialNumber' => $serialNumber, $prop => $value);
	}
	else // action command without parms
	   $params = array('messageId' => $serialNumber,'command' => $itf . '.' . $action, 'serialNumber' => $serialNumber);

   	log::add('eufy', 'debug', '> send to daemon: ' . json_encode($params));
        eufy::sendToDaemon($params);
    }
  }
}
