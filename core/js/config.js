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

$('.configKey[data-l1key=eufyMode]').off('change').on('change', function() {
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

$('#bt_restartEufy').off('click').on('click', function() {
        $.ajax({ type: "POST", url: "plugins/eufy/core/ajax/eufy.ajax.php",
                data: {
                        action: "restartEufy"
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
                                        message: '{{Redémarrage en cours}}',
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

$('#bt_upgradeEufy').off('click').on('click', function() {
        $.ajax({ type: "POST", url: "plugins/eufy/core/ajax/eufy.ajax.php",
                data: {
                        action: "upgradeEufy"
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
                                        message: '{{Upgrade en cours}}',
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

$('.configKey[data-l1key=host_mode]').off('change').on('change', function() {
    if ($(this).value() == 1) {
	var port = document.querySelector('.configKey[data-l2key="port"]')
	port.value = 3000
    }
})

$('body').off('eufy::dependancy_end').on('eufy::dependancy_end', function(_event, _options) {
  window.location.reload();
})
