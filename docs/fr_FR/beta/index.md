# Présentation du plugin pyenv4Jeedom (bêta)

> :memo: ***Remarque***  
> Il s'agit de la documentation du plugin en version bêta. Les fonctionnalitées à venir sont listées dans la todo-liste
> au début du changelog et ne sont donc pas évoquées ici.

Le plugin pyenv4Jeedom (pyenv) permet d'externaliser les fonctions de pyenv. Ce plugin est une dépendance pour un autre
plugin que vous avez installé. Si ne développez pas un plugin nécessitant pyenv, cette documentation ne vous sera
d'aucune utilité.

***

# Le principe

pyenv est un outil permettant d'utiliser la version de python que vous souhaitez et pyenv4Jeedom vous permet d'isoler
l'exécution de votre script python dans une virtualenv dédié. Il est possible de créer plusieurs virtualenv avec des
versions de python différentes.

# Installation du plugin

Depuis la version 4.2 de Jeedom, il est conseillé d'utiliser la méthode d'installation de votre plugin avec le fichier
**packages.json**. Dans ce fichier, il faut inclure `pyenv` dans les plugins à installer :
```json
{

  "plugin": {
    "pyenv": {}
  },

}
```

# Utilisation

Toutes les interactions avec pyenv4Jeedom se font via des méthodes statiques de la classe `pyenv`, une sous-classe de
`eqLogic`. Il faut systématiquement faire les appels dans un bloc `try {...} catch (Exception $e) {...}` afin que vous
puissiez savoir si tout s'est bien passé.

## pyenv::createVirtualenv

`pyenv::createVirtualenv` crée un virtualenv pour un plugin et installe les modules

### Description

```php
pyenv::createVirtualenv($_pluginId, $_pythonVersion, $_requirements, $_suffix='none')
```

**pyenv::createVirtualenv()** :
- installe la version de python $_pythonVersion si elle n'est pas installée,
- crée un virtualenv du nom du $_pluginId avec un suffix afin de pouvoir créer plusieurs virtualenv pour le même
plugin, même si les cas d'usage seront très rares.
- installe les modules nécessaires

### Liste des paramètres

**$pluginId**: l'id du plugin pour lequel le virtualenv est installé. Si le plugin n'est pas installé, une exception
est levée. Si le nom du virtualenv existe déjà, une exception est levée.

**$_pythonVersion**: la version de python pour laquelle le virtualenv doit être installé. Si cette version n'est pas
installée, cette instruction l'installera. La version doit être disponible à l'installation sans quoi une exception est
levée.

**$_requirements**: si c'est le chemin d'un fichier, ce fichier doit être au format `requirements.txt`. Les modules
décrits seront installés. $_requirements peut également être le contenu d'un fichier `requirements.txt`.

**$_suffix**: afin de différencier les virtualenv installés pour un même plugin, ceux-ci doivent avoir un suffix
différent. Le nom réel pour le virtualenv est `$pluginId . '++' . $_suffix`.

### Valeur de retour

Pas de valeur de retour. En cas d'erreur, une exception est levée.

### Exemple

```php
try {
  pyenv::createVirtualenv('mymodbus', '3.12.2', 'pymodbus==3.2.2');
} catch (Exception $e) {
  
}
```

