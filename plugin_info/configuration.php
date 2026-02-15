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

require_once dirname(__FILE__) . '/../../../core/php/core.inc.php';
include_file('core', 'authentification', 'php');
//include_file('desktop', 'config', 'js', 'eufy');

if (!isConnect()) {
  include_file('desktop', '404', 'php');
  die();
}
?>

<style>
  .warning-tooltip {
    color: var(--al-warning-color);
  }

</style>

<form class="form-horizontal">
  <fieldset>
    <div class="col-lg-6">
       <div class="form-group">
           <label class="col-lg-4 control-label">{{Mode}}
         	<sup><i class="fas fa-question-circle tooltips" title="{{Mode du service Eufy}}"></i></sup>
           </label>
           <div class="col-lg-4">
           	<select class="configKey form-control" data-l1key="eufyMode">
             		<option value="local">{{docker local}}</option>
             		<option value="remote">{{docker distant}}</option>
           	</select>
            </div>
        </div>
	<p/>
        <div class="form-group eufyMode local">
            <label class="col-lg-4 control-label">{{Device}}
		<sup><i class="fa fa-question-circle tooltips" title="{{Nom du device utilisé pour l'app Eufy}}"></i></sup>
	    </label>
            <div class="col-lg-4">
                <input class="configKey form-control" data-l1key="deviceName"/>
            </div>
        </div>
        <div class="form-group eufyMode local">
            <label class="col-lg-4 control-label">{{Utilisateur}}</label>
            <div class="col-lg-4">
                <input class="configKey form-control" data-l1key="username" />
            </div>
        </div>
        <div class="form-group eufyMode local">
            <label class="col-lg-4 control-label">{{Mot de passe}}</label>
            <div class="col-lg-4">
                <input class="configKey inputPassword form-control" data-l1key="password"/>
            </div>
        </div>
        <div class="form-group eufyMode local">
            <label class="col-lg-4 control-label">{{Version cible}}
                <sup><i class="fas fa-question-circle tooltips" title="{{Version du container eufy à installer, défaut: dernière version}}"></i></sup>
	    </label>
            <div class="col-lg-4">
                <input class="configKey form-control" data-l1key="targetVersion"/>
            </div>
        </div>
	<p/>
        <div class="form-group">
            <label class="col-md-4 control-label tooltips" style="position:relative;top:-5px;">{{Communication}}
                <sup><i class="fas fa-question-circle tooltips" title="{{Communication avec container / Cloud Eufy}}"></i></sup>
            </label>
            <div class="col-md-7">
              <?php
                if (!eufy::isListening())
                        echo '<span class="col-sm-1 label label-danger">NOK</span>';
                else
                        echo '<span class="col-sm-1 label label-success">OK</span>';
                echo '<span class="col-sm-1"></span>';
                if (!eufy::isOnline())
                        echo '<span class="col-sm-1 label label-danger">NOK</span>';
                else
                        echo '<span class="col-sm-1 label label-success">OK</span>';
              ?>
	      <span class="col-sm-1"></span>
              <a class="btn btn-xs btn-primary" id="bt_testEufy" style="position:relative;top:-5px;">
                        <i class="fas fa-sync"></i> {{Tester}}</a>
            </div>
        </div>
        <div class="form-group">
            <label class="col-md-4 control-label tooltips" style="position:relative;top:-5px;">{{Version installée}}
                <sup><i class="fas fa-question-circle tooltips" title="{{Version installée du container Eufy}}"></i></sup>
            </label>
            <div class="col-md-7">
		<span class="col-sm-1 label">
              		<?php echo cache::byKey('eufy::version')->getValue(); ?>
		</span>
            </div>
        </div>

    </div>
    <div class="col-lg-6">
        <div class="form-group">
           <label class="col-lg-4 control-label">{{Container Docker}}&nbsp;
             <sup><i class="fa fa-question-circle tooltips" title="{{Image docker eufy-security-ws}}"></i></sup>
           </label>
           <div class="col-lg-6 input-group">
             <span class="input-group-addon">ws://</span>
             <input class="configKey form-control tooltips" data-l1key="container" data-l2key="host" placeholder="localhost"
                title="{{Adresse IP du container. Défaut: localhost}}"/>
             <span class="input-group-addon">:</span>
             <input class="configKey form-control tooltips" data-l1key="container" data-l2key="port" placeholder="3000"
                type="number" min="1" max="65535" title="{{Port du container. Défaut: 3000}}"/>
             </div>
        </div>
	<div class="form-group eufyMode local">
	   <label class="col-md-4 control-label">{{Réseau mode host}}
		<sup><i class="fas fa-question-circle tooltips" title="{{Mode réseau docker: bridge (défaut) ou host}}"></i></sup>
	   </label>
	   <div class="col-md-1">
                <input type="checkbox" class="configKey" data-l1key="host_mode" unchecked>
	   </div>
	</div>
        <div class="form-group">
           <label class="col-lg-4 control-label">{{Port socket deamon}}&nbsp;
             <sup><i class="fas fa-exclamation-triangle tooltips warning-tooltip"
                title="{{Port du deamon eufyd. Ne pas modifier sauf en cas de conflit}}"></i></sup>
           </label>
           <div class="col-lg-6">
             <input class="configKey form-control" data-l1key="socketPort" type="number" min="1" max="65535" placeholder="60600"/>
           </div>
        </div>
        <div class="form-group eufyMode local">
            <label class="col-md-4 control-label tooltips">{{Setup docker}}
                <sup><i class="fas fa-question-circle tooltips" title="{{Gestion de l'image Eufy}}"></i></sup>
 	    </label>
            <div class="col-md-8">
             <a class="btn btn-xs btn-primary" id="bt_installEufy"><i class="fas fa-plus-square"></i> {{Installer}}</a>
	     <a class="btn btn-xs btn-primary" id="bt_restartEufy"><i class="fas fa-play"></i> {{Redémarrer}}</a>
             <a class="btn btn-xs btn-danger" id="bt_uninstallEufy"><i class="fas fa-minus-square"></i> {{Désinstaller}}</a>
             <a class="btn btn-xs btn-warning" id="bt_upgradeEufy"><i class="fas fa-sync"></i> {{Upgrader}}</a>
            </div>
        </div>
     </div>
  </fieldset>
</form>
<?php
include_file('core', 'config', 'js', 'eufy');
?>
