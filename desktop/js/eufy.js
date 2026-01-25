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


function buildCmd(_cmd) {
  let tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">'
  tr += '<td class="hidden-xs">'
  tr += '<span class="cmdAttr" data-l1key="id"></span>'
  tr += '</td>'
  tr += '<td>'
  tr += '<div class="input-group">'
  tr += '<input class="cmdAttr form-control input-sm roundedLeft" data-l1key="name" placeholder="{{Nom de la commande}}">'
  tr += '<span class="input-group-btn"><a class="cmdAction btn btn-sm btn-default" data-l1key="chooseIcon" title="{{Choisir une icône}}"><i class="fas fa-icons"></i></a></span>'
  tr += '<span class="cmdAttr input-group-addon roundedRight" data-l1key="display" data-l2key="icon" style="font-size:19px;padding:0 5px 0 0!important;"></span>'
  tr += '</div>'
  tr += '<select class="cmdAttr form-control input-sm" data-l1key="value" style="display:none;margin-top:5px;" title="{{Commande info liée}}">'
  tr += '<option value="">{{Aucune}}</option>'
  tr += '</select>'
  tr += '</td>'
  tr += '<td>'
  tr += '<span class="type" type="' + init(_cmd.type) + '">' + jeedom.cmd.availableType() + '</span>'
  tr += '<span class="subType" subType="' + init(_cmd.subType) + '"></span>'
  tr += '</td>'

  tr += '<td>'
  tr += '<span class="cmdAttr" data-l1key="htmlstate"></span>'

  if (init(_cmd.type) == 'action') {
    if ((_cmd.subType == 'other') && (_cmd.logicalId.endsWith(':set')))
	tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="value" placeholder="{{Valeur}}">'
    else if (_cmd.subType == 'slider') {
	 tr += '<div style="margin-top:7px; display: flex">'
	tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="minValue" placeholder="{{Min}}" title="{{Min}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">'
	tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="maxValue" placeholder="{{Max}}" title="{{Max}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">'
    }
    else if (_cmd.subType == 'select')
	tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="listValue" placeholder="{{Liste : valeur|texte (séparées par un point-virgule)}}" title="{{Liste : valeur|texte}}">'
  }
  tr += '</td>'

  tr += '<td>'
  tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isVisible" checked/>{{Afficher}}</label> '
  tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="isHistorized" checked/>{{Historiser}}</label> '
  tr += '<label class="checkbox-inline"><input type="checkbox" class="cmdAttr" data-l1key="display" data-l2key="invertBinary"/>{{Inverser}}</label> '
  tr += '<div style="margin-top:7px; display: flex">'
  if (init(_cmd.unite) != '')
     tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="unite" placeholder="Unité" title="{{Unité}}" style="width:30%;max-width:80px;display:inline-block;margin-right:2px;">'

  tr += '</div>'
  tr += '</td>'
  tr += '<td><div>'
  if (is_numeric(_cmd.id)) {
    tr += '<a class="btn btn-default btn-xs cmdAction" data-action="configure"><i class="fas fa-cogs"></i></a> '
    tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fas fa-rss"></i> Tester</a>'
  }
  tr += '<i class="fas fa-minus-circle pull-right cmdAction cursor" data-action="remove" title="{{Supprimer la commande}}"></i>'
  tr += '</div></td></tr>'
  return tr;
}

function displayCmd(_cmd, _tr) {
    jeedom.eqLogic.buildSelectCmd ({
        id: $('.eqLogicAttr[data-l1key=id]').value(),
        filter: {type: 'info'},
        error: function (error) {
                alert ('error in displayCmd cmdid=' + $('.eqLogicAttr[data-l1key=id]').value());
                $('#div_alert').showAlert({message: error.message, level: 'danger'});
        },
        success: function (result) {
// alert ('prop: ' + _cmd.logicalId)
                _tr.find('.cmdAttr[data-l1key=value]').append(result);
                _tr.setValues(_cmd, '.cmdAttr');
                jeedom.cmd.changeType(_tr, init(_cmd.subType));
        }
    });
}

function addCmdToTable(_cmd) {
    if (!isset(_cmd))
        var _cmd = { configuration: {} }
    if (!isset(_cmd.configuration))
        _cmd.configuration = {}

    if (isset(_cmd.configuration.other)) {
	$('#other_table tbody').append(buildCmd(_cmd))
	tr = $('#other_table tbody tr').last()
    } else {
	if (!isset(_cmd.configuration.interface))
	    return;
// alert ('prop station: ' + _cmd.logicalId + ' itf:' + _cmd.configuration.interface)
        if (_cmd.configuration.interface == 'device') {
	    $('#device_table tbody').append(buildCmd(_cmd))
	    var tr = $('#device_table tbody tr').last()
	} else if (_cmd.configuration.interface == 'station') {
	    $('#station_table tbody').append(buildCmd(_cmd))
	    var tr = $('#station_table tbody tr').last()
// alert (JSON.stringify($('#station_table tbody tr')))
	}
    }
    displayCmd(_cmd,tr);
}

function updatePicture (model) {
   //alert('updatePicture: ' + model);
   $.ajax({
      type: "POST",
      url: "plugins/eufy/core/ajax/eufy.ajax.php",
      data: {
          action: "getPicture",
          model : model
      },
      dataType: 'json',
      error: function (request, status, error) {
          handleAjaxError(request, status, error);
      },
      success: function (data) {
        if (data.state != 'ok') {
                $('#div_alert').showAlert({ message: data.result, level: 'danger' });
                return;
        }
        $('#device_pic').attr("src", data.result);
      }
  });
}


$('#bt_syncEufy').on('click', function () {
  $.ajax({
      type: "POST",
      url: "plugins/eufy/core/ajax/eufy.ajax.php",
      data: {
          action: "sync",
      },
      dataType: 'json',
      error: function (request, status, error) {
          handleAjaxError(request, status, error);
      },
      success: function (data) {
          if (data.state != 'ok') {
              $('#div_alert').showAlert({ message: data.result, level: 'danger' });
              return;
          }
          $('#div_alert').showAlert({ message: '{{Synchronisation terminée.}}', level: 'success' });
          setTimeout(function () {
              window.location.replace("index.php?v=d&m=eufy&p=eufy");
          }, 10000);
      }
  });
});


$('#bt_discoEufy').on('click', function () {
  $.ajax({
      type: "POST",
      url: "plugins/eufy/core/ajax/eufy.ajax.php",
      data: {
          action: "disco",
      },
      dataType: 'json',
      error: function (request, status, error) {
          handleAjaxError(request, status, error);
      },
      success: function (data) {
          if (data.state != 'ok') {
              $('#div_alert').showAlert({ message: data.result, level: 'danger' });
              return;
          }
          $('#div_alert').showAlert({ message: '{{Auto-découverte terminée.}}', level: 'success' });
          setTimeout(function () {
              window.location.replace("index.php?v=d&m=eufy&p=eufy");
          }, 10000);
      }
  });
});


$('#bt_healtheufy').on('click', function () {
  $('#md_modal').dialog({title: "{{Santé Eufy}}"});
  $('#md_modal').load('index.php?v=d&plugin=eufy&modal=health').dialog('open');
});

$('#bt_upload_pic').on('click', function () {
    var model = $(this).closest('.form-horizontal').find('.eqLogicAttr[data-l2key="eufyModel"]').value();
    // var uid = $(this).closest('.form-horizontal').find('.eqLogicAttr[data-l1key="logicalId"]').value();
    // alert('bt_upload_pic uid= '+ uid)
    $(this).fileupload({
        dataType: 'json',
        url: 'plugins/eufy/core/ajax/eufy.ajax.php?action=uploadPicture&model=' + model,
        replaceFileInput: false,
        error: function (request, status, error) {
            handleAjaxError(request, status, error);
        },
        done: function (e,data) {
            if (data.result.state != 'ok') {
                $('#div_alert').showAlert({ message: data.result.result, level: 'danger' });
                return;
            }
            updatePicture(model);
        }
    });
});


$('#bt_reset_pic').on('click', function () {
  var model = $(this).closest('.form-horizontal').find('.eqLogicAttr[data-l2key="eufyModel"]').value();
  // var uid = $(this).closest('.form-horizontal').find('.eqLogicAttr[data-l1key="logicalId"]').value();
    $.ajax({
        type: "POST",
        url: "plugins/eufy/core/ajax/eufy.ajax.php",
        data: {
            action: "resetPicture",
            model: model
        },
        dataType: 'json',
        error: function (request, status, error) {
            handleAjaxError(request, status, error);
        },
        success: function (data) {
            if (data.state != 'ok') {
                $('#div_alert').showAlert({ message: data.result, level: 'danger' });
                return;
            }
            updatePicture(model);
        }
    });
});

$('.refreshBtn[data-action=refresh]').on('click',function() {
   $('#md_modal').dialog('close');
   $('#md_modal').load('index.php?v=d&plugin=eufy&modal=health').dialog('open');
});


$("#table_cmd").sortable({
  axis: "y",
  cursor: "move",
  items: ".cmd",
  placeholder: "ui-state-highlight",
  tolerance: "intersect",
  forcePlaceholderSize: true
})

setTimeout(() => {
  $('.eqLogicAction[data-action=returnToThumbnailDisplay]').removeAttr('href').off('click').on('click', function(event) {
    // contournement du plugin.template du core
    // force un load page lors du click sur returnToThumbnailDisplay
    event.preventDefault()
    jeedomUtils.loadPage('index.php?v=d&m=eufy&p=eufy', false)
  })
}, "500");


function printEqLogic(_eqLogic) {
  if (isset(_eqLogic.configuration)) {
//	alert ('config:' + JSON.stringify(_eqLogic.configuration.interfaces))
    const itfs = _eqLogic.configuration.interfaces
    if (! isset(itfs))
	return
    if (itfs.includes('device'))
	$('.deviceCmds').show()
    else
	$('.deviceCmds').hide()
    if (itfs.includes('station'))
	$('.stationCmds').show()
    else
	$('.stationCmds').hide()
  }
  updatePicture (_eqLogic.configuration.eufyModel);
  // lance une tempo pour laisser le temps au core d'executer tous les addCmdToTable
  setTimeout(() => {
    $('table.tablesorter').trigger('update') // update de tablesorter
  }, "1000");
}

