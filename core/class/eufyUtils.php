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
class eufyUtils {

  public static function initConfig() {
    $mode = config::byKey('eufyMode', 'eufy');
    log::add('eufy', 'debug', '[' . __FUNCTION__ .'] mode: ' . $mode);
    $f = __DIR__ . '/../../resources/eufy';
    if (file_exists($f))
	chmod ($f, 755);
    self::initContainerSettings();
    config::save('socketPort','60600', 'eufy');
    if (empty($mode)) {
        config::save('eufyMode','local', 'eufy');
        config::save('targetVersion', 'latest', 'eufy');
        cache::set('eufy::online',false);
        eufyUtils::updateYaml();
    }
  }

  public static function initContainerSettings() {
    $container = config::byKey('container','eufy',array());
    if (empty($container)) {
	$container['host'] = 'localhost';
	$container['port'] = '3000';
	config::save('container',json_encode($container),'eufy');
    }
    return $container;
  }

  public static function getPicture($model) {
    $f = __DIR__ . '/../../data/tmp/' . $model . '.png';
    if (file_exists($f))
        return 'plugins/eufy/data/tmp/' . $model . '.png' .'?ts=' . @filemtime($f);
    else {
	$f = __DIR__ . '/../../core/config/devices/' . $model . '.png';
	if (file_exists($f))
            return 'plugins/eufy/core/config/devices/' . $model . '.png' .'?ts=' . @filemtime($f);
	else {
	    $f = __DIR__ . '/../../plugin_info/eufy_icon.png';
            return 'plugins/eufy/plugin_info/eufy_icon.png' .'?ts=' . @filemtime($f);
	}
    }
  }

  public static function uploadPicture ($pic, $model) {
    //log::add('eufy', 'debug', '[' . __FUNCTION__ . '] pic=' . $pic['name'] . ' modèle=' . $model);
    $dir = __DIR__ . '/../../data/tmp/';
    if (!file_exists($dir))
        mkdir ($dir,0755,true);

    $f = file_get_contents($pic['tmp_name']);
    file_put_contents($dir . $model . '.png', $f);
  }

  public static function resetPicture ($model) {
    // log::add('eufy', 'debug', '[' . __FUNCTION__ . '] modèle=' . $model);
    $fname = __DIR__ . '/../../data/tmp/' . $model . '.png';

    if (file_exists($fname))
        unlink($fname);
  }

  public static function getPyPath() {
    if (method_exists('system', 'getCmdPython3')) // pour core < 4.4.7
        $pyExec = system::getCmdPython3('eufy');
    else
	$pyExec = '/usr/bin/python3';

    // log::add('eufy', 'debug', 'Py executable: ' . $pyExec);
    return $pyExec;
  }

  public static function getSchemaVersion()
  {
    $s = '';
    $v = cache::byKey('eufy::version')->getValue();
    $vv = (int) str_replace(".","",$v);
    switch ($vv) {
	case $vv >= 180:
		$s = '21'; break;
	case $vv >= 171:
                $s = '20'; break;
	default:
		throw new Exception(__("Version d'image eufy non supportée: ". $v, __FILE__));
    }
    log::add('eufy', 'debug', 'eufy-security-ws schema version: ' . $s);
    return $s;
  }

  public static function getPyCmdLine() {
    $osVersion = shell_exec("lsb_release -r|awk '{ print $2 }'");
    $pyExec = self::getPyPath();
    log::add('eufy', 'debug', 'debian OS version: ' . $osVersion);
    log::add('eufy', 'debug', 'Py executable: ' . $pyExec);
    $container = self::initContainerSettings();
    $schemaVersion = self::getSchemaVersion();
    $path = realpath(dirname(__FILE__) . '/../../resources/eufyd'); // répertoire du démon
    $cmd = $pyExec . ' ' . $path . '/eufyd.py'; // nom du démon
    $cmd .= ' --loglevel ' . log::convertLogLevel(log::getLogLevel('eufy'));
    $cmd .= ' --socketport ' . config::byKey('socketPort', 'eufy', '60600');
    $cmd .= ' --callback ' . network::getNetworkAccess('internal', 'proto:127.0.0.1:port:comp') . '/plugins/eufy/core/php/jeeeufy.php';
    $cmd .= ' --host "' . trim(str_replace('"', '\"', $container['host'])) . '"'; // on rajoute les paramètres utiles à votre démon
    $cmd .= ' --port "' . trim(str_replace('"', '\"', $container['port'])) . '"'; // second parametre
    $cmd .= ' --schemaversion "' . trim(str_replace('"', '\"', $schemaVersion)) . '"';
    $cmd .= ' --apikey ' . jeedom::getApiKey('eufy'); // l'apikey pour authentifier les échanges suivants
    $cmd .= ' --pid ' . jeedom::getTmpFolder('eufy') . '/deamon.pid'; // et on précise le chemin vers le pid file (ne pas modifier)
    return $cmd;
  }

  public static function list2enum($list)
  {
   $enum = "";
   foreach ($list as $key => $val) {
        $enum .= $key . '|' . $val ;
        if (next($list)) $enum .= ";";
   }
   // log::add('eufy', 'debug', '['. __FUNCTION__ . '] ' . json_encode($list) .' => ' . $enum);
   return $enum;
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

  public static function installDocker() {
   if ((shell_exec(system::getCmdSudo() . ' which docker | wc -l') != 1) ||
      (shell_exec(system::getCmdSudo() . " docker compose version|sed 's:.*version ::g'") == "")) {
        $msg = "*** Installation de docker, merci de patienter ***";
        event::add('jeedom::alert', array('level' => 'info', 'page' => 'eufy', 'message' => $msg));
        if (cache::exist('eufy::opInProgress'))
                $inProgress = cache::byKey('eufy::opInProgress')->getValue();
        else
                $inProgress = false;
        if ($inProgress)
                throw  new Exception(__("l'Opération " . $action . ' est déjà en cours, merci de patienter', __FILE__));
        cache::set('eufy::opInProgress', true);
        $log = log::getPathToLog('eufy_packages');
        shell_exec(system::getCmdSudo() . ' echo ' . $msg . ' >> ' . $log);
        shell_exec(system::getCmdSudo() . ' apt-get update' . ' >> ' . $log . ' 2>&1');
        shell_exec(system::getCmdSudo() . ' apt-get install ca-certificates curl' . ' >> ' . $log . ' 2>&1');
        shell_exec(system::getCmdSudo() . ' install -m 0755 -d /etc/apt/keyrings' . ' >> ' . $log . ' 2>&1');
        shell_exec(system::getCmdSudo() . ' curl -fsSL https://download.docker.com/linux/debian/gpg -o /etc/apt/keyrings/docker.asc' . ' >> ' . $log . ' 2>&1');
        shell_exec(system::getCmdSudo() . ' chmod a+r /etc/apt/keyrings/docker.asc');
        // Add the repository to Apt sources
        $file = '/etc/apt/sources.list.d/docker.list';
        $repo = 'deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/debian
		$(. /etc/os-release && echo "$VERSION_CODENAME") stable';
        shell_exec(system::getCmdSudo() . ' echo ' . $repo . ' > ' . $file);
        shell_exec(system::getCmdSudo() . ' apt-get update' . ' >> ' . $log . ' 2>&1');
        shell_exec(system::getCmdSudo() . ' apt-get install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin' . ' >> ' . $log . ' 2>&1');
        $msg = "*** Installation de docker terminée ***";
        shell_exec(system::getCmdSudo() . ' echo ' . $msg . ' >> ' . $log);
        event::add('jeedom::alert', array('level' => 'info', 'page' => 'eufy', 'message' => $msg));
        cache::set('eufy::opInProgress', false);
    } else
        log::add('eufy', 'debug', 'docker est déjà installé');
  }

  // init yaml file
  public static function updateYaml ()
  {
        $path= realpath(__DIR__ . '/../..');
        $store_dir = $path . '/data/store';
        $yaml = 'docker-compose.yml';
        $file = $path . '/data/' . $yaml ;

        log::add('eufy', 'debug', "Mise à jour du fichier yaml " . $file);
        $device = config::byKey('deviceName', 'eufy');
        $user = config::byKey('username', 'eufy');
        $passwd = config::byKey('password', 'eufy');
	$container = self::initContainerSettings();
        $port = $container['port'];
        $version = config::byKey('targetVersion', 'eufy');
        if (empty($device))
                throw new Exception(__('Nom du device non renseigné', __FILE__));
        else if (empty($user) or empty($passwd))
                throw new Exception(__('Login ou password non renseignés', __FILE__));
        if (empty($version))
                $version = 'latest';

        $compose = file_get_contents($path . '/resources/' . $yaml);
	$hostMode = config::byKey('host_mode', 'eufy');
	if ($hostMode) {
		$compose = str_replace('#network#', 'network_mode: host', $compose);
		$compose = str_replace('#ports#', '', $compose);
		$compose = str_replace('#port#', '', $compose);
	} else {
		$compose = str_replace('#network#', '', $compose);
		$compose = str_replace('#ports#', 'ports:', $compose);
		$compose = str_replace('#port#', '- '.$port.':3000', $compose);
	}
        $compose = str_replace('#store#', $store_dir, $compose);
        $compose = str_replace('#device#', $device, $compose);
        $compose = str_replace('#user#', $user, $compose);
        $compose = str_replace('#password#', $passwd, $compose);
        $compose = str_replace('#version#', $version, $compose);
        mkdir ($store_dir,0755,true);
        file_put_contents($file, $compose);
  }

  // eufy container management
  public static function setupContainer($action)
  {
        $msg = $action . ' du service eufy merci de patienter';
        log::add('eufy', 'info', ">>> Début de l'opération " . $action . ' du service Eufy');
        event::add('jeedom::alert', array('level' => 'info', 'page' => 'eufy',
                'message' => $msg));
        $eufy = 'bropat/eufy-security-ws';
        $yaml = realpath(__DIR__ . '/../..') . '/data/docker-compose.yml';
        $cid  = shell_exec(system::getCmdSudo() . " docker ps -a | grep -i eufy|awk '{ print $1 }'");
        $images = shell_exec(system::getCmdSudo() . ' docker images -q ' . $eufy );
        log::add('eufy', 'debug','images installées: '. $images);
        log::add('eufy', 'debug','container actif: '. $cid);
        $log = log::getPathToLog('eufy');
        if (cache::exist('eufy::opInProgress'))
                $inProgress = cache::byKey('eufy::opInProgress')->getValue();
        else
                $inProgress = false;
        try {
          if ($inProgress)
                throw  new Exception(__("l'Opération " . $action . ' est déjà en cours, merci de patienter', __FILE__));
          cache::set('eufy::opInProgress', true);
          $arch = shell_exec(system::getCmdSudo() . ' uname -m');
          $arch = str_replace("\n", "", $arch);
          log::add('eufy', 'debug', 'hardware arch: ' . $arch);
          switch ($action) {
            case 'install':
                if ($cid != "")
                        throw new Exception(__("Le container n'est pas arrêté", __FILE__));
                if ($images != "")
                         throw new Exception(__("L'image est déjà installée", __FILE__));
                $version = config::byKey('targetVersion', 'eufy');
                $cmd = 'docker pull ' . $eufy . ':' . $version;
                if ($arch == 'aarch64')
                        $cmd = 'DOCKER_DEFAULT_PLATFORM=linux/arm64 ' . $cmd;
                log::add('eufy', 'debug', $cmd);
                shell_exec(system::getCmdSudo() . ' ' . $cmd . ' >> ' . $log . ' 2>&1');
                break;
            case 'start':
                if ($images == "")
                        throw new Exception(__("L'image n'est pas installée", __FILE__));
                if ($cid != "")
                        throw new Exception(__('Le container est déjà démarré', __FILE__));
                self::updateYaml();
                $cmd = 'docker compose -f '. $yaml .' up -d';
                if ($arch == 'aarch64')
                        $cmd = 'DOCKER_DEFAULT_PLATFORM=linux/arm64 ' . $cmd;
                log::add('eufy', 'debug', $cmd);
                $cid = shell_exec(system::getCmdSudo() . ' ' . $cmd . ' >> ' . $log . ' 2>&1');
                log::add('eufy', 'debug','container id: '. $cid);
                sleep(3);
                self::checkContainer();
                self::testService();
                break;
             case 'stop':
                if ($cid == "")
                        throw new Exception(__("Le container n'est pas démarré", __FILE__));
                $cmd = 'docker rm -f ' . $cid;
                log::add('eufy', 'debug', $cmd);
                shell_exec(system::getCmdSudo() . ' ' . $cmd . ' >> ' . $log . ' 2>&1');
                sleep(3);
                self::checkContainer();
                self::testService();
                break;
             case 'uninstall':
                if ($cid != "")
                        throw new Exception(__("Le container n'est pas arrêté", __FILE__));
                $ids = explode(PHP_EOL, $images);
                foreach ($ids as $id)
                        if ($id != '') {
                                $cmd = 'docker rmi --force '. $id;
                                log::add('eufy', 'debug', $cmd);
                                shell_exec(system::getCmdSudo() . ' ' . $cmd . ' >> ' . $log . ' 2>&1');
                        }
                break;
          }
          log::add('eufy', 'info', ">>> Fin de l'opération " . $action . ' du service Eufy');
          event::add('jeedom::alert', array('level' => 'info', 'page' => 'eufy',
                'message' => $action . " terminé"));
        } catch (Exception $e) {
                event::add('jeedom::alert', array('level' => 'warning', 'page' => 'eufy',
                'message' => $e->getMessage()));
                log::add('eufy', 'warning', '>>> ' . $action . ' du service Eufy: ' . $e->getMessage());
        }
        cache::set('eufy::opInProgress', false);
  }

 // vérif si container up&running
  public static function checkContainer()
  {
	$container = self::initContainerSettings();
	//log::add('eufy', 'debug', '>>> Checking container ' . $container['host'] .':' . $container['port']);
	try {
	   $conn = @fsockopen($container['host'], $container['port']);
	   if (is_resource($conn)) {
		log::add('eufy', 'debug',  '[' . __FUNCTION__ .'] container ' . $container['host'] .':' . $container['port'] .' is listening');
		fclose($conn);
		cache::set('eufy::container_ok',true);
		return true;
	   }
	}
	catch (Exception $ex) {}
	log::add('eufy', 'debug',  '[' . __FUNCTION__ .'] container '. $container['host'] . ':'. $container['port'] . ' is not responding :(');
	cache::set('eufy::container_ok',false);
	return false;
  }


  public static function testContainer($option)
  {
	$container = self::initContainerSettings();
        $h = "'" . $container['host'] . "'";
        $p = "'" . $container['port'] . "'";
        // log::add('eufy', 'debug',  '>>> Testing EufyWS service on '. $host . ':' . $port);
        $python = self::getPyPath();
        $script =  __DIR__ . '/../../resources/test_eufy.py ';
	$cmdline = $script . $option . ' -u ' . $h . ':' . $p;
        $rc = shell_exec(system::getCmdSudo() . $python . ' ' . $cmdline .' 2>&1');
        // log::add('eufy', 'debug', '*** Test result '. $rc);
        return json_decode($rc);
  }

  public static function testService() {
        $jsonObj = self::testContainer("-v");
        $version = '';
        if (is_object($jsonObj))
                $version = $jsonObj->serverVersion;
        log::add('eufy', 'debug', 'eufy-security-ws image version: ' .   $version);
        cache::set('eufy::version',$version);

        $jsonObj = self::testContainer("-t");
        $online = False;
        if (is_object($jsonObj))
                $online = $jsonObj->result->state->driver->connected;
        log::add('eufy', 'debug', 'eufy-security-ws service online: ' . $online);
        cache::set('eufy::online', $online);
        return $online;
  }


}
