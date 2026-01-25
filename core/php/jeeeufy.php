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
     $str = file_get_contents("php://input");
//   log::add('eufy', 'debug','>>> Eufyd message received: ' . $str);
     $msg = json_decode($str, true);

     if ((!is_array($msg)) || (! isset($msg['type'])))
        die();

     if (($msg['type'] == 'result') && (isset($msg['success'])))
	eufy::handleResult($msg);
     else if (($msg['type'] == 'event') && (isset($msg['event'])))
	eufy::handleEvent ($msg['event']);
     else
        log::add('eufy', 'warning', 'unmanaged message received from deamon: ' . $str);
}
catch (Exception $e) {
    log::add('eufy', 'error', displayException($e));
}
?>
