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

  // Les noms de fichier sont définis relativement au répertoire 'ressources'
  public static $_SHELL_INIT = '/shell_init';
  public static $_SCRIPT_TMP = '/script.tmp';
  public static $_PYTHON_BUILD = '/pyenv/plugins/python-build/bin/python-build';
  public static $_REQUIREMENTS = '/requirements.txt';

  public static $_SEPARATOR = '++';

  /*     * ***********************Methode static*************************** */

  /*
  * Fonction exécutée automatiquement toutes les minutes par Jeedom
  public static function cron() {}
  */

  /*
  * Fonction exécutée automatiquement toutes les 5 minutes par Jeedom
  public static function cron5() {}
  */

  /*
  * Fonction exécutée automatiquement toutes les 10 minutes par Jeedom
  public static function cron10() {}
  */

  /*
  * Fonction exécutée automatiquement toutes les 15 minutes par Jeedom
  public static function cron15() {}
  */

  /*
  * Fonction exécutée automatiquement toutes les 30 minutes par Jeedom
  public static function cron30() {}
  */

  /*
  * Fonction exécutée automatiquement toutes les heures par Jeedom
  public static function cronHourly() {}
  */

  /*
  * Fonction exécutée automatiquement tous les jours par Jeedom
  public static function cronDaily() {}
  */
  
  /*
  * Permet de déclencher une action avant modification d'une variable de configuration du plugin
  * Exemple avec la variable "param3"
  public static function preConfig_param3( $value ) {
    // do some checks or modify on $value
    return $value;
  }
  */

  /*
  * Permet de déclencher une action après modification d'une variable de configuration du plugin
  * Exemple avec la variable "param3"
  public static function postConfig_param3($value) {
    // no return value
  }
  */

  /*
   * Permet d'indiquer des éléments supplémentaires à remonter dans les informations de configuration
   * lors de la création semi-automatique d'un post sur le forum community
   public static function getConfigForCommunity() {
      return "les infos essentiel de mon plugin";
   }
   */

  /*
   * Initialise le plugin en créant un équipement fictif
   * TODO: Voir si cet équipement est utile
   */
  public static function init() {
    log::add(__CLASS__, 'debug', __CLASS__ . '::' . __FUNCTION__);
    $eqLogics = self::byType(__CLASS__);
    if (count($eqLogics) === 0) {
      $eqLogic = new pyenv();
      $eqLogic->setName(__CLASS__);
      $eqLogic->setLogicalId(__CLASS__);
      $eqLogic->setEqType_name(__CLASS__);
      $eqLogic->setIsEnable(1);
      $eqLogic->setIsVisible(0);
      $eqLogic->save();
    }
  }

  /*
   * Met pyenv à jour
   */
  static function updatePyenv() {
    log::add(__CLASS__, 'debug', __CLASS__ . '::' . __FUNCTION__);
    return self::runPyenv('pyenv update');
  }

  /*
   * Installe une version de python
   */
  static function installPython($_version) {
    log::add(__CLASS__, 'debug', __CLASS__ . '::' . __FUNCTION__ . ' * version = ' . $_version);
    if (self::pythonIsInstalled($_version))
      return;

    self::updatePyenv();
    $python_build = self::runPyenv(realpath(__DIR__ . '/../../ressources') . self::$_PYTHON_BUILD . ' --definitions');
    if (!in_array($_version, $python_build))
      throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '&nbsp;:<br>' . sprintf(__("La version python '%s' n'est pas disponible à l'installation", __FILE__), $_version));
    
    $command = sprintf('pyenv install %s', $_version);
    self::runPyenv($command);
  }

  /*
   * Désinstalle une version de python
   */
  static function uninstallPython($_version) {
    log::add(__CLASS__, 'debug', __CLASS__ . '::' . __FUNCTION__ . ' * version = ' . $_version);
    $command = sprintf('pyenv uninstall -f %s', $_version);
    if (self::pythonIsInstalled($_version))
      self::runPyenv($command);
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
    log::add(__CLASS__, 'debug', __CLASS__ . '::' . __FUNCTION__ . sprintf(" * pluginId = '%s', pythonVersion = '%s', requirements = '%s', suffix = '%s'", $_pluginId, $_pythonVersion, $_requirements, $_suffix));
    if (self::virtualenvIsInstalled($_pluginId . self::$_SEPARATOR . $_suffix)) {
      if ($_upgrade)
        self::deleteVirtualenv($_pluginId, $_suffix);
      else
        throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '&nbsp;:<br>' . sprintf(__("Le virtualenv '%s' existe déjà", __FILE__), $_pluginId . self::$_SEPARATOR . $_suffix));
    }
    if (!self::pluginExists($_pluginId))
      throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '&nbsp;:<br>' . sprintf(__("Le plugin '%s' n'existe pas", __FILE__), $_pluginId));
    if (strpos($_suffix, self::$_SEPARATOR))
      throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '&nbsp;:<br>' . sprintf(__("Le suffixe '%s' n'est pas valide", __FILE__), $_suffix));

    self::installPython($_pythonVersion);
    
    $requirements_content = '';
    if (is_file($_requirements)) {
      $requirements_content = file_get_content($_requirements);
    } elseif (is_string($_requirements) && $_requirements !== '') {
      $requirements_content = $_requirements;
    }
    $requirements_txt = realpath(__DIR__ . '/../../ressources') . self::$_REQUIREMENTS;
    if (file_put_contents($requirements_txt, $requirements_content) === false)
      throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '&nbsp;:<br>' . sprintf(__("Impossible de créer le fichier '%s'", __FILE__), $requirements_txt));

    $command = sprintf('pyenv virtualenv %s %s', $_pythonVersion, $_pluginId . self::$_SEPARATOR . $_suffix);
    self::runPyenv($command);
    
    $command = sprintf('pyenv exec pip install -r "%s"', $requirements_txt);
    self::runPyenv($command, '', $_pluginId . self::$_SEPARATOR . $_suffix);
    unlink($requirements_txt);
  }

  /*
   * Supprime un virtualenv
   */
  public static function deleteVirtualenv($_pluginId, $_suffix='none') {
    log::add(__CLASS__, 'debug', __CLASS__ . '::' . __FUNCTION__ . sprintf(" * pluginId = '%s', suffix = '%s'", $_pluginId, $_suffix));
    if (!self::pluginExists($_pluginId))
      throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '&nbsp;:<br>' . sprintf(__("Le plugin '%s' n'existe pas", __FILE__), $_pluginId));
    $command = sprintf('pyenv virtualenvs --skip-aliases --bare | grep %s', $_pluginId . self::$_SEPARATOR . $_suffix);
    $inst_virtualenvs = self::runPyenv($command);
    $pythonVersion = null;
    foreach ($inst_virtualenvs as $row) {
      $list = explode('/', $row);
      if ($list[2] === $_pluginId . self::$_SEPARATOR . $_suffix)
        $pythonVersion = $list[0];
    }
    $command = sprintf('pyenv virtualenvs --skip-aliases --bare | grep %1$s | grep -v %1$s++%2$s', $_pluginId, $_suffix);
    $virtualenvs = self::runPyenv($command);
    $command = sprintf('pyenv virtualenv-delete -f %s', $_pluginId . self::$_SEPARATOR . $_suffix);
    if (self::virtualenvIsInstalled($_pluginId . self::$_SEPARATOR . $_suffix))
      self::runPyenv($command);
    if (count($virtualenvs) === 0 && !is_null($pythonVersion))
      self::uninstallPython($pythonVersion);
  }

  /*
   * Exécute une commande pyenv
   */
  public static function runPyenv($_command, $_args='', $_virtualenv=null, $_daemon=false) {
    log::add(__CLASS__, 'debug', __CLASS__ . '::' . __FUNCTION__ . sprintf(" * command = '%s', args = '%s', virtualenv = '%s', daemon = '%s'", $_command, $_args, $_virtualenv, $_daemon));
    $script_file = '';
    if (!$_daemon) {
      $script_file = realpath(__DIR__ . '/../../ressources') . self::$_SCRIPT_TMP;
      if (is_file($script_file))
        throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '&nbsp;:<br>' . __("La commande ne peut être exécutée, une commande pyenv est en cours d'exécution.", __FILE__));
    } else {
      $script_file = tempnam(realpath(__DIR__ . '/../../ressources'), self::$_SCRIPT_TMP);
      if (!is_null($_virtualenv) && !self::virtualenvIsInstalled($_virtualenv))
          throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '&nbsp;:<br>' . sprintf(__("Le virtualenv '%s' n'est pas installé", __FILE__), $_virtualenv));
    }
    
    $script_content = self::sourceScript($_command, $_args, $_virtualenv);
    
    if (file_put_contents($script_file, $script_content) === false)
      throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '&nbsp;:<br>' . __("Impossible de créer le script pour la commande pyenv.", __FILE__));
    chmod($script_file, 0755);
    $output = array();
    $retval = null;
    $ret = exec($script_file, $output, $retval);
    unlink($script_file);
    if ($ret === false)
      throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '&nbsp;:<br>' . sprintf(__("Erreur lors de l'exécution de la commande '%s'", __FILE__), $_command));
    foreach ($output as $row)
      log::add(__CLASS__, 'debug', __CLASS__ . '::' . __FUNCTION__ . sprintf(" * %s", $row));
    return $output;
  }

  /*
   * Retourne le contenu du script à exécuter pour être dans un environnement pyenv
   */
  public static function sourceScript($_command, $_args='', $_virtualenv=null, $_daemon=false) {
    log::add(__CLASS__, 'debug', __CLASS__ . '::' . __FUNCTION__ . sprintf(" * command = '%s', args = '%s', virtualenv = '%s', daemon = '%s'", $_command, $_args, $_virtualenv, $_daemon));
    $ret = file(realpath(__DIR__ . '/../../ressources') . self::$_SHELL_INIT);
    if (is_file($_command)) {
      $dirname = dirname($_command);
      $ret[] = sprintf('cd "%s"', $dirname);
    }
    if (!is_null($_virtualenv)) {
      if (!self::virtualenvIsInstalled($_virtualenv))
        throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '&nbsp;:<br>' . sprintf(__("Le virtualenv '%s' n'est pas installé", __FILE__), $_virtualenv));
      if (!$_daemon) {
        [$pluginId, $suffix] = explode(self::$_SEPARATOR, $_virtualenv);
        $_args .= ' >> ' . log::getPathToLog($pluginId) . ' 2>&1 &';
      }
      $ret[] = sprintf('pyenv activate %s', $_virtualenv);
      $ret[] = sprintf('pyenv exec %s %s', $_command, $_args);
    } else {
      if ($_daemon)
        $_args .= ' 2>&1 &';
      $ret[] = sprintf('%s %s', $_command, $_args);
    }
    foreach ($ret as &$row)
      $row = trim($row);
    return implode("\n", $ret);
  }

  /*
   * Vérifie si une version de python (ou un virtualenv) est installé
   */
  static function pythonIsInstalled($_version) {
    log::add(__CLASS__, 'debug', __CLASS__ . '::' . __FUNCTION__ . ' * version = ' . $_version);
    $installed_pythons = self::runPyenv('pyenv versions --bare');
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
    $virtualenvs = self::runPyenv('pyenv virtualenvs --skip-aliases --bare');
    foreach ($virtualenvs as $virtualenv) {
      [$version, , $virtualenvName] = explode('/', $virtualenv);
      [$virtualenvPlugin, $virtualenvSuffix] = explode(self::$_SEPARATOR, $virtualenvName);
      if ((!$_pluginId || $_pluginId === $virtualenvPlugin) &&
          (!$_pythonVersion || $version === $_pythonVersion) &&
          (!$_suffix || $_suffix === $virtualenvSuffix))
        $ret[] = array(
          'fullname'  => $virtualenvName,
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
    $installed_virtualenvs = self::runPyenv('pyenv virtualenvs --bare');
    return in_array($_virtualenv, $installed_virtualenvs);
  }

  /*
   * Permet d'inclure le répertoire ressources/pyenv au backup
   */
  public static function backupExclude() {
    if (config::byKey('includeInBackup', __CLASS__, '0', true) === '1')
      return;
    return [
      'ressources/pyenv'
    ];
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
  * Permet de crypter/décrypter automatiquement des champs de configuration des équipements
  * Exemple avec le champ "Mot de passe" (password)
  public function decrypt() {
    $this->setConfiguration('password', utils::decrypt($this->getConfiguration('password')));
  }
  public function encrypt() {
    $this->setConfiguration('password', utils::encrypt($this->getConfiguration('password')));
  }
  */

  /*
  * Permet de modifier l'affichage du widget (également utilisable par les commandes)
  public function toHtml($_version = 'dashboard') {}
  */

  /*     * **********************Getteur Setteur*************************** */
}

class pyenvCmd extends cmd {
  /*     * *************************Attributs****************************** */

  /*
  public static $_widgetPossibility = array();
  */

  /*     * ***********************Methode static*************************** */


  /*     * *********************Methode d'instance************************* */

  /*
  * Permet d'empêcher la suppression des commandes même si elles ne sont pas dans la nouvelle configuration de l'équipement envoyé en JS
  public function dontRemoveCmd() {
    return true;
  }
  */

  // Exécution d'une commande
  public function execute($_options = array()) {
  }

  /*     * **********************Getteur Setteur*************************** */
}

?>