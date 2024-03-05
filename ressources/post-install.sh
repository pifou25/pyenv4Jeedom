# post-install script for Jeedom plugin pyenv4Jeedom
PROGRESS_FILE=/tmp/jeedom_install_in_progress_pyenv
if [ -n "$1" ]; then
	PROGRESS_FILE="$1"
fi
if [ -d ../../plugins/pyenv ]; then
  cd ../../plugins/pyenv
else
  echo "Ce script doit être appelé depuis .../core/data"
  exit
fi
TMP_FILE=/tmp/post-install_pyenv_bashrc
SHELL_INIT="$(realpath ressources)/shell_init"
PYENV_UPDATE="$(realpath ressources)/pyenv_update"
export PYENV_ROOT="$(realpath ressources)/pyenv"

touch "$PROGRESS_FILE"
echo 5 > "$PROGRESS_FILE"
echo "********************************************************"
echo "*            Installation de pyenv                     *"
echo "********************************************************"
date
ldconfig

pyenv_bashrc="export PYENV_ROOT=\"$PYENV_ROOT\"
command -v pyenv >/dev/null || export PATH=\"\$PYENV_ROOT/bin:\$PATH\"
eval \"\$(pyenv init -)\"
eval \"\$(pyenv virtualenv-init -)\""

cat > $SHELL_INIT << EOF
$pyenv_bashrc
EOF

if [ -d "$PYENV_ROOT" ] && [ ! -d "$PYENV_ROOT/.git" ]; then
  echo "********************************************************"
  echo "** Le répertoire pyenv n'est pas complet, il est donc supprimé"
  rm -rf "$PYENV_ROOT"
fi
if [ ! -d "$PYENV_ROOT" ]; then
  echo "********************************************************"
  echo "** Installation de pyenv dans $PYENV_ROOT"
  sudo -E -u www-data curl https://pyenv.run | bash
  chown -R www-data:www-data "$PYENV_ROOT"
fi

echo "********************************************************"
echo "** Mise à jour de pyenv"
cat > $PYENV_UPDATE << EOF
$pyenv_bashrc
pyenv update
EOF
sudo -E -u www-data sh $PYENV_UPDATE
chown -R www-data:www-data "$PYENV_ROOT"
rm $PYENV_UPDATE

echo 90 > "$PROGRESS_FILE"

echo "********************************************************"
echo "** Préparation de l'environnement shell pour root et www-data"
grep -vi pyenv ~/.bashrc > "$TMP_FILE"
cat "$TMP_FILE" > ~/.bashrc
cat >> ~/.bashrc<< EOF
$pyenv_bashrc
EOF

sudo -E -u www-data grep -vi pyenv ~www-data/.bashrc > "$TMP_FILE"
cat "$TMP_FILE" > ~www-data/.bashrc
sudo -E -u www-data cat >> ~www-data/.bashrc<< EOF
$pyenv_bashrc
EOF

echo 100 > "$PROGRESS_FILE"
rm "$TMP_FILE"
echo "********************************************************"
echo "*           Installation terminée                      *"
echo "********************************************************"
date