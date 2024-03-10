<?php
if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
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
	
	$pyenv_version = pyenv::runPyenv('pyenv', '--version');
	echo '<p>' . sprintf(__("Version de pyenv : %s", __FILE__), $pyenv_version[0]) . '</p>';


	$eqLogic = pyenv::byLogicalId($pluginId, $pluginId);
	if ($eqLogic->getConfiguration(pyenv::LOCK, 'false') !== 'false')
		echo '<p>' . sprintf(__("Une commande bloquante est en cours d'exécution : '%s'", __FILE__), $eqLogic->getConfiguration(pyenv::LOCKING_CMD, '')) . '</p>';
	
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
			[$pluginId, $suffix] = explode(pyenv::SEPARATOR, $virtualenv['fullname']);
			echo '<tr>';
			echo '  <td>';
			echo $pluginId;
			echo '  </td>';
			echo '  <td>';
			echo $virtualenv['python'];
			echo '  </td>';
			echo '  <td>';
			echo $suffix;
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
						console.log(checkbox.id);
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
							}
						});
					}
				});
				window.location.reload();
			});
		};
	});

</script>

<?php

	}	
}

// Inclusion du fichier javascript du plugin (dossier, nom_du_fichier, extension_du_fichier, id_du_plugin) -->
include_file('desktop', 'pyenv', 'js', 'pyenv');
// Inclusion du fichier javascript du core - NE PAS MODIFIER NI SUPPRIMER -->
include_file('core', 'plugin.template', 'js');

?>