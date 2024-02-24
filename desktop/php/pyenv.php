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
	$eqLogics = eqLogic::byType($plugin->getId());
	//log::add($pluginId, 'debug', __FILE__ . ' - $eqLogics = *' . var_export($eqLogics, true) . '*');

	pyenv::init();

	echo sprintf(__('<legend><i class="fas fa-cog"></i> {{Gestion de %s}}</legend>', __FILE__), $plugin->getName());
	$eqLogic = pyenv::byLogicalId($plugin->getId(), $plugin->getId());

	// TODO: ajouter ici la gstion des pyenv-virtualenv
	

	$ret = pyenv::runPyenv('pyenv install -l');
	log::add($pluginId, 'debug', __FILE__ . ' : $ret = ' . var_export($ret, true));

}

// Inclusion du fichier javascript du plugin (dossier, nom_du_fichier, extension_du_fichier, id_du_plugin) -->
include_file('desktop', 'pyenv', 'js', 'pyenv');
// Inclusion du fichier javascript du core - NE PAS MODIFIER NI SUPPRIMER -->
include_file('core', 'plugin.template', 'js');

?>