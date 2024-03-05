# Changelog Pyenv4Jeedom bêta

> :memo: ***Remarque***  
> Si une mise à jour du plugin en version bêta est disponible sans détails correspondants sur cette page, cela signifie
> que seule la documantation a été mise à jour.

## TODO
- Suppression manuelle des virtualenv

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