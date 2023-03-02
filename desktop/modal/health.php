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

if (!isConnect('admin')) {
	throw new Exception('401 - Accès non autorisé');
}
$plugin = plugin::byId('eufy');
$eqLogics = eufy::byType('eufy');
?>
<table class="table table-condensed tablesorter" id="table_healtheufy">
	<thead>
		<tr>
			<th>{{Image}}</th>
			<th>{{Module}}</th>
			<th>{{ID}}</th>
                        <th>{{N° de série}}</th>
			<th>{{Modèle}}</th>
			<th>{{Type}}</th>
			<th>{{Présent}}</th>
			<th>{{Batterie}}</th>
			<th>{{Dernière communication}}</th>
			<th>{{Date création}}</th>
		</tr>
	</thead>
	<tbody>
<?php
foreach ($eqLogics as $eqLogic) {
	if (file_exists(dirname(__FILE__) . '/../../core/config/devices/' . $eqLogic->getConfiguration('eufyModel') . '.png')) {
		$image = '<img src="plugins/eufy/core/config/devices/' . $eqLogic->getConfiguration('eufyModel') . '.png' . '" height="55" width="55" />';
	} else {
		$image = '<img src="' . $plugin->getPathImgIcon() . '" height="55" width="55" />';
	}
	echo '<tr><td>' . $image . '</td><td><a href="' . $eqLogic->getLinkToConfiguration() . '" style="text-decoration: none;">' . $eqLogic->getHumanName(true) . '</a></td>';
	echo '<td><span class="label label-info" style="font-size : 1em; cursor : default;">' . $eqLogic->getId() . '</span></td>';
	echo '<td><span class="label label-info" style="font-size : 1em; cursor : default;">' . $eqLogic->getConfiguration('serialNumber') . '</span></td>';
        echo '<td><span class="label label-info" style="font-size : 1em; cursor : default;">' . $eqLogic->getConfiguration('eufyModel') . '</span></td>';
        echo '<td><span class="label label-info" style="font-size : 1em; cursor : default;">' . $eqLogic->getConfiguration('eufyType') . '</span></td>';

        $present = $eqLogic->getCmd('info','present');
	$status = '<span class="label label-danger" style="font-size : 1em; cursor : default;">{{NOK}}</span>';
        if ((is_object($present)) && ($present->execCmd()))
           $status = '<span class="label label-success" style="font-size : 1em; cursor : default;">{{OK}}</span>';
 	echo '<td>' . $status . '</td>';

	$battery_status = '<span class="label label-success" style="font-size : 1em;">{{OK}}</span>';
	$bat = $eqLogic->getCmd('info','battery');
        if (is_object($bat))
                $battery= $bat->execCmd();
        else
                $battery = '';

	if ($battery == '') 
		$battery_status = '<span class="label label-primary" style="font-size : 1em;" title="{{Secteur}}"><i class="fas fa-plug"></i></span>';
  	elseif ($battery < 20)
		$battery_status = '<span class="label label-danger" style="font-size : 1em;">' . $battery . '%</span>';
	elseif ($battery < 60)
		$battery_status = '<span class="label label-warning" style="font-size : 1em;">' . $battery . '%</span>';
	elseif ($battery > 60)
		$battery_status = '<span class="label label-success" style="font-size : 1em;">' . $battery . '%</span>';
	else
		$battery_status = '<span class="label label-primary" style="font-size : 1em;">' . $battery . '%</span>';

	echo '<td>' . $battery_status . '</td>';
	echo '<td><span class="label label-info" style="font-size : 1em; cursor : default;">' . $eqLogic->getStatus('lastCommunication') . '</span></td>';
	echo '<td><span class="label label-info" style="font-size : 1em; cursor : default;">' . $eqLogic->getConfiguration('createtime') . '</span></td>';
}
?>
	</tbody>
</table>
