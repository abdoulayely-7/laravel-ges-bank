# Guide de Déploiement Laravel + Swagger sur Render avec Docker

## Vue d'ensemble
Ce guide explique comment déployer votre application Laravel avec documentation Swagger sur Render en utilisant Docker. Votre projet utilise PostgreSQL en local et sera déployé avec une base de données PostgreSQL managée sur Render.

## Prérequis
- Compte Render (https://render.com)
- Projet GitHub avec votre code
- Docker installé localement (pour les tests)

## Fichiers de configuration créés

### 1. Dockerfile
Le Dockerfile configure une image PHP 8.2 avec Apache, installe les dépendances et configure Laravel pour la production.

### 2. docker-compose.yml
Fichier pour les tests locaux avec PostgreSQL.

### 3. render.yaml
Configuration Render pour le déploiement automatisé.

### 4. .env.production
Variables d'environnement pour la production.

## Étape 1 : Préparation du projet

### 1.1 Générer la documentation Swagger
Avant de déployer, assurez-vous que la documentation est générée :

```bash
php artisan l5-swagger:generate
```

### 1.2 Créer la clé d'application
Générez une clé pour la production :

```bash
php artisan key:generate --show
```

Copiez cette clé pour l'utiliser plus tard.

### 1.3 Configurer les variables d'environnement
Modifiez le fichier `.env.production` avec vos vraies valeurs :

```env
APP_KEY=votre_clé_générée
APP_URL=https://votre-app.render.com
```

## Étape 2 : Configuration Render

### 2.1 Méthode recommandée : Utiliser render.yaml (Automatique)
1. Commitez et pushez tous les fichiers créés (Dockerfile, render.yaml, build.sh, etc.)
2. Allez sur https://dashboard.render.com
3. Cliquez sur "New" → "Blueprint"
4. Connectez votre repository GitHub
5. Render détectera automatiquement le fichier `render.yaml` et créera les services

### 2.2 Méthode alternative : Configuration manuelle
Si vous préférez configurer manuellement :
1. Cliquez sur "New" → "Web Service"
2. Connectez votre repository GitHub
3. **Name**: ges-bank-api
4. **Runtime**: Docker
5. **Build Command**: `./build.sh`
6. **Start Command**: `docker run -p $PORT:80 ges-bank`

### 2.3 Variables d'environnement
Ajoutez ces variables dans l'onglet "Environment" :

```
APP_ENV=production
APP_KEY=votre_clé_générée
DB_CONNECTION=pgsql
L5_SWAGGER_GENERATE_ALWAYS=false
```

Les variables de base de données seront automatiquement configurées par Render.

### 2.4 Créer la base de données
1. Dans Render, cliquez sur "New" → "PostgreSQL"
2. **Name**: ges-bank-db
3. **Database**: ges_bank_prod
4. **User**: ges_bank_user
5. Choisissez le plan (Starter est gratuit)

## Étape 3 : Déploiement GRATUIT sur Render

### 3.1 🚀 Render Web Service Gratuit + Supabase PostgreSQL

Render offre un **Web Service gratuit** (750 heures/mois) et nous utiliserons Supabase pour la base PostgreSQL gratuite.

#### Étape 1 : Créer la base de données Supabase (gratuite)
1. **Allez sur https://supabase.com**
2. **Créez un compte gratuit** (pas de carte bancaire)
3. **Cliquez "New project"**
4. **Remplissez** :
   - Name: `ges-bank-db`
   - Database Password: Choisissez un mot de passe fort
   - Region: Choisissez la plus proche (Europe West)
5. **Attendez** que le projet se crée (2-3 minutes)

#### Étape 2 : Récupérer les informations de connexion
1. Dans Supabase, allez dans **Settings** → **Database**
2. **Copiez la connection string** qui ressemble à :
   ```
   postgresql://postgres:[YOUR-PASSWORD]@db.xxxx.supabase.co:5432/postgres
   ```

#### Étape 3 : Déployer sur Render (Web Service gratuit)
1. **Allez sur https://dashboard.render.com**
2. **Cliquez "New" → "Web Service"**
3. **Connectez votre repository GitHub**
4. **Configurez le service** :
   - **Name**: `ges-bank-api`
   - **Runtime**: `Docker`
   - **Build Command**: `./build.sh`
   - **Start Command**: Laissez vide (utilise le Dockerfile)

#### Étape 4 : Variables d'environnement
Dans l'onglet **Environment**, ajoutez :

```
APP_ENV=production
APP_KEY=base64:7L2SMsmG+L8t870jAEAIvSximHu3xANP9EOfSbYqKg8=
DATABASE_URL=postgresql://postgres:votre_vrai_mot_de_passe_supabase@db.uuwsgqfteliiqkjbkwrl.supabase.co:5432/postgres
L5_SWAGGER_GENERATE_ALWAYS=false
```

**⚠️ IMPORTANT** : Remplacez `votre_vrai_mot_de_passe_supabase` par le **vrai mot de passe** que vous avez défini lors de la création du projet Supabase.

**Pour trouver votre mot de passe Supabase** :
1. Allez dans votre projet Supabase
2. Settings → Database
3. Le mot de passe est celui que vous avez choisi lors de la création

#### Étape 5 : Déploiement
1. **Cliquez "Create Web Service"**
2. **Render va builder et déployer automatiquement**
3. **Votre app sera accessible** sur une URL comme : `https://ges-bank-api.onrender.com`

### 3.2 URLs après déploiement
- **Application**: `https://ges-bank-api.onrender.com`
- **API Comptes**: `https://ges-bank-api.onrender.com/api/v1/comptes`
- **Documentation Swagger**: `https://ges-bank-api.onrender.com/api/documentation`

### 3.3 Avantages de cette solution :
- ✅ **100% gratuit** : Web Service Render (750h/mois) + Supabase PostgreSQL gratuit
- ✅ **Pas de carte bancaire** requise
- ✅ **Swagger intégré** : Documentation automatique
- ✅ **Production-ready** : HTTPS, scaling automatique
- ✅ **Base de données fiable** : Supabase gère tout

### 3.4 Dépannage
Si les migrations ne s'exécutent pas :
1. Vérifiez les logs Render
2. Assurez-vous que `DATABASE_URL` est correcte
3. Testez la connexion : `php artisan migrate:status`

### 3.5 Limites gratuites
- **Render Web Service** : 750 heures/mois (environ 31 jours)
- **Supabase** : 500MB database, 50MB file storage
- **Bande passante** : Suffisant pour un projet personnel

## Étape 4 : Migration de la base de données

### 4.1 Commandes de build personnalisées
Dans Render, ajoutez ces commandes dans "Build Command" :

```bash
docker build -t ges-bank . && docker run --rm ges-bank php artisan migrate --force
```

Ou créez un script `build.sh` :

```bash
#!/bin/bash
docker build -t ges-bank .
docker run --rm --env-file .env.production ges-bank php artisan migrate --force
docker run --rm --env-file .env.production ges-bank php artisan db:seed --force
```

## Étape 5 : Test du déploiement

### 5.1 Vérifier que l'application fonctionne
Une fois déployée, testez :
- L'URL principale : `https://votre-app.render.com`
- L'API : `https://votre-app.render.com/api/v1/comptes`
- La documentation Swagger : `https://votre-app.render.com/api/documentation`

### 5.2 Logs et débogage
- Vérifiez les logs dans Render Dashboard
- Utilisez `php artisan tinker` via SSH si nécessaire

## Configuration Docker locale (pour les tests)

### Test en local avec Docker
```bash
# Construire l'image
docker build -t ges-bank .

# Lancer avec docker-compose (pour les tests locaux)
docker-compose up -d

# Ou lancer manuellement
docker run -p 8080:80 --env-file .env.production ges-bank
```

## Variables d'environnement importantes

### Production
```
APP_ENV=production
APP_DEBUG=false
APP_KEY=votre_clé_unique
DB_CONNECTION=pgsql
LOG_LEVEL=error
CACHE_DRIVER=file
SESSION_DRIVER=file
```

### Développement local
```
APP_ENV=local
APP_DEBUG=true
DB_CONNECTION=pgsql
DB_HOST=localhost
DB_PORT=5432
DB_DATABASE=ges_bank_dev
DB_USERNAME=postgres
DB_PASSWORD=password
```

## Dépannage courant

### Erreur de connexion à la base de données
- Vérifiez les variables d'environnement
- Assurez-vous que la base Render est accessible
- Vérifiez les credentials dans Render Dashboard

### Erreur 500
- Vérifiez les logs : `docker logs <container_id>`
- Vérifiez les permissions des dossiers storage et bootstrap/cache
- Assurez-vous que APP_KEY est défini

### Documentation Swagger ne s'affiche pas
- Vérifiez que `php artisan l5-swagger:generate` a été exécuté
- Vérifiez les permissions sur `storage/api-docs/`
- URL : `https://votre-app.render.com/api/documentation`

### Migrations ne s'exécutent pas
- Ajoutez `--force` aux commandes artisan en production
- Vérifiez que la base de données est accessible

## Optimisations pour la production

### Cache
Activez le cache pour améliorer les performances :
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Sécurité
- Changez `APP_DEBUG=false`
- Utilisez HTTPS (activé par défaut sur Render)
- Configurez CORS si nécessaire
- Utilisez des variables d'environnement pour les secrets

## URLs importantes après déploiement

- **Application**: `https://votre-app.render.com`
- **API Comptes**: `https://votre-app.render.com/api/v1/comptes`
- **Documentation Swagger**: `https://votre-app.render.com/api/documentation`
- **Base de données**: Accessible via les credentials Render

## Support
Si vous rencontrez des problèmes :
1. Vérifiez les logs Render
2. Testez localement avec Docker
3. Vérifiez la configuration des variables d'environnement
4. Consultez la documentation Render : https://docs.render.com/
