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
...
  "plugin": {
    "pyenv"
  },
...
}
```

# Utilisation

Toutes les interactions avec pyenv4Jeedom se font via des méthodes statiques de la classe `pyenv`, une sous-classe de
`eqLogic`. Il faut systématiquement faire les appels dans un bloc `try {...} catch (Exception $e) {...}` afin que vous
puissiez savoir si tout s'est bien passé.



