# Guide Complet du Système d'Archivage Automatique

## Vue d'ensemble

Ce guide détaille le système d'archivage automatique des comptes bancaires bloqués. Le système utilise deux jobs Laravel pour déplacer les comptes entre la base de données principale (Render PostgreSQL) et une base d'archivage (Neon PostgreSQL).

## Architecture du Système

### Bases de données
- **Base principale** : PostgreSQL sur Render (comptes actifs)
- **Base d'archivage** : Neon PostgreSQL (comptes archivés)

### Jobs implémentés
1. `ArchiveBlockedAccounts` - Déplace les comptes bloqués vers l'archive
2. `UnarchiveExpiredBlockedAccounts` - Ramène les comptes expirés vers la base principale

## Job 1 : ArchiveBlockedAccounts

### Description
Ce job s'exécute quotidiennement et déplace tous les comptes ayant le statut `'bloque'` de la base principale vers la base Neon.

### Fréquence d'exécution
- **Automatique** : Tous les jours à 02:00 (via le scheduler Laravel)
- **Manuel** : `php artisan archive:run --type=archive`

### Processus détaillé

#### 1. Recherche des comptes à archiver
```php
$blockedAccounts = Compte::where('statut', 'bloque')->get();
```

#### 2. Pour chaque compte bloqué :
```php
foreach ($blockedAccounts as $compte) {
    DB::beginTransaction();

    try {
        // Récupération des données
        $compteData = $compte->toArray();
        $clientData = $compte->client->toArray();
        $userData = $compte->client->user->toArray();
        $transactions = Transaction::where('compte_id', $compte->id)->get();

        // Déplacement vers Neon
        $this->moveToNeon('users', $userData);
        $this->moveToNeon('clients', $clientData);
        $this->moveToNeon('comptes', $compteData);

        foreach ($transactions as $transaction) {
            $this->moveToNeon('transactions', $transaction->toArray());
        }

        // Suppression définitive de la base principale
        Transaction::where('compte_id', $compte->id)->delete();
        $compte->forceDelete();

        DB::commit();
        Log::info("Successfully moved account {$compte->id} to Neon archive");

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error("Failed to archive account {$compte->id}: " . $e->getMessage());
    }
}
```

#### 3. Gestion des erreurs
- Utilise les transactions pour garantir l'intégrité
- En cas d'erreur, rollback complet des opérations
- Logging détaillé des succès/échecs

## Job 2 : UnarchiveExpiredBlockedAccounts

### Description
Ce job s'exécute toutes les heures et vérifie si des comptes archivés ont dépassé leur date de déblocage prévue.

### Fréquence d'exécution
- **Automatique** : Toutes les heures (via le scheduler Laravel)
- **Manuel** : `php artisan archive:run --type=unarchive`

### Processus détaillé

#### 1. Recherche des comptes expirés dans Neon
```php
$expiredAccounts = DB::connection('neon')
    ->table('comptes')
    ->where('statut', 'bloque')
    ->where('date_deblocage_prevue', '<', now())
    ->get();
```

#### 2. Pour chaque compte expiré :
```php
foreach ($expiredAccounts as $archivedAccount) {
    DB::beginTransaction();

    try {
        // Récupération des données depuis Neon
        $userData = $this->getFromNeon('users', ['id' => $archivedAccount->client_id]);
        $clientData = $this->getFromNeon('clients', ['id' => $archivedAccount->client_id]);
        $transactions = DB::connection('neon')
            ->table('transactions')
            ->where('compte_id', $archivedAccount->id)
            ->get();

        // Recréation dans la base principale
        if ($userData) User::create($userData);
        if ($clientData) Client::create($clientData);

        $compteData = (array) $archivedAccount;
        $compteData['statut'] = 'actif';
        unset($compteData['motif_blocage']);
        unset($compteData['date_blocage']);
        unset($compteData['date_deblocage_prevue']);
        unset($compteData['date_deblocage']);

        Compte::create($compteData);

        foreach ($transactions as $transaction) {
            Transaction::create((array) $transaction);
        }

        // Suppression définitive de Neon
        DB::connection('neon')->table('transactions')->where('compte_id', $archivedAccount->id)->delete();
        DB::connection('neon')->table('comptes')->where('id', $archivedAccount->id)->delete();
        DB::connection('neon')->table('clients')->where('id', $archivedAccount->client_id)->delete();
        DB::connection('neon')->table('users')->where('id', $archivedAccount->client_id)->delete();

        DB::commit();
        Log::info("Successfully moved account {$archivedAccount->id} back from Neon archive");

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error("Failed to unarchive account {$archivedAccount->id}: " . $e->getMessage());
    }
}
```

## Configuration Requise

### Variables d'environnement
Ajoutez dans votre `.env` (et Render) :
```env
NEON_DATABASE_URL=postgresql://neondb_owner:npg_2OxIS1ifZUbJ@ep-dawn-dew-ad7nryvj-pooler.c-2.us-east-1.aws.neon.tech/neondb?sslmode=require&channel_binding=require
```

### Configuration base de données
Dans `config/database.php` :
```php
'neon' => [
    'driver' => 'pgsql',
    'url' => env('NEON_DATABASE_URL'),
    'host' => env('NEON_DB_HOST'),
    'port' => env('NEON_DB_PORT', '5432'),
    'database' => env('NEON_DB_DATABASE'),
    'username' => env('NEON_DB_USERNAME'),
    'password' => env('NEON_DB_PASSWORD'),
    'charset' => 'utf8',
    'prefix' => '',
    'prefix_indexes' => true,
    'search_path' => 'public',
    'sslmode' => 'require',
],
```

## Planification des Jobs

### Configuration dans `app/Console/Kernel.php`
```php
protected function schedule(Schedule $schedule): void
{
    // Archivage quotidien à 2h du matin
    $schedule->job(new \App\Jobs\ArchiveBlockedAccounts)->dailyAt('02:00');

    // Désarchivage horaire
    $schedule->job(new \App\Jobs\UnarchiveExpiredBlockedAccounts)->hourly();
}
```

### Commande Artisan
```php
// Dans app/Console/Commands/RunArchiveJobs.php
public function handle()
{
    $type = $this->option('type') ?? 'all';

    switch ($type) {
        case 'archive':
            ArchiveBlockedAccounts::dispatch();
            break;
        case 'unarchive':
            UnarchiveExpiredBlockedAccounts::dispatch();
            break;
        case 'all':
        default:
            ArchiveBlockedAccounts::dispatch();
            UnarchiveExpiredBlockedAccounts::dispatch();
            break;
    }
}
```

## Test du Système

### Préparation des données de test

#### 1. Créer un compte épargne
```bash
# Via API ou seeder
POST /api/v1/comptes
{
  "numero_compte": "TEST123456",
  "type": "epargne",
  "devise": "FCFA",
  "client_id": "uuid-du-client"
}
```

#### 2. Bloquer le compte
```bash
POST /api/v1/comptes/{compte-id}/bloquer
{
  "motif": "Test d'archivage",
  "duree": 1,
  "unite": "jours"
}
```

### Test de l'archivage

#### 1. Vérifier le statut du compte
```sql
-- Dans la base Render
SELECT id, statut, motif_blocage, date_blocage, date_deblocage_prevue
FROM comptes
WHERE id = 'votre-compte-id';
```

#### 2. Lancer l'archivage manuellement
```bash
php artisan archive:run --type=archive
```

#### 3. Vérifier que le compte a été déplacé
```sql
-- Dans la base Render (devrait être vide)
SELECT * FROM comptes WHERE id = 'votre-compte-id';

-- Dans la base Neon (devrait contenir le compte)
SELECT * FROM comptes WHERE id = 'votre-compte-id';
```

### Test du désarchivage

#### 1. Modifier manuellement la date d'expiration (pour test)
```sql
-- Dans Neon, mettre une date passée
UPDATE comptes
SET date_deblocage_prevue = NOW() - INTERVAL '1 hour'
WHERE id = 'votre-compte-id';
```

#### 2. Lancer le désarchivage manuellement
```bash
php artisan archive:run --type=unarchive
```

#### 3. Vérifier que le compte est revenu
```sql
-- Dans la base Render (devrait être présent avec statut 'actif')
SELECT id, statut FROM comptes WHERE id = 'votre-compte-id';

-- Dans la base Neon (devrait être vide)
SELECT * FROM comptes WHERE id = 'votre-compte-id';
```

## Logs et Monitoring

### Fichiers de logs
- `storage/logs/laravel.log` - Logs des jobs
- Logs Render pour les exécutions planifiées

### Messages de logs typiques
```
[2025-10-28 02:00:00] local.INFO: Starting ArchiveBlockedAccounts job
[2025-10-28 02:00:00] local.INFO: Found 3 blocked accounts to archive
[2025-10-28 02:00:01] local.INFO: Successfully moved account uuid-1 to Neon archive
[2025-10-28 02:00:01] local.INFO: Successfully moved account uuid-2 to Neon archive
[2025-10-28 02:00:01] local.INFO: Successfully moved account uuid-3 to Neon archive
[2025-10-28 02:00:01] local.INFO: ArchiveBlockedAccounts job completed successfully
```

## Dépannage

### Problèmes courants

#### 1. Connexion à Neon échoue
```bash
# Tester la connexion
php artisan tinker
DB::connection('neon')->getPdo();
```

#### 2. Jobs ne s'exécutent pas
```bash
# Vérifier le scheduler
php artisan schedule:list

# Lancer manuellement
php artisan schedule:run
```

#### 3. Erreurs de transactions
- Vérifier les logs détaillés
- S'assurer que les foreign keys sont gérées correctement
- Vérifier l'intégrité des données

#### 4. Données corrompues
```bash
# Nettoyer manuellement si nécessaire
php artisan tinker
DB::connection('neon')->table('comptes')->truncate();
```

## Sécurité et Performance

### Sécurité
- Utilise SSL pour la connexion Neon
- Transactions pour garantir l'intégrité
- Logs détaillés pour l'audit

### Performance
- Jobs asynchrones (Queue)
- Transactions optimisées
- Requêtes indexées sur les dates

## Maintenance

### Sauvegardes
- Sauvegardez régulièrement la base Neon
- Gardez des logs d'archivage pour l'audit

### Monitoring
- Surveillez les logs d'erreurs
- Vérifiez régulièrement que les jobs s'exécutent
- Monitorer l'espace disque des deux bases

## Conclusion

Ce système d'archivage garantit que :
1. Les comptes bloqués sont automatiquement déplacés vers l'archive
2. Les comptes expirés reviennent automatiquement en base active
3. Aucune duplication de données
4. Intégrité des données préservée
5. Traçabilité complète via les logs

Le système est entièrement automatisé et nécessite une intervention minimale une fois configuré.
