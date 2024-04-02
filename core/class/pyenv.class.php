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

/* * ***************************Includes********************************* */
require_once __DIR__  . '/../../../../core/php/core.inc.php';

class pyenv extends eqLogic {
  /*     * *************************Attributs****************************** */

  /*
  * Permet de définir les possibilités de personnalisation du widget (en cas d'utilisation de la fonction 'toHtml' par exemple)
  * Tableau multidimensionnel - exemple: array('custom' => true, 'custom::layout' => false)
  public static $_widgetPossibility = array();
  */

  /*
  * Permet de crypter/décrypter automatiquement des champs de configuration du plugin
  * Exemple : "param1" & "param2" seront cryptés mais pas "param3"
  public static $_encryptConfigKey = array('param1', 'param2');
  */

  // Les noms de fichier sont définis relativement au répertoire 'resources'
  const SHELL_INIT = '/shell_init';
  const SCRIPT_TMP = '/script.tmp';
  const PYTHON_BUILD = '/pyenv/plugins/python-build/bin/python-build';
  const REQUIREMENTS = '/requirements.txt';

  const SEPARATOR = '++';

  const LOCK = 'lock';
  const LOCKING_CMD = 'locking_cmd';
  const TIMESTAMP = 'timestamp';

  /*     * ***********************Methode static*************************** */

  /*
   * Permet d'indiquer des éléments supplémentaires à remonter dans les informations de configuration
   * lors de la création semi-automatique d'un post sur le forum community
   public static function getConfigForCommunity() {
      return "les infos essentiel de mon plugin";
   }
   */

  /*
   * Initialise le plugin en créant un équipement fictif
   */
  public static function init() {
    log::add(__CLASS__, 'debug', __CLASS__ . '::' . __FUNCTION__);
    $eqLogics = self::byType(__CLASS__);
    if (count($eqLogics) === 0) {
      $eqLogic = new pyenv();
      $eqLogic->setName(__CLASS__);
      $eqLogic->setLogicalId(__CLASS__);
      $eqLogic->setEqType_name(__CLASS__);
      $eqLogic->setConfiguration(self::LOCK, 'false');
      $eqLogic->setConfiguration(self::LOCKING_CMD, '');
      $eqLogic->setConfiguration(self::TIMESTAMP, time());
      $eqLogic->setIsEnable(0);
      $eqLogic->setIsVisible(0);
      $eqLogic->save();
    }
  }

  /*
   * Met pyenv à jour
   */
  static function updatePyenv() {
    log::add(__CLASS__, 'debug', __CLASS__ . '::' . __FUNCTION__);
    return self::runPyenv('pyenv', 'update', null, false, true);
  }

  /*
   * Installe une version de python
   */
  static function installPython($_version) {
    log::add(__CLASS__, 'debug', __CLASS__ . '::' . __FUNCTION__ . ' * version = ' . $_version);
    if (self::pythonIsInstalled($_version))
      return;
    
    self::updatePyenv();
    $python_build = self::runPyenv(realpath(__DIR__ . '/../../resources') . self::PYTHON_BUILD, '--definitions');
    if (!in_array($_version, $python_build))
      throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '&nbsp;:<br>' . sprintf(__("La version python '%s' n'est pas disponible à l'installation", __FILE__), $_version));

    $arg = sprintf('install -s %s', $_version);
    self::runPyenv('pyenv', $arg, null, false, true);
    self::runPyenv('pyenv', 'rehash', null, false, true);
    log::add(__CLASS__, 'info', __CLASS__ . '::' . __FUNCTION__ . ': ' . sprintf(__("Python version '%s' installée", __FILE__), $_version));
  }

  /*
  * Désinstalle une version de python
  */
  static function uninstallPython($_version) {
    log::add(__CLASS__, 'debug', __CLASS__ . '::' . __FUNCTION__ . ' * version = ' . $_version);
    $arg = sprintf('uninstall -f %s', $_version);
    if (self::pythonIsInstalled($_version))
      self::runPyenv('pyenv', $arg, null, false, true);
    log::add(__CLASS__, 'info', __CLASS__ . '::' . __FUNCTION__ . ': ' . sprintf(__("Python version '%s' désinstallée", __FILE__), $_version));
  }
  
  /*
   * Vérifie si un plugin exste
   */
  static function pluginExists($_pluginId) {
    log::add(__CLASS__, 'debug', __CLASS__ . '::' . __FUNCTION__ . ' * pluginId = ' . $_pluginId);
    $list_plugins = plugin::listPlugin(false, false, false, true); // Liste des id des plugins installés
    return in_array($_pluginId, $list_plugins);
  }
  
  /*
  * Crée un virtualenv pour un plugin et installe les modules
  */
  public static function createVirtualenv($_pluginId, $_pythonVersion, $_requirements, $_suffix='none', $_upgrade=false) {
    log::add(__CLASS__, 'debug', __CLASS__ . '::' . __FUNCTION__ . sprintf(" * pluginId = '%s', pythonVersion = '%s', requirements = '%s', suffix = '%s', upgrade = '%s'", $_pluginId, $_pythonVersion, $_requirements, $_suffix, var_export($_upgrade, true)));
    if (self::virtualenvIsInstalled($_pluginId . self::SEPARATOR . $_suffix)) {
      if ($_upgrade)
      self::deleteVirtualenv($_pluginId, $_suffix);
      else
        throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '&nbsp;:<br>' . sprintf(__("Le virtualenv '%s' existe déjà", __FILE__), $_pluginId . self::SEPARATOR . $_suffix));
    }
    if (!self::pluginExists($_pluginId))
      throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '&nbsp;:<br>' . sprintf(__("Le plugin '%s' n'existe pas", __FILE__), $_pluginId));
    if (strpos($_suffix, self::SEPARATOR))
      throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '&nbsp;:<br>' . sprintf(__("Le suffixe '%s' n'est pas valide", __FILE__), $_suffix));
    
    self::installPython($_pythonVersion);
    
    $requirements_content = '';
    if (is_file($_requirements)) {
      $requirements_content = file_get_content($_requirements);
    } elseif (is_string($_requirements) && $_requirements !== '') {
      $requirements_content = $_requirements;
    }
    $requirements_txt = realpath(__DIR__ . '/../../resources') . self::REQUIREMENTS;
    if (file_put_contents($requirements_txt, $requirements_content) === false)
      throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '&nbsp;:<br>' . sprintf(__("Impossible de créer le fichier '%s'", __FILE__), $requirements_txt));
    
    $arg = sprintf('virtualenv %s %s', $_pythonVersion, $_pluginId . self::SEPARATOR . $_suffix);
    self::runPyenv('pyenv', $arg, null, false, true);
    log::add(__CLASS__, 'info', __CLASS__ . '::' . __FUNCTION__ . ': ' . sprintf(__("virtualenv '%s' installé", __FILE__), $_pluginId . self::SEPARATOR . $_suffix));
    
    $arg = sprintf('exec pip install -r "%s"', $requirements_txt);
    self::runPyenv('pyenv', $arg, $_pluginId . self::SEPARATOR . $_suffix, false, true);
    log::add(__CLASS__, 'info', __CLASS__ . '::' . __FUNCTION__ . ': ' . sprintf(__("Dépendances dans le virtualenv '%s' installées", __FILE__), $_pluginId . self::SEPARATOR . $_suffix));
    unlink($requirements_txt);
  }

  /*
   * Supprime un virtualenv
   */
  public static function deleteVirtualenv($_pluginId, $_suffix='none') {
    log::add(__CLASS__, 'debug', __CLASS__ . '::' . __FUNCTION__ . sprintf(" * pluginId = '%s', suffix = '%s'", $_pluginId, $_suffix));
    if (!self::pluginExists($_pluginId))
      throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '&nbsp;:<br>' . sprintf(__("Le plugin '%s' n'existe pas", __FILE__), $_pluginId));
    $arg = sprintf('virtualenvs --skip-aliases --bare | grep %s', $_pluginId . self::SEPARATOR . $_suffix);
    $inst_virtualenvs = self::runPyenv('pyenv', $arg);
    $pythonVersion = null;
    foreach ($inst_virtualenvs as $row) {
      $list = explode('/', $row);
      if ($list[2] === $_pluginId . self::SEPARATOR . $_suffix)
        $pythonVersion = $list[0];
    }
    $arg = sprintf('virtualenvs --skip-aliases --bare | grep %1$s | grep -v %1$s++%2$s', $_pluginId, $_suffix);
    $virtualenvs = self::runPyenv('pyenv', $arg);
    if (self::virtualenvIsInstalled($_pluginId . self::SEPARATOR . $_suffix)) {
      $arg = sprintf('virtualenv-delete -f %s', $_pluginId . self::SEPARATOR . $_suffix);
      self::runPyenv('pyenv', $arg, null, false, true);
      log::add(__CLASS__, 'info', __CLASS__ . '::' . __FUNCTION__ . ': ' . sprintf(__("virtualenv '%s' supprimé", __FILE__), $_pluginId . self::SEPARATOR . $_suffix));
    }
    if (count($virtualenvs) === 0 && !is_null($pythonVersion))
      self::uninstallPython($pythonVersion);
  }

  /*
   * Réinitialise le verrou d'exécution de commande bloquante
   */
  public static function reinit() {
    log::add(__CLASS__, 'debug', __CLASS__ . '::' . __FUNCTION__);
    $eqLogic = pyenv::byLogicalId(__CLASS__, __CLASS__);
    if (is_object($eqLogic)) {
      $eqLogic->setConfiguration(self::LOCK, 'false');
      $eqLogic->setConfiguration(self::LOCKING_CMD, '');
      $eqLogic->setConfiguration(self::TIMESTAMP, time());
      $eqLogic->save();
    }
  }

  /*
   * Exécute une commande pyenv
   */
  public static function runPyenv($_command, $_args='', $_virtualenv=null, $_daemon=false, $_lock=false) {
    log::add(__CLASS__, 'debug', __CLASS__ . '::' . __FUNCTION__ . sprintf(" * command = '%s', args = '%s', virtualenv = '%s', daemon = '%s', lock = '%s'", $_command, $_args, var_export($_virtualenv, true), var_export($_daemon, true), var_export($_lock, true)));
    $eqLogic = self::byLogicalId(__CLASS__, __CLASS__);
    if ($_lock !== false && $eqLogic->getConfiguration(self::LOCK, 'false') !== 'false')
      throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '&nbsp;:<br>' . __("La commande ne peut pas être exécutée, une commande pyenv bloquante est en cours d'exécution.", __FILE__));
    
    if (!is_null($_virtualenv) && !self::virtualenvIsInstalled($_virtualenv))
      throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '&nbsp;:<br>' . sprintf(__("Le virtualenv '%s' n'est pas installé", __FILE__), $_virtualenv));
    
    $script_content = self::sourceScript($_command, $_args, $_virtualenv, $_daemon);
    
    if ($_lock !== false) {
      $eqLogic->setConfiguration(self::LOCK, 'true');
      $eqLogic->setConfiguration(self::LOCKING_CMD, sprintf('%s %s', $_command, $_args));
      $eqLogic->setConfiguration(self::TIMESTAMP, time());
      $eqLogic->save();
    }

    $output = array();
    $retval = null;
    $ret = exec($script_content, $output, $retval);
    
    if ($_lock !== false && $eqLogic->getConfiguration(self::LOCK, 'true') !== 'false') {
      $eqLogic->setConfiguration(self::LOCK, 'false');
      $eqLogic->setConfiguration(self::LOCKING_CMD, '');
      // Le timestamp n'est volontairement pas mis à jour pour garder le timestamp de la dernière commande bloquante
      $eqLogic->save();
    }

    if ($ret === false)
      throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '&nbsp;:<br>' . sprintf(__("Erreur lors de l'exécution de la commande '%s %s'", __FILE__), $_command, $_args));
    foreach ($output as $row)
      log::add(__CLASS__, 'debug', __CLASS__ . '::' . __FUNCTION__ . sprintf(" -> %s", $row));
    return $output;
  }

  /*
   * Retourne le contenu du script à exécuter pour être dans un environnement pyenv
   */
  public static function sourceScript($_command, $_args='', $_virtualenv=null, $_daemon=false) {
    log::add(__CLASS__, 'debug', __CLASS__ . '::' . __FUNCTION__ . sprintf(" * command = '%s', args = '%s', virtualenv = '%s', daemon = '%s'", $_command, $_args, var_export($_virtualenv, true), var_export($_daemon, true)));
    $ret = file(realpath(__DIR__ . '/../../resources') . self::SHELL_INIT);
    if (is_file($_command)) {
      $dirname = dirname($_command);
      $ret[] = sprintf('cd "%s"', $dirname);
    }
    if (!is_null($_virtualenv)) {
      if (!self::virtualenvIsInstalled($_virtualenv))
        throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '&nbsp;:<br>' . sprintf(__("Le virtualenv '%s' n'est pas installé", __FILE__), $_virtualenv));
      if ($_daemon) {
        [$pluginId, $suffix] = explode(self::SEPARATOR, $_virtualenv);
        $_args .= ' >> ' . realpath(log::getPathToLog($pluginId)) . ' 2>&1 &';
      }
      $ret[] = sprintf('pyenv activate %s', $_virtualenv);
    } else {
      if ($_daemon)
        $_args .= ' 2>&1 &';
    }
    $ret[] = sprintf('%s %s', $_command, $_args);
    foreach ($ret as &$row)
      $row = trim($row);
    return implode("\n", $ret);
  }

  /*
   * Vérifie si une version de python (ou un virtualenv) est installé
   */
  static function pythonIsInstalled($_version) {
    log::add(__CLASS__, 'debug', __CLASS__ . '::' . __FUNCTION__ . ' * version = ' . $_version);
    $installed_pythons = self::runPyenv('pyenv', 'versions --bare');
    return in_array($_version, $installed_pythons);
  }

  /*
   * Retourne le nom du ou des virtualenv correspondants
   */
  public static function getVirtualenvNames($_pluginId='', $_pythonVersion='', $_suffix='') {
    log::add(__CLASS__, 'debug', __CLASS__ . '::' . __FUNCTION__ . sprintf(" * pluginId = '%s', pythonVersion = '%s', suffix = '%s'", $_pluginId, $_pythonVersion, $_suffix));
    if ($_pluginId && !self::pluginExists($_pluginId))
      throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '&nbsp;:<br>' . sprintf(__("Le plugin '%s' n'existe pas", __FILE__), $_pluginId));
    $ret = array();
    $virtualenvs = self::runPyenv('pyenv', 'virtualenvs --skip-aliases --bare');
    foreach ($virtualenvs as $virtualenv) {
      [$version, , $virtualenvName] = explode('/', $virtualenv);
      [$virtualenvPlugin, $virtualenvSuffix] = explode(self::SEPARATOR, $virtualenvName);
      if ((!$_pluginId || $_pluginId === $virtualenvPlugin) &&
          (!$_pythonVersion || $version === $_pythonVersion) &&
          (!$_suffix || $_suffix === $virtualenvSuffix))
        $ret[] = array(
          'fullname'  => $virtualenvName,
          'pluginId'  => $virtualenvPlugin,
          'suffix'    => $virtualenvSuffix,
          'python'    => $version
        );
    }
    return $ret;
  }

  /*
   * Vérifie si un virtualenv est installé
   */
  static function virtualenvIsInstalled($_virtualenv) {
    log::add(__CLASS__, 'debug', __CLASS__ . '::' . __FUNCTION__ . ' * virtualenv = ' . $_virtualenv);
    $installed_virtualenvs = self::runPyenv('pyenv', 'virtualenvs --bare');
    return in_array($_virtualenv, $installed_virtualenvs);
  }

  /*
   * Permet d'inclure le répertoire resources/pyenv au backup
   */
  public static function backupExclude() {
    if (config::byKey('includeInBackup', __CLASS__, '0', true) === '1')
      return;
    return [
      'resources/pyenv'
    ];
  }

  /*
   * Informations pour la page santé
   */
  public static function health() {
    log::add(__CLASS__, 'debug', __CLASS__ . '::' . __FUNCTION__);
    self::init();
    $ret = array();
    $lock = array();
    $eqLogic = self::byLogicalId(__CLASS__, __CLASS__);
    $lock['test'] = __("Commande bloquante en cours", __FILE__);
    $lock['result'] = $eqLogic->getConfiguration(self::LOCKING_CMD, __("Aucune", __FILE__));
    $lock['state'] = ($eqLogic->getConfiguration(self::LOCK, 'false') !== 'false') ? 'nok' : 'ok';
    $ret[] = $lock;

    $virtualenvNames = self::getVirtualenvNames();
    foreach ($virtualenvNames as $virtualenv) {
      $health = array();
      $result = self::runPyenv('python', '--version', $virtualenv['fullname']);
      $output = array();
      $retval = null;
      $ret_exec = exec(sprintf('ps ax | grep %s | grep -v grep', $virtualenv['fullname']), $output, $retval);
      $info = '';
      if (count($output) > 0) {
        $info = ' - ' . __("En cours d'utilisation", __FILE__);
      }
      $health['test'] = sprintf(__("Plugin '%s', virtualenv '%s'", __FILE__), $virtualenv['pluginId'], $virtualenv['suffix']);
      $health['result'] = $result[0] . $info;
      $health['advice'] = $virtualenv['fullname'];
      $health['state'] = ($result[0] === sprintf('Python %s', $virtualenv['python'])) ? 'ok' : 'nok';
      $ret[] = $health;
    }
    return $ret;
  }

  /*     * *********************Méthodes d'instance************************* */

  // Fonction exécutée automatiquement avant la création de l'équipement
  public function preInsert() {
    log::add(__CLASS__, 'debug', __CLASS__ . '::' . __FUNCTION__);
  }

  // Fonction exécutée automatiquement après la création de l'équipement
  public function postInsert() {
    log::add(__CLASS__, 'debug', __CLASS__ . '::' . __FUNCTION__);
  }

  // Fonction exécutée automatiquement avant la mise à jour de l'équipement
  public function preUpdate() {
    log::add(__CLASS__, 'debug', __CLASS__ . '::' . __FUNCTION__);
  }

  // Fonction exécutée automatiquement après la mise à jour de l'équipement
  public function postUpdate() {
    log::add(__CLASS__, 'debug', __CLASS__ . '::' . __FUNCTION__);
  }

  // Fonction exécutée automatiquement avant la sauvegarde (création ou mise à jour) de l'équipement
  public function preSave() {
    log::add(__CLASS__, 'debug', __CLASS__ . '::' . __FUNCTION__);
  }

  // Fonction exécutée automatiquement après la sauvegarde (création ou mise à jour) de l'équipement
  public function postSave() {
    log::add(__CLASS__, 'debug', __CLASS__ . '::' . __FUNCTION__);
  }

  // Fonction exécutée automatiquement avant la suppression de l'équipement
  public function preRemove() {
    log::add(__CLASS__, 'debug', __CLASS__ . '::' . __FUNCTION__);
  }

  // Fonction exécutée automatiquement après la suppression de l'équipement
  public function postRemove() {
    log::add(__CLASS__, 'debug', __CLASS__ . '::' . __FUNCTION__);
  }

  /*
  * Permet de modifier l'affichage du widget (également utilisable par les commandes)
  public function toHtml($_version = 'dashboard') {}
  */

  /*     * **********************Getteur Setteur*************************** */
}

class pyenvCmd extends cmd {
  /*     * *************************Attributs****************************** */

  /*     * ***********************Methode static*************************** */

  /*     * *********************Methode d'instance************************* */

  // Exécution d'une commande
  public function execute($_options = array()) {
  }

  /*     * **********************Getteur Setteur*************************** */
}

?>