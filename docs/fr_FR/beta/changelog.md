# Changelog Pyenv4Jeedom bêta

> :memo: ***Remarque***  
> Si une mise à jour du plugin en version bêta est disponible sans détails correspondants sur cette page, cela signifie
> que seule la documantation a été mise à jour.

## TODO
- Modifier le répertoire d'installation de pyenv vers /opt/pyenv
- Mettre la documentation à jour

## 03/04/2024 V0.8 beta
- Ajout d'une information sur la page du plugin concernant le temps depuis lequel une commande bloquante est en cours
- Possibilité de réinitialiser le verrou pour les commandes bloquantes après 5 minutes si la commande en question n'est
pas exécutée

## 27/03/2024 V0.7 beta
- Correction d'une erreur possible dans la page santé
- Améliorations mineures

## 16/03/2024 V0.6 beta
- Utilisation du répertoire 'resources' au lieu de 'ressources'. Les versions de python et les virtualenv doivent être
réinstallés :
  - Avant la mise à jour, il faut arrêter les démons des plugins qui utilisent pyenv4Jeedom
  - Il faut réinstaller les dépendances si ce n'est pas fait automatiquement
- Documentation à jour

## 10/03/2024 V0.5 beta
- Ajout des infos de santé

## 10/03/2024 V0.4 beta
- Suppression manuelle des virtualenv possible depuis la page du plugin
- Correction de l'information sur la page du plugin si une commande bloquante est en cours

## 07/03/2024 V0.3 beta
- Nettoyage des fichiers .bashrc si pyenv4Jeedom a été installé avec une version précédente
- Pas de mise à jour de pyenv dans post-install.sh puisque la mise à jour est faite avant l'installation d'un
virtualenv
- Ajout d'une information sur la page du plugin si une commande bloquante est en cours

## 05/03/2024 V0.2 beta
- Correction d'une erreur de syntaxe dans post-install.sh
- Amélioration des messages de log debug
- Ajout du paramètre *$_lock* à runPyenv pour empêcher l'exécution d'autres commandes parce que `pyenv install ...`
pouvait être lancé plusieurs fois en même temps
- Documentation à jour

## 03/03/2024 V0.1 beta (Version initiale)
- Commandes uniquement via script php depuis un autre plugin :
  - Possibilité de créer des virtualenv dans la version python souhaitée
  - Possibilité de supprimer un virtualenv
  - Possibilité de lister les virtualenv installés
  - Posibilités de lancer une commande dans l'environnement pyenv
  - Posibilités de générer un script pour l'exécution d'une commande dans l'environnement pyenv
- Documentation à jour avec exemple d'utilistion dans MyModbus