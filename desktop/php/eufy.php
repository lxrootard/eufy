<?php
if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}
$plugin = plugin::byId('eufy');
sendVarToJS('eqType', $plugin->getId());
$eqLogics = eqLogic::byType($plugin->getId());
$deamon_info = eufy::deamon_info();

if (!eufy::isOnline())
    echo "<div id='div_alert'><div class='alert alert-danger'  role='alert'>
	{{Le container eufy-ws-security n'est pas démarré ou pas connecté au Cloud Eufy}}</div></div>";
else if ($deamon_info['state'] != 'ok')
    echo "<div id='div_alert'><div class='alert alert-warning'  role='alert'>
	{{Le deamon eufyd n&apos;est pas démarré}}</div></div>";
    echo '<div id="div_alert"></div>';
?>

<div class="row row-overflow">
    <div class="col-xs-12 eqLogicThumbnailDisplay">
        <legend><i class="fas fa-cog"></i> {{Gestion}}</legend>
        <div class="eqLogicThumbnailContainer">
	    <div class="cursor eqLogicAction logoPrimary" data-action="gotoPluginConf">
		<i class="fas fa-wrench"></i>
		<br>
		<span>{{Configuration}}</span>
	    </div>
	    <div class="cursor logoSecondary" id="bt_discoEufy">
                <i class="fab fa-searchengin"></i>
                <br>
                <span>{{Auto-découverte}}</span>
            </div>
            <div class="cursor logoSecondary" id="bt_syncEufy">
                <i class="fas fa-sync"></i>
                <br>
                <span>{{Synchroniser}}</span>
            </div>
            <div class="cursor logoSecondary" id="bt_healtheufy">
          	<i class="fas fa-medkit"></i>
        	<br/>
        	<span>{{Santé}}</span>
            </div>
	    <div class="cursor pluginAction logoSecondary" data-action="openLocation"
		data-location="<?= $plugin->getDocumentation() ?>">
		<i class="fas fa-book"></i>
		<br>
		<span>{{Documentation}}</span>
	    </div>
	    <div class="cursor pluginAction logoSecondary"
		data-action="openLocation" data-location="https://community.jeedom.com/tag/plugin-<?= $plugin->getId() ?>">
		<i class="fas fa-comments"></i>
		<br>
		<span>{{Community}}</span>
	    </div>
	</div>


	<legend><i class="fas fa-table"></i> {{Mes équipements}}</legend>
        <div class="input-group" style="margin:5px;">
            <input class="form-control roundedLeft" placeholder="{{Rechercher}}" id="in_searchEqlogic" />
            <div class="input-group-btn">
                <a id="bt_resetSearch" class="btn roundedRight" style="width:30px"><i class="fas fa-times"></i></a>
            </div>
        </div>
        <div class="eqLogicThumbnailContainer">
            <?php
            foreach ($eqLogics as $eqLogic) {
                $opacity = ($eqLogic->getIsEnable()) ? '' : 'disableCard';
                echo '<div class="eqLogicDisplayCard cursor ' . $opacity . '" data-eqLogic_id="' . $eqLogic->getId() . '">';
		echo '<img src="' . $eqLogic->getImage() . '"/>';
                echo "<br>";
                echo '<span class="name">' . $eqLogic->getHumanName(true, true) . '</span>';
                echo '</div>';
            }
            ?>
        </div>
    </div>

    <div class="col-xs-12 eqLogic" style="display: none;">
        <div class="input-group pull-right" style="display:inline-flex">
            <span class="input-group-btn">
                <a class="btn btn-sm btn-default eqLogicAction roundedLeft" data-action="configure"><i class="fas fa-cogs"></i><span class="hidden-xs"> {{Configuration avancée}}</span>
                </a><a class="btn btn-sm btn-success eqLogicAction" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}
                </a><a class="btn btn-sm btn-danger eqLogicAction roundedRight" data-action="remove"><i class="fas fa-minus-circle"></i><span class="hidden-xs"> {{Supprimer}}</span></a>
            </span>
        </div>
        <ul class="nav nav-tabs" role="tablist">
            <li role="presentation"><a href="#" class="eqLogicAction" aria-controls="home" role="tab" data-toggle="tab" data-action="returnToThumbnailDisplay"><i class="fas fa-arrow-circle-left"></i></a></li>
            <li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-tachometer-alt"></i> {{Equipement}}</a></li>
            <li role="presentation" class="stationCmds"><a href="#stationtab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fas fa-box"></i> {{Commandes Station}}</a></li>
	    <li role="presentation" class="deviceCmds"><a href="#devicetab" aria-controls="profile" role="tab" data-toggle="tab"><i class="kiko-web-camera"></i> {{Commandes Caméra}}</a></li>
	    <li role="presentation"><a href="#othertab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fas fa-expand-arrows-alt"></i> {{Autres Commandes}}</a></li>
        </ul>

        <div class="tab-content"> <!-- style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;"> -->
            <div role="tabpanel" class="tab-pane active" id="eqlogictab">
                <br />
                <div class="row">
                    <div class="col-sm-7">
                        <form class="form-horizontal">
                            <fieldset>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">{{Nom de l'équipement}}</label>
                                    <div class="col-sm-3">
                                        <input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;" />
                                        <input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement}}" />
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">{{Objet parent}}</label>
                                    <div class="col-sm-3">
                                        <select id="sel_object" class="eqLogicAttr form-control" data-l1key="object_id">
                                            <option value="">{{Aucun}}</option>
                                            <?php
                                            $options = '';
                                            foreach ((jeeObject::buildTree(null, false)) as $object) {
                                                $options .= '<option value="' . $object->getId() . '">' . str_repeat('&nbsp;&nbsp;', $object->getConfiguration('parentNumber')) . $object->getName() . '</option>';
                                            }
                                            echo $options;
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label">{{Catégorie}}</label>
                                    <div class="col-sm-9">
                                        <?php
                                        foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value) {
                                            echo '<label class="checkbox-inline">';
                                            echo '<input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
                                            echo '</label>';
                                        }
                                        ?>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-sm-3 control-label"></label>
                                    <div class="col-sm-9">
                                        <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" />{{Activer}}</label>
                                        <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" />{{Visible}}</label>
                                    </div>
                                </div>
				<div>&nbsp;</div>
                                <div class="form-group stationCmds">
                                    <label class="col-sm-3 control-label">{{Contrôle Station}}</label>
                                    <div class="col-sm-9">
                                        <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="stationCmds" />{{Activer}}
                                            <sup><i class="fas fa-question-circle tooltips"
                                                title="{{Création des commandes optionnelles station. Uniquement pour les modèles supportés}}"></i></sup>
                                        </label>
                                    </div>
                                </div>
                                <div class="form-group deviceCmds">
                                    <label class="col-sm-3 control-label">{{Contrôle Pan & Tilt}}</label>
                                    <div class="col-sm-9">
					<label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="configuration" data-l2key="panTiltCmds" />{{Activer}}
					    <sup><i class="fas fa-question-circle tooltips"
						title="{{Création des commandes optionnelles Pan&Tilt. Uniquement pour les modèles supportés}}"></i></sup>
					</label>
                                    </div>
                                </div>
                            </fieldset>
                        </form>
                    </div>
                    <div class="col-sm-5">
			<form class="form-horizontal">
			    <fieldset>
				<div class="form-group">
				    <label class="col-sm-3 control-label">{{Numéro de série}}</label>
				    <div class="col-sm-6">
					<span type="text" class="eqLogicAttr label label-default" data-l1key="logicalId"></span>
				    </div>
				</div>
				<div class="form-group">
				    <label class="col-sm-3 control-label">{{Type}}</label>
				    <div class="col-sm-6">
					<span type="text" class="eqLogicAttr label label-default" data-l1key="configuration" data-l2key="eufyType"></span>
				    </div>
				</div>
				<div class="form-group">
				    <label class="col-sm-3 control-label">{{Modèle}}</label>
				    <div class="col-sm-6">
					<span type="text" class="eqLogicAttr label label-default" data-l1key="configuration" data-l2key="eufyModel"></span>
				    </div>
				</div>
				<div class="form-group">
				    <label class="col-sm-3 control-label">{{Version matérielle}}</label>
				    <div class="col-sm-6">
					<span type="text" class="eqLogicAttr label label-default" data-l1key="configuration" data-l2key="hardwareVersion"></span>
				    </div>
				</div>
				<div class="form-group">
				    <label class="col-sm-3 control-label">{{Version logicielle}}</label>
				    <div class="col-sm-6">
					<span type="text" class="eqLogicAttr label label-default" data-l1key="configuration" data-l2key="softwareVersion"></span>
				    </div>
				</div>
				<div class="form-group">
				    <label class="col-sm-3 control-label">{{Date de création}}</label>
				    <div class="col-sm-6">
					<span type="text" class="eqLogicAttr label label-default" data-l1key="configuration" data-l2key="createtime"></span>
				    </div>
				</div>
				<div class="form-group">
				    <div class="col-sm-3 control-label">
					<label>{{Image}}</label>
					<div style="margin-top: 20px">
					    <label class="btn btn-info" for="file-input" id="bt_upload_pic" title="{{Uploader une image}}">
						<i class="fas fa-upload" style="width:15px"></i>
						<input id="file-input" type="file" name="file" accept="image/png" style="display:none"/>
					    </label>
					    <a class="btn btn-info" id="bt_reset_pic" title="{{Réinitialiser}}">
						<i class="fas fa-redo" style="width:15px"></i>
					    </a>
					</div>
				    </div>
				    <div class="col-sm-6" style="text-align:left;">
					<img id="device_pic" style="height:100px; margin-top:10px"/>
				    </div>
				</div>
			    </fieldset>
			</form>
		    </div>
		</div> <!-- row -->
	    </div> <!-- tabpanel eqLogictab -->
            <?php
		displayTabPanel('station');
		displayTabPanel('device');
		displayTabPanel('other');
            ?>
        </div>
    </div>
</div>

<?php
function displayTabPanel ($id) {
        echo '<div role="tabpanel" class="tab-pane" id="' . $id . 'tab">';
        echo '<div class="table-responsive">';
        echo '<table id="' . $id . '_table" class="table table-bordered table-condensed tablesorter">';
        echo '<thead><tr>';
        echo '<th>{{Id}}</th>';
        echo '<th data-sortable="true" data-sorter="inputs">{{Nom}}</th>';
        echo '<th data-sorter="select-text">{{Type}}</th>';
	echo '<th data-sorter="false">{{Valeur}}</th>';
	echo '<th data-sorter="false" data-filter="false">{{Options}}</th>';
	echo '<th data-sorter="false" data-filter="false">{{Actions}}</th>';
        echo '</tr></thead><tbody></tbody></table>';
        echo '</div></div>';
}
?>


<?php include_file('desktop', 'eufy', 'js', 'eufy'); ?>
<?php include_file('core', 'plugin.template', 'js'); ?>
