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

$deamonRunning = eufy::deamonRunning();
?>

<form class="form-horizontal">
  <fieldset>

    <div class="col-lg-6">
       <div class="form-group">
           <label class="col-lg-4 control-label">{{Mode}}
         	<sup><i class="fas fa-question-circle tooltips" title="{{Mode du service Eufy}}"></i></sup>
           </label>
           <div class="col-lg-4">
           	<select class="configKey form-control" data-l1key="mode">
             		<option value="local">{{docker local}}</option>
             		<option value="remote">{{docker distant}}</option>
           	</select>
            </div>
        </div>
	<p/>
        <div class="form-group">
            <label class="col-lg-4 control-label">{{IP Docker}}</label>
            <div class="col-lg-4">
                <input class="configKey form-control" data-l1key="containerip" />
            </div>
        </div>
        <div class="form-group">
            <label class="col-lg-4 control-label">{{Port Docker}}</label>
            <div class="col-lg-4">
                <input class="configKey form-control" data-l1key="containerPort" />
            </div>
        </div>
        <div class="form-group eufyMode local">
            <label class="col-lg-4 control-label">{{Device}}</label>
            <div class="col-lg-4">
                <input class="configKey form-control" data-l1key="devicename"/>
            </div>
        </div>
        <div class="form-group eufyMode local">
            <label class="col-lg-4 control-label">{{User}}</label>
            <div class="col-lg-4">
                <input class="configKey form-control" data-l1key="username" />
            </div>
        </div>
        <div class="form-group eufyMode local">
            <label class="col-lg-4 control-label">{{Password}}</label>
            <div class="col-lg-4">
                <input class="configKey inputPassword form-control" data-l1key="password"/>
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
    </div>

    <div class="col-lg-6"> 
        <div class="form-group eufyMode local">
            <label class="col-md-4 control-label tooltips">{{Setup}}
                <sup><i class="fas fa-question-circle tooltips" title="{{Setup du container Eufy}}"></i></sup>
 	    </label>
            <div class="col-md-8">
             <a class="btn btn-xs btn-warning" id="bt_installEufy"><i class="fas fa-plus-square"></i> {{Installer Eufy}}</a>
             <a class="btn btn-xs btn-danger" id="bt_uninstallEufy"><i class="fas fa-minus-square"></i> {{Désinstaller Eufy}}</a>
             <a class="btn btn-xs btn-primary" id="bt_startEufy"><i class="fas fa-play"></i> {{Démarrer Eufy}}</a>
             <a class="btn btn-xs btn-primary" id="bt_stopEufy"><i class="fas fa-stop"></i> {{Arrêter Eufy}}</a>
            </div>
        </div>

     </div>
  </fieldset>
</form>
<script>
$('.configKey[data-l1key=mode]').off('change').on('change', function() {
    $('.eufyMode').hide()
    $('.eufyMode.' + $(this).value()).show()
})

$('#bt_installEufy').off('click').on('click', function() { 
	$.ajax({ type: "POST", url: "plugins/eufy/core/ajax/eufy.ajax.php", 
		data: {
			action: "installEufy"
		},
      		dataType: 'json',
		error: function(error) { 
			$.fn.showAlert({ message: error.message, level: 'danger'
        		})
      		},
      		success: function(data) { 
			if (data.state != 'ok') { 
				$.fn.showAlert({ message: data.result, level: 'danger'
          			})
          			return
        		} else {
          			$('.pluginDisplayCard[data-plugin_id=' + $('#span_plugin_id').text() + ']').click() 
				$.fn.showAlert({ 
					message: '{{Installation en cours}}', 
					level: 'success', 
					emptyBefore: true
          			})
        		}
      		}
	})
})
$('#bt_uninstallEufy').off('click').on('click', function() {
        $.ajax({ type: "POST", url: "plugins/eufy/core/ajax/eufy.ajax.php",
                data: {
                        action: "uninstallEufy"
                },
                dataType: 'json',
                error: function(error) {
                        $.fn.showAlert({ message: error.message, level: 'danger'
                        })
                },
                success: function(data) {
                        if (data.state != 'ok') {
                                $.fn.showAlert({ message: data.result, level: 'danger'
                                })
                                return
                        } else {
                                $('.pluginDisplayCard[data-plugin_id=' + $('#span_plugin_id').text() + ']').click()
                                $.fn.showAlert({
                                        message: '{{Désinstallation en cours}}',
                                        level: 'success',
                                        emptyBefore: true
                                })
                        }
                }
        })
})

$('#bt_startEufy').off('click').on('click', function() {
        $.ajax({ type: "POST", url: "plugins/eufy/core/ajax/eufy.ajax.php",
                data: {
                        action: "startEufy"
                },
                dataType: 'json',
                error: function(error) {
                        $.fn.showAlert({ message: error.message, level: 'danger'
                        })
                },
                success: function(data) {
                        if (data.state != 'ok') {
                                $.fn.showAlert({ message: data.result, level: 'danger'
                                })
                                return
                        } else {
                                $('.pluginDisplayCard[data-plugin_id=' + $('#span_plugin_id').text() + ']').click()
                                $.fn.showAlert({
                                        message: '{{Démarrage en cours}}',
                                        level: 'success',
                                        emptyBefore: true
                                })
                        }
                }
        })
})

$('#bt_stopEufy').off('click').on('click', function() {
        $.ajax({ type: "POST", url: "plugins/eufy/core/ajax/eufy.ajax.php",
                data: {
                        action: "stopEufy"
                },
                dataType: 'json',
                error: function(error) {
                        $.fn.showAlert({ message: error.message, level: 'danger'
                        })
                },
                success: function(data) {
                        if (data.state != 'ok') {
                                $.fn.showAlert({ message: data.result, level: 'danger'
                                })
                                return
                        } else {
                                $('.pluginDisplayCard[data-plugin_id=' + $('#span_plugin_id').text() + ']').click()
                                $.fn.showAlert({
                                        message: '{{Arrêt en cours}}',
                                        level: 'success',
                                        emptyBefore: true
                                })
                        }
                }
        })
})

$('#bt_testEufy').off('click').on('click', function() {
        $.ajax({ type: "POST", url: "plugins/eufy/core/ajax/eufy.ajax.php",
                data: {
                        action: "testEufy"
                },
                dataType: 'json',
                error: function(error) {
                        $.fn.showAlert({ message: error.message, level: 'danger'
                        })
                },
                success: function(data) {
                        if (data.state != 'ok') {
                                $.fn.showAlert({ message: data.result, level: 'danger'
                                })
                                return
                        } else {
                                $('.pluginDisplayCard[data-plugin_id=' + $('#span_plugin_id').text() + ']').click()
//                                window.location.reload()
                        }
                }
        })
})


</script>
