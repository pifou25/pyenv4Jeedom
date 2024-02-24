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

  public static $_SHELL_INIT = 'shell_init';
  public static $_SCRIPT_TMP = 'script.tmp';

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
    $eqLogics = self::byType(__CLASS__);
    if (count($eqLogics) === 0) {
      log::add(__CLASS__, 'debug', __CLASS__ . '::' . __FUNCTION__ . ' : ' . __("Création de l'équipement pyenv", __FILE__));
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
   * Permet d'exécuter une commande pyenv
   */
  public static function runPyenv($_command) {
    if (strpos($_command, 'pyenv') === false)
      throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '&nbsp;:<br>' . __("La commande à exécuter n'est pas une commande pyenv.", __FILE__));
    
    $script_content = file_get_contents(__DIR__ . '/../../ressources/' . self::$_SHELL_INIT) . "\n";
    $script_content .= $_command;
    $script_file = realpath(__DIR__ . '/../../ressources') . '/' . self::$_SCRIPT_TMP;
    if (!file_put_contents($script_file, $script_content))
      throw new Exception(__CLASS__ . '::' . __FUNCTION__ . '&nbsp;:<br>' . __("Impossible de créer le script pour la commande pyenv.", __FILE__));
    chmod($script_file, 0755);
    $ret = shell_exec($script_file);
    unlink($script_file);
    return $ret;
  }

  /*
   * Permet de mettre à jour pyenv
   */
  public static function updatePyenv() {

  }

  /*
   * Permet de créer un virtualenv
   */
  public static function createVirtualenv($_pluginId, $_pythonVersion, $_requirements) {

  }

  /*
   * Permet d'inclure le répertoire ressources/pyenv au backup
   */
  public static function backupExclude() {
    if (config::byKey('includeInBackup', __CLASS__, '0', true) === '1')
      return [
        'resources/pyenv'
      ];
    return;
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