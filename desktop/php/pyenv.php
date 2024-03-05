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

	$eqLogic = pyenv::byLogicalId($pluginId, $pluginId);
	$lock = $eqLogic->getConfiguration(pyenv::LOCK);
	log::add($pluginId, 'debug', __FILE__ . ' : $lock = ' . var_export($lock, true));

	$virtualenvNames = pyenv::getVirtualenvNames();
	
	if (count($virtualenvNames) === 0) {
		echo '<p>' . __("Aucun virtualenv pyenv à afficher.", __FILE__) . '</p>';
	} else {

		log::add($pluginId, 'debug', __FILE__ . ' : $virtualenvNames = ' . var_export($virtualenvNames, true));
		//echo '<pre>';
		//var_export($virtualenvNames);
		//echo '</pre>';
		
?>

<table id="table_virtualenv">
	<thead>
		<tr>
			<th style="min-width:200px;width:300px;">{{PluginId}}</th>
			<th style="min-width:200px;width:300px;">{{Version python}}</th>
			<th style="min-width:200px;width:300px;">{{Suffixe}}</th>
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
			echo '</tr>';
		}

?>

	</tbody>
</table>

<?php

	}	
}

// Inclusion du fichier javascript du plugin (dossier, nom_du_fichier, extension_du_fichier, id_du_plugin) -->
include_file('desktop', 'pyenv', 'js', 'pyenv');
// Inclusion du fichier javascript du core - NE PAS MODIFIER NI SUPPRIMER -->
include_file('core', 'plugin.template', 'js');

?>