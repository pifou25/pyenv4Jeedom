# Présentation du plugin pyenv4Jeedom (bêta)

> :memo: ***Remarque***  
> Il s'agit de la documentation du plugin en version bêta. Les fonctionnalitées à venir sont listées dans la todo-liste
> au début du changelog et ne sont donc pas évoquées ici.

Le plugin pyenv4Jeedom (pyenv) permet d'externaliser les fonctions de pyenv. Ce plugin est une dépendance pour un autre
plugin que vous avez installé. Si vous ne développez pas un plugin nécessitant pyenv, cette documentation ne vous sera
d'aucune utilité.

# Configuration

Il est seulement possible de configurer pyenv4Jeedom pour inclure pyenv au backup ou pas. La configuration par défaut
est sans inclusion afin de limiter la taille du backup et les ressources nécessaires pour créer le backup. Le choix
pour cette option est laissée à l'utilisateur en fonction de la place et des ressources disponibles sur son système.

***

# Le principe

pyenv est un outil permettant d'utiliser la version de python que vous souhaitez et pyenv4Jeedom vous permet d'isoler
l'exécution de votre script python dans un virtualenv dédié. Il est possible de créer plusieurs virtualenv avec des
versions de python différentes ou avec des versions du même module différentes.

Il est possible d'installer une version plus récente que celle du système mais aussi plus ancienne afin de pouvoir
faire fonctionner une ancienne version d'un module.

Finalement pyenv permet de garantir la compatibilité de votre plugin quelle que soit la version debian sur laquelle est
installée Jeedom en fixant les versions à utiliser.

# Installation du plugin

## Version stable

Depuis la version 4.2 de Jeedom, il est conseillé d'utiliser la méthode d'installation de votre plugin avec le fichier
**packages.json**. Dans ce fichier, il faut inclure `pyenv` dans les plugins à installer :
```json
{

  "plugin": {
    "pyenv": {}
  },

}
```

## Version bêta

Pour installer la version bêta, comme il n'y a pas de moyen via le fichier **packages.json**, il est possible de le
faire en php dans le fichier **plugin_info/install.php**. Dans ce fichier il est possible de créer une fonction
**install_pyenv** qui, dans le plugin MyModbus est le suivant :
```php
function install_pyenv() {
  $myModbusId = 'mymodbus';
  $myModbusUpdate = update::byLogicalId($myModbusId);
  $myModbusVersion = $myModbusUpdate->getConfiguration('version');

  $pluginId = 'pyenv';
  $update = update::byLogicalId($pluginId);
  if (!is_object($update)) {
    $update = new update();
    $update->setLogicalId($pluginId);
  }
  $update->setSource('market');
  $update->setConfiguration('version', $myModbusVersion);
  $update->save();
  $update->doUpdate();
  $plugin = plugin::byId($pluginId);
  if (!is_object($plugin)) {
    log::add($myModbusId, 'error', sprintf(__("** Installation ** : plugin non trouvé : %s", __FILE__), $pluginId));
    die();
  }
  $plugin->setIsEnable(1);
  $plugin->dependancy_install();
  log::add($myModbusId, 'info', sprintf(__("** Installation ** : installation terminée : %s", __FILE__), $pluginId));

  mymodbus::init_pyenv();
}
```

Cette fonction installe la même version (bêta ou stable) que celle du plugin MyModbus. Elle est appelée dans les
fonctions **mymodbus_install** et **mymodbus_update**. A la fin, la fonction **init_pyenv** du plugin MyModbus est
lancée.

# Implémentation

La fonction **init_pyenv** évoquée dans le chapitre [Installation du plugin](#installation-du-plugin) est la suivante :

```php
public static function init_pyenv() {
  pyenv::init();
  $requirements = array('requests', 'pyserial', 'pyudev', 'pymodbus==3.2.2');
  try {
    pyenv::createVirtualenv(__CLASS__, mymodbusConst::PYENV_PYTHON, implode("\n", $requirements), mymodbusConst::PYENV_SUFFIX);
  } catch (Exception $e) {
    // Déjà installé
  }
  
  try {
    $virtualenvs = pyenv::getVirtualenvNames(__CLASS__, mymodbusConst::PYENV_PYTHON, mymodbusConst::PYENV_SUFFIX);
  } catch (Exception $e) {
    throw new Exception(__('Impossible de lister les virtualenv du plugin pyenv4Jeedom', __FILE__));
  }

  $ret = null;

  foreach ($virtualenvs as $virtualenv) {
    if ($virtualenv['suffix'] !== mymodbusConst::PYENV_SUFFIX || $virtualenv['python'] !== mymodbusConst::PYENV_PYTHON) {
      try {
        pyenv::deleteVirtualenv(__CLASS__, $virtualenv['suffix']);
      } catch (Exception $e) {
        throw new Exception(sprintf(__("Impossible de supprimer le virtualenv avec le suffixe '%s' du plugin pyenv4Jeedom", __FILE__), $virtualenv['suffix']));
      }
    } else {
      $ret = $virtualenv['fullname'];
    }
  }
  return $ret;
}
```

Elle crée le virtualenv nécessaire à MyModbus et supprime es autres virtualenv installés par MyModbus.

Le lancement du démon se fait de la manière suivante :

```php
$virtualenv = self::init_pyenv();
if (is_null($virtualenv))
  throw new Exception(__('L\'environnement pyenv n\'a pas pu être installé', __FILE__));

// Création des valeurs d'argument avec escapeshellarg()

$script = realpath(__DIR__ . '/../../ressources/mymodbusd/mymodbusd.py');
$args = '--socketport ' . $socketPort . ' --loglevel ' . $daemonLoglevel . ' --apikey ' . $daemonApikey . ' --callback ' . $daemonCallback . ' --json ' . $jsonEqConfig;

log::add('mymodbus', 'info', 'Lancement du démon mymodbus : ' . $script);
$result = pyenv::runPyenv($script, $args, $virtualenv, true);
```

# Utilisation

Toutes les interactions avec pyenv4Jeedom se font via des méthodes statiques de la classe `pyenv`, une sous-classe de
`eqLogic`. Il faut systématiquement faire les appels dans un bloc `try {...} catch (Exception $e) {...}` afin que vous
puissiez savoir si tout s'est bien passé.

## pyenv::createVirtualenv

`pyenv::createVirtualenv` crée un virtualenv pour un plugin et installe les modules

### Description

```php
pyenv::createVirtualenv($_pluginId, $_pythonVersion, $_requirements, $_suffix='none', $_upgrade=false);
```

**pyenv::createVirtualenv()** :
- installe la version de python *$_pythonVersion* si elle n'est pas installée,
- crée un virtualenv du nom du *$_pluginId* avec un suffixe afin de pouvoir créer plusieurs virtualenv pour le même
plugin, même si les cas d'usage seront très rares,
- installe les modules nécessaires,
- si *$_upgrade* est vrai (true), supprime le virtualenv s'il existe et le réinstalle dans la nouvelle version de
python.

### Liste des paramètres

**$pluginId**: (string) l'id du plugin pour lequel le virtualenv est installé. Si le plugin n'est pas installé, une
exception est levée. Si le nom du virtualenv existe déjà, une exception est levée.

**$_pythonVersion**: (string) la version de python pour laquelle le virtualenv doit être installé. Si cette version
n'est pas installée, cette instruction l'installera. La version doit être disponible à l'installation sans quoi une
exception est levée.

**$_requirements**: (string) si c'est le chemin d'un fichier, ce fichier doit être au format `requirements.txt`. Les
modules décrits seront installés. *$_requirements* peut également être le contenu d'un fichier `requirements.txt`.

**$_suffix**: (string) afin de différencier les virtualenv installés pour un même plugin, ceux-ci doivent avoir un
suffixe différent. Le nom réel pour le virtualenv est `$_pluginId . '++' . $_suffix`.

**$_upgrade**: (boolean) à mettre à true pour mettre la version de python d'un virtualenv existant à niveau.

### Valeur de retour

Pas de valeur de retour. En cas d'erreur, une exception est levée.

### Exemple

```php
try {
  pyenv::createVirtualenv('mymodbus', '3.11.4', 'pymodbus==3.2.2', 'pymodbus3.2.2');
} catch (Exception $e) {
  
}
```

## pyenv::deleteVirtualenv

### Description

```php
pyenv::deleteVirtualenv($_pluginId, $_suffix='none');
```

**pyenv::deleteVirtualenv** :
- supprime le virtualenv pour le plugin *$_pluginId* avec le suffixe *$_suffix*,
- supprime la version de python dans laquelle ce virtualenv a été installé si aucun autre virtualenv n'est installé
dans cette version.

### Liste des paramètres

**$_pluginId**: (string) l'id du plugin pour lequel le virtualenv doit être supprimé. Si le plugin n'est pas installé,
une exception est levée.

**$_suffix**: (string) le suffixe du virtualenv à supprimer. Si aucun virtualenv ne correspond, rien n'est fait et
aucune exception n'est levée.

### Valeur de retour

Pas de valeur de retour. En cas d'erreur, une exception est levée.

### Exemple

```php
try {
  pyenv::deleteVirtualenv('mymodbus');
} catch (Exception $e) {
  
}
```

## pyenv::getVirtualenvNames

### Description

```php
pyenv::getVirtualenvNames($_pluginId='', $_pythonVersion='', $_suffix='');
```

**pyenv::getVirtualenvNames** recherche les virtualenv selon des critères de recherche.  
Recommandé pour récupérer le nom du virtualenv à utiliser pour les commandes **pyenv::runPyenv** ou
**pyenv::sourceScript**.

### Liste des paramètres

**$_pluginId**: l'id du plugin pour lequel le virtualenv doit être recherché. Si le plugin n'est pas installé, une
exception est levée. Si ce paramètre n'est pas précisé, les virtualenv de tous les plugins seront listés.

**$_pythonVersion**: (string) la version de python pour laquelle le virtualenv doit être recherché. Si ce paramètre
n'est pas précisé, les virtualenv de toutes les versions de python seront listées.

**$_suffix**: (string) le suffixe du virtualenv à rechercher. Si ce paramètre n'est pas précisé, tous les virtualenv
seront listées.

### Valeur de retour

Retourne une liste (array) avec le nom du virtualenv et la version python des virtualenv correspondants aux critères de
recherche.

### Exemple

```php
try {
  pyenv::getVirtualenvNames('mymodbus', '3.11.4');
} catch (Exception $e) {
  
}
```
Retourne :
```php
array (
  0 =>    array (
    'fullname' => 'mymodbus++pymodbus3.2.2',
    'suffix' => 'pymodbus3.2.2',
    'python' => '3.11.4'
  ),
  1 =>    array (
    'fullname' => 'mymodbus++pymodbus3.5.2',
    'suffix' => 'pymodbus3.5.2',
    'python' => '3.11.4'
  ),
  2 =>    array (
    'fullname' => 'mymodbus++pymodbus3.6.4',
    'suffix' => 'pymodbus3.6.4',
    'python' => '3.11.4'
    )
  )
```

## pyenv::runPyenv

### Description

```php
pyenv::runPyenv($_command, $_args='', $_virtualenv=null, $_daemon=false, $_lock=false);
```

**pyenv::runPyenv** lance une commande dans l'environnement pyenv. Il est possible de préciser un virtualenv, de
préciser que la commande doit être lancée comme un démon, auquel cas un virtualenv doit être spécifié.

### Liste des paramètres

**$_command**: (string) commande à exécuter. S'il s'agit d'un fichier, une commande `cd` sera exécutée vers le
répertoire du fichier avant d'exécuter la commande.

**$_args**: (string) les arguments de la commande. A préciser surtout si la commande est un fichier script.

**$_virtualenv**: (string) le nom du virtualenv dans lequel exécuter la commande. Si non précisé, le résultat est
l'équivalent de `exec($_commande . ' ' . $_args)`.

**$_daemon**: (boolean) mode démon. Avec un virtualenv, la sortie du script est redirigée vers le log du plugin.

**$_lock_**: (boolean) commande bloquante. Si *$_lock* vaut `true`, aucune autre commande ne pourra être exécutée via
**pyenv::runPyenv**

### Valeur de retour

Retourne le résultat de la commande dans un array, à raison d'un élément par ligne de retour.  
Ne retourne rien en mode démon.

### Exemple

```php
$args = '-a -b "valeur"';
$virtualenvs = pyenv::getVirtualenvNames('mymodbus', '3.11.4', 'pymodbus3.2.2');
try {
  pyenv::runPyenv(realpath(__DIR__ . '/../../ressources/super_script.py'), $args, $virtualenvs[0]['name']);
} catch (Exception $e) {
  
}
```

## pyenv::sourceScript

### Description

```php
pyenv::sourceScript($_command, $_args='', $_virtualenv=null, $_daemon=false);
```

**pyenv::sourceScript** génère le contenu d'un script shell pour exécuter la commande dans l'environnement pyenv.

### Liste des paramètres

Identique à **pyenv::runPyenv**.

### Valeur de retour

Retourne contenu d'un script shell (sans la ligne shebang) pour exécuter la commande sous forme de string.

### Exemple


```php
$args = '-a -b "valeur"';
$virtualenvs = pyenv::getVirtualenvNames('mymodbus', '3.11.4', 'pymodbus3.2.2');
try {
  $script = pyenv::sourceScript(realpath(__DIR__ . '/../../ressources/super_script.py'), $args, $virtualenvs[0]['name']);
} catch (Exception $e) {
  
}
```

