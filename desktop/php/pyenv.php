<?php
if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}

function format_intervalle(DateInterval $intervalle) {
	$mois = $intervalle->m;
	$jours = $intervalle->days;
	$heures = $intervalle->h;
	$minutes = $intervalle->i;
	$secondes = $intervalle->s;

	$texte = "";

	if ($mois > 0) {
		$texte .= $mois . " mois";
	}

	if ($jours > 0) {
		$texte .= (strlen($texte) > 0 ? ", " : "") . $jours . " jour" . ($jours > 1 ? "s" : "");
	}

	if ($heures > 0) {
		$texte .= (strlen($texte) > 0 ? ", " : "") . $heures . " heure" . ($heures > 1 ? "s" : "");
	}

	if ($minutes > 0) {
		$texte .= (strlen($texte) > 0 ? ", " : "") . $minutes . " minute" . ($minutes > 1 ? "s" : "");
	}

	if ($secondes > 0) {
		$texte .= (strlen($texte) > 0 ? ", " : "") . $secondes . " seconde" . ($secondes > 1 ? "s" : "");
	}

	return $texte;
}

// Déclaration des variables obligatoires
$pluginId = 'pyenv';
$plugin = plugin::byId($pluginId);

if (!$plugin->isActive()) {
?>
	<div class="alert alert-danger div_alert">
		<span id="span_errorMessage">{{Gestion du plugin impossible tant qu'il est désactivé}}</span>
	</div>
<?php

} else {

	sendVarToJS('eqType', $plugin->getId());

	echo sprintf(__('<legend><i class="fas fa-cog"></i> {{Gestion de %s}}</legend>', __FILE__), $plugin->getName());
	
	pyenv::init();
	
	$pyenv_version = pyenv::runPyenv('pyenv', "--version | awk '{ print $2 }'");
	echo '<p><b>' . sprintf(__("Version de pyenv : %s", __FILE__), $pyenv_version[0]) . '</b></p>';

	$eqLogic = pyenv::byLogicalId($pluginId, $pluginId);
	if ($eqLogic->getConfiguration(pyenv::LOCK, 'false') !== 'false') {
		echo '<p>' . sprintf(__("Une commande bloquante est en cours d'exécution : '%s'", __FILE__), $eqLogic->getConfiguration(pyenv::LOCKING_CMD, '')) . '<br>';
		$timestamp = intval($eqLogic->getConfiguration(pyenv::TIMESTAMP, time()));
		$dt = new DateTime();
		$dt->setTimestamp($timestamp);
		$now = new DateTime();
		$dd = date_diff($now, $dt);
		echo sprintf(__("Cette commande est lancée depuis %s", __FILE__), format_intervalle($dd)) . '<br>';

		$total_minutes = $dd->m * 30 * 24 * 60 + $dd->days * 24 * 60 + $dd->h * 60 + $dd->i;
		$output = array();
		$retval = null;
		$ret_exec = exec(sprintf("ps ax | grep '%s' | grep -v grep", $eqLogic->getConfiguration(pyenv::LOCKING_CMD, '')), $output, $retval);
		if ($total_minutes >= 5 && count($output) == 0) {
			echo "<br>{{Il semble que cette commande n'est plus en cours d'exécution, il est possible de réinitialiser le verrou pour les commandes bloquante.}}&nbsp;";
			echo '<a class="btn btn-danger" id="bt_ReinitPyenv"><i class="fas fa-trash"></i> {{Réinitialiser pyenv4Jeedom}}</a><br>';
		}
		echo '</p>';
	}
	
	$virtualenvNames = pyenv::getVirtualenvNames();
	if (count($virtualenvNames) === 0) {
		echo '<p>' . __("Aucun virtualenv pyenv à afficher.", __FILE__) . '</p>';
	} else {

		log::add($pluginId, 'debug', __FILE__ . ' : $virtualenvNames = ' . var_export($virtualenvNames, true));
		//echo '<pre>';
		//var_export($virtualenvNames);
		//echo '</pre>';
		
?>

<form>
	<table id="table_virtualenv">
		<thead>
			<tr>
				<th style="min-width:200px;width:300px;">{{PluginId}}</th>
				<th style="min-width:200px;width:300px;">{{Version python}}</th>
				<th style="min-width:200px;width:300px;">{{Suffixe}}</th>
				<th style="min-width:200px;width:200px;">{{Sélection}}</th>
			</tr>
		</thead>
		<tbody>

<?php

		foreach ($virtualenvNames as $virtualenv) {
			echo '<tr>';
			echo '  <td>';
			echo $virtualenv['pluginId'];
			echo '  </td>';
			echo '  <td>';
			echo $virtualenv['python'];
			echo '  </td>';
			echo '  <td>';
			echo $virtualenv['suffix'];
			echo '  </td>';
			echo '  <td>';
			echo '		<input type="checkbox" id="' . $virtualenv['fullname'] . '" name="virtualenv">';
			echo '  </td>';
			echo '</tr>';
		}

?>
			<tr>
				<td></td>
				<td></td>
				<td></td>
				<td>
					<p>&nbsp;</p>
					<a class="btn btn-danger" id="bt_RemoveVirtualenv"><i class="fas fa-trash"></i> {{Supprimer la sélection}}</a>
				</td>
		</tbody>
	</table>
</form>

<script>
	const checkboxes = document.querySelectorAll('input[type="checkbox"][name="virtualenv"]');
	const bt_RemoveVirtualenv = document.getElementById('bt_RemoveVirtualenv');

	bt_RemoveVirtualenv.addEventListener('click', (event) => {
		if (!bt_RemoveVirtualenv.disabled) {
			event.preventDefault();
			bootbox.confirm('{{Êtes-vous sûr de vouloir supprimer la sélection ?}}', function(result) {
				checkboxes.forEach(checkbox => {
					if (!result)
						return;
					if (checkbox.checked) {
						//console.log(checkbox.id);
						$.ajax({
							type: "POST",
							url: "plugins/pyenv/core/ajax/pyenv.ajax.php",
							data: {
								action: "deleteVirtualenv",
								virtualenv: checkbox.id
							},
							dataType: 'json',
							error: function (request, status, error) {
								handleAjaxError(request, status, error);
							},
    					success: function (data) {
								window.location.reload();
							}
						});
					}
				});
			});
		};
	});

</script>

<?php

	}

?>

<script>
	const bt_ReinitPyenv = document.getElementById('bt_ReinitPyenv');

	if (!is_null(bt_ReinitPyenv)) {
		bt_ReinitPyenv.addEventListener('click', (event) => {
			if (!bt_ReinitPyenv.disabled) {
				event.preventDefault();
				bootbox.confirm('{{Êtes-vous sûr de vouloir réinitialiser le verrou ?}}', function(result) {
					if (!result)
						return;

					$.ajax({
						type: "POST",
						url: "plugins/pyenv/core/ajax/pyenv.ajax.php",
						data: {
							action: "ReinitPyenv"
						},
						dataType: 'json',
						error: function (request, status, error) {
							handleAjaxError(request, status, error);
						},
						success: function (data) {
							window.location.reload();
						}
					});
				});
			};
		});
	}

</script>

<?php

}

// Inclusion du fichier javascript du plugin (dossier, nom_du_fichier, extension_du_fichier, id_du_plugin) -->
include_file('desktop', 'pyenv', 'js', 'pyenv');
// Inclusion du fichier javascript du core - NE PAS MODIFIER NI SUPPRIMER -->
include_file('core', 'plugin.template', 'js');

?>