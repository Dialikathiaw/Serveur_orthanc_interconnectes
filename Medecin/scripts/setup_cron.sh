#!/bin/bash

# Script pour configurer la tâche CRON automatiquement
# Exécuter avec : bash setup_cron.sh

echo "Configuration de la tâche CRON pour la vérification des serveurs Orthanc..."

# Obtenir le chemin absolu du script PHP
SCRIPT_PATH="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)/serveurs/cron_verification.php"

# Vérifier que le script existe
if [ ! -f "$SCRIPT_PATH" ]; then
    echo "Erreur : Le script $SCRIPT_PATH n'existe pas"
    exit 1
fi

# Créer la ligne cron (exécution toutes les 5 minutes)
CRON_LINE="*/5 * * * * /usr/bin/php $SCRIPT_PATH"

# Ajouter la tâche cron
(crontab -l 2>/dev/null; echo "$CRON_LINE") | crontab -

echo "Tâche CRON ajoutée avec succès !"
echo "La vérification des serveurs s'exécutera toutes les 5 minutes."
echo ""
echo "Pour vérifier que la tâche est bien configurée :"
echo "crontab -l"
echo ""
echo "Pour voir les logs de vérification :"
echo "tail -f $(dirname "$SCRIPT_PATH")/cron_verification.log"
echo ""
echo "Pour tester manuellement le script :"
echo "php $SCRIPT_PATH"
