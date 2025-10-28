# Guide Complet du Système de Blocage des Comptes

## Vue d'ensemble
Ce guide détaille le processus complet de blocage des comptes depuis la création des fichiers jusqu'aux tests de chaque type de blocage (minutes, heures, jours, mois).

## Prérequis
- Migration `2025_10_28_094841_add_blocking_fields_to_comptes_table.php` exécutée
- Job `ProcessScheduledAccountBlocks` créé et planifié
- Routes API configurées
- Documentation Swagger générée

## 1. Structure des Données

### Champs Ajoutés à la Table `comptes`
```sql
-- Champs pour la planification du blocage
date_debut_blocage_planifiee TIMESTAMP NULL
duree_blocage_valeur INTEGER NULL
duree_blocage_unite ENUM('minutes', 'heures', 'jours', 'mois') NULL

-- Champs pour le blocage effectif
date_debut_blocage TIMESTAMP NULL
date_fin_blocage_prevue TIMESTAMP NULL
date_fin_blocage TIMESTAMP NULL
```

## 2. Types de Blocage

### 2.1 Blocage Immédiat
**Endpoint**: `POST /api/v1/comptes/{uuid}/bloquer`

**Description**: Bloque immédiatement un compte épargne actif.

**Règles**:
- Uniquement les comptes épargne actifs
- Durée en jours ou mois
- Statut change immédiatement à 'bloque'

### 2.2 Blocage Planifié
**Endpoint**: `POST /api/v1/comptes/{uuid}/planifier-blocage`

**Description**: Planifie un blocage futur qui s'activera automatiquement.

**Règles**:
- Uniquement les comptes épargne actifs
- Date de début dans le futur
- Durée en minutes, heures, jours ou mois
- Job automatique vérifie chaque minute

## 3. Exemples Pratiques de Test

### Préparation des Tests

#### 3.1 Créer un Compte Épargne Actif
```bash
# Via API
curl -X POST http://localhost:8000/api/v1/comptes \
  -H "Content-Type: application/json" \
  -d '{
    "type": "epargne",
    "soldeInitial": 100000,
    "devise": "FCFA",
    "client": {
      "titulaire": "Test User",
      "email": "test@example.com",
      "telephone": "771234567",
      "nci": "1234567890123",
      "adresse": "Dakar, Sénégal"
    }
  }'
```

#### 3.2 Récupérer l'UUID du Compte
```bash
# Lister les comptes pour trouver l'UUID
curl http://localhost:8000/api/v1/comptes?type=epargne&statut=actif
```

### 3.2 Tests de Blocage Immédiat

#### Test 1: Blocage de 7 Jours
```bash
curl -X POST http://localhost:8000/api/v1/comptes/{UUID_DU_COMPTE}/bloquer \
  -H "Content-Type: application/json" \
  -d '{
    "motif": "Test blocage 7 jours",
    "duree": 7,
    "unite": "jours"
  }'
```

**Résultat Attendu**:
```json
{
  "success": true,
  "message": "Compte bloqué avec succès",
  "data": {
    "id": "uuid-du-compte",
    "numero_compte": "ABC123",
    "statut": "bloque",
    "date_debut_blocage": "2025-10-28T10:50:00Z",
    "date_fin_blocage_prevue": "2025-11-04T10:50:00Z",
    "motif_blocage": "Test blocage 7 jours"
  }
}
```

#### Test 2: Blocage de 2 Mois
```bash
curl -X POST http://localhost:8000/api/v1/comptes/{UUID_DU_COMPTE}/bloquer \
  -H "Content-Type: application/json" \
  -d '{
    "motif": "Maintenance système",
    "duree": 2,
    "unite": "mois"
  }'
```

#### Test 3: Erreur - Compte Non Épargne
```bash
# Créer d'abord un compte courant
curl -X POST http://localhost:8000/api/v1/comptes \
  -H "Content-Type: application/json" \
  -d '{
    "type": "courant",
    "soldeInitial": 50000,
    "devise": "FCFA",
    "client": {
      "id": "uuid-client-existant"
    }
  }'

# Tenter de le bloquer
curl -X POST http://localhost:8000/api/v1/comptes/{UUID_COMPTE_COURANT}/bloquer \
  -H "Content-Type: application/json" \
  -d '{
    "motif": "Test erreur",
    "duree": 1,
    "unite": "jours"
  }'
```

**Résultat Attendu**:
```json
{
  "success": false,
  "message": "Seuls les comptes épargne actifs peuvent être bloqués",
  "errors": {}
}
```

### 3.3 Tests de Blocage Planifié

#### Test 1: Blocage dans 5 Minutes
```bash
# Calculer la date dans 5 minutes
DATE_FUTURE=$(date -u +"%Y-%m-%dT%H:%M:%SZ" -d "+5 minutes")

curl -X POST http://localhost:8000/api/v1/comptes/{UUID_DU_COMPTE}/planifier-blocage \
  -H "Content-Type: application/json" \
  -d '{
    "dateDebut": "'$DATE_FUTURE'",
    "duree": 30,
    "unite": "minutes",
    "motif": "Test blocage planifié 30 minutes"
  }'
```

**Résultat Attendu**:
```json
{
  "success": true,
  "message": "Blocage planifié avec succès",
  "data": {
    "compteId": "uuid-du-compte",
    "dateDebutBlocage": "2025-10-28T10:55:00Z",
    "dateFinBlocagePrevue": "2025-10-28T11:25:00Z",
    "motif": "Test blocage planifié 30 minutes",
    "duree": 30,
    "unite": "minutes"
  }
}
```

#### Test 2: Blocage dans 2 Heures
```bash
DATE_FUTURE=$(date -u +"%Y-%m-%dT%H:%M:%SZ" -d "+2 hours")

curl -X POST http://localhost:8000/api/v1/comptes/{UUID_DU_COMPTE}/planifier-blocage \
  -H "Content-Type: application/json" \
  -d '{
    "dateDebut": "'$DATE_FUTURE'",
    "duree": 4,
    "unite": "heures",
    "motif": "Maintenance serveur"
  }'
```

#### Test 3: Blocage dans 3 Jours
```bash
DATE_FUTURE=$(date -u +"%Y-%m-%dT%H:%M:%SZ" -d "+3 days")

curl -X POST http://localhost:8000/api/v1/comptes/{UUID_DU_COMPTE}/planifier-blocage \
  -H "Content-Type: application/json" \
  -d '{
    "dateDebut": "'$DATE_FUTURE'",
    "duree": 1,
    "unite": "mois",
    "motif": "Audit annuel"
  }'
```

#### Test 4: Blocage dans 1 Mois
```bash
DATE_FUTURE=$(date -u +"%Y-%m-%dT%H:%M:%SZ" -d "+1 month")

curl -X POST http://localhost:8000/api/v1/comptes/{UUID_DU_COMPTE}/planifier-blocage \
  -H "Content-Type: application/json" \
  -d '{
    "dateDebut": "'$DATE_FUTURE'",
    "duree": 15,
    "unite": "jours",
    "motif": "Révision réglementaire"
  }'
```

#### Test 5: Erreur - Date Passée
```bash
DATE_PASSEE="2025-10-27T10:00:00Z"

curl -X POST http://localhost:8000/api/v1/comptes/{UUID_DU_COMPTE}/planifier-blocage \
  -H "Content-Type: application/json" \
  -d '{
    "dateDebut": "'$DATE_PASSEE'",
    "duree": 1,
    "unite": "jours",
    "motif": "Test erreur date passée"
  }'
```

**Résultat Attendu**:
```json
{
  "success": false,
  "message": "The date debut field must be a date after now.",
  "errors": {
    "dateDebut": ["The date debut field must be a date after now."]
  }
}
```

## 4. Vérification des Résultats

### 4.1 Vérifier l'État du Compte
```bash
# Après blocage immédiat
curl http://localhost:8000/api/v1/comptes/id/{UUID_DU_COMPTE}
```

### 4.2 Vérifier les Blocages Planifiés
```sql
-- Dans la base de données
SELECT id, numero_compte, date_debut_blocage_planifiee,
       duree_blocage_valeur, duree_blocage_unite, motif_blocage
FROM comptes
WHERE statut = 'actif'
AND date_debut_blocage_planifiee IS NOT NULL;
```

### 4.3 Forcer l'Exécution du Job
```bash
# Exécuter manuellement le job de blocage planifié
php artisan archive:run --type=block
```

### 4.4 Vérifier les Logs
```bash
tail -f storage/logs/laravel.log | grep -i "block\|blocage"
```

## 5. Tests Automatisés

### 5.1 Test des Différentes Unités
```bash
# Script de test complet
#!/bin/bash

echo "=== Tests de Blocage de Comptes ==="

# Test 1: Blocage immédiat 7 jours
echo "Test 1: Blocage immédiat 7 jours"
curl -X POST http://localhost:8000/api/v1/comptes/$COMPTE_UUID/bloquer \
  -H "Content-Type: application/json" \
  -d '{"motif": "Test immédiat", "duree": 7, "unite": "jours"}'

echo -e "\nTest 2: Blocage planifié 5 minutes"
DATE_5MIN=$(date -u +"%Y-%m-%dT%H:%M:%SZ" -d "+5 minutes")
curl -X POST http://localhost:8000/api/v1/comptes/$COMPTE_UUID2/planifier-blocage \
  -H "Content-Type: application/json" \
  -d "{\"dateDebut\": \"$DATE_5MIN\", \"duree\": 10, \"unite\": \"minutes\", \"motif\": \"Test 5min\"}"

echo -e "\nAttente de 6 minutes..."
sleep 360

echo -e "\nVérification du blocage automatique:"
curl http://localhost:8000/api/v1/comptes/id/$COMPTE_UUID2

echo -e "\n=== Tests terminés ==="
```

### 5.2 Tests de Performance
```bash
# Tester avec plusieurs comptes
for i in {1..10}; do
  curl -X POST http://localhost:8000/api/v1/comptes/$COMPTE_UUID/planifier-blocage \
    -H "Content-Type: application/json" \
    -d "{\"dateDebut\": \"$(date -u +"%Y-%m-%dT%H:%M:%SZ" -d "+$i minutes")\", \"duree\": $i, \"unite\": \"heures\", \"motif\": \"Test perf $i\"}" &
done
```

## 6. Dépannage

### 6.1 Job ne s'exécute pas
```bash
# Vérifier que le scheduler fonctionne
php artisan schedule:list

# Exécuter manuellement
php artisan schedule:run

# Ou forcer le job
php artisan archive:run --type=block
```

### 6.2 Erreurs de Validation
- Vérifier que le compte est de type 'epargne'
- Vérifier que le statut est 'actif'
- Pour planification : date doit être dans le futur
- Durée doit être > 0

### 6.3 Problèmes de Base de Données
```sql
-- Vérifier la structure des tables
\d comptes

-- Voir les blocages planifiés
SELECT * FROM comptes WHERE date_debut_blocage_planifiee IS NOT NULL;
```

## 7. Flux Complet de Test

### Étape 1: Préparation
```bash
# Créer des comptes de test
# Migration déjà exécutée
# Routes configurées
```

### Étape 2: Tests Immédiats
```bash
# Tester blocage immédiat avec différentes durées
# Vérifier changements de statut
```

### Étape 3: Tests Planifiés
```bash
# Planifier des blocages à différentes échelles de temps
# Attendre ou forcer l'exécution
# Vérifier l'application automatique
```

### Étape 4: Tests d'Erreurs
```bash
# Tester avec comptes non-épargne
# Tester avec dates passées
# Tester avec durées invalides
```

### Étape 5: Validation
```bash
# Vérifier logs
# Vérifier base de données
# Tester via Swagger UI
```

## 8. Métriques de Test

### Succès Attendus
- ✅ Blocage immédiat fonctionne
- ✅ Planification fonctionne pour toutes les unités
- ✅ Job automatique s'exécute
- ✅ Calculs de dates corrects
- ✅ Gestion d'erreurs appropriée

### Points de Vigilance
- ⚠️ Performance avec beaucoup de comptes planifiés
- ⚠️ Précision des calculs de dates
- ⚠️ Gestion des fuseaux horaires
- ⚠️ Concurrence lors de l'exécution du job

Ce guide vous permet de tester exhaustivement chaque aspect du système de blocage des comptes.
