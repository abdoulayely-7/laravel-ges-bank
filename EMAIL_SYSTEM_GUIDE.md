# Guide Complet du Système d'Envoi d'Emails dans Laravel

## Vue d'ensemble
Ce guide détaille étape par étape comment implémenter un système complet d'envoi d'emails dans Laravel en utilisant les Observers, Events et Listeners. Le système envoie automatiquement un email de confirmation lors de la création d'un compte bancaire.

## Prérequis
- Laravel installé et configuré
- Base de données configurée
- Modèles `User`, `Client`, `Compte` créés
- Relations Eloquent configurées

## Étape 1 : Création de l'Event

### 1.1 Générer l'Event
```bash
php artisan make:event CompteCreated
```

### 1.2 Configuration de l'Event
**Fichier : `app/Events/CompteCreated.php`**

```php
<?php

namespace App\Events;

use App\Models\Compte;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CompteCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $compte;

    /**
     * Create a new event instance.
     */
    public function __construct(Compte $compte)
    {
        $this->compte = $compte;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
```

## Étape 2 : Création du Listener

### 2.1 Générer le Listener
```bash
php artisan make:listener SendClientNotification --event=CompteCreated
```

### 2.2 Configuration du Listener
**Fichier : `app/Listeners/SendClientNotification.php`**

```php
<?php

namespace App\Listeners;

use App\Events\CompteCreated;
use App\Mail\CompteCreeMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendClientNotification
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(CompteCreated $event): void
    {
        $compte = $event->compte;

        // Charger les relations nécessaires
        $compte->load('client.user');

        // Vérifier que l'email existe
        if (!$compte->client || !$compte->client->user || !$compte->client->user->email) {
            Log::warning('Impossible d\'envoyer l\'email de création de compte : email manquant', [
                'compte_id' => $compte->id,
                'client_id' => $compte->client_id ?? null,
                'user_id' => $compte->client->user_id ?? null,
            ]);
            return;
        }

        try {
            // Envoi du mail
            Mail::to($compte->client->user->email)->send(new CompteCreeMail($compte));

            Log::info('Email de création de compte envoyé avec succès', [
                'compte_id' => $compte->id,
                'email' => $compte->client->user->email,
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur lors de l\'envoi de l\'email de création de compte', [
                'compte_id' => $compte->id,
                'email' => $compte->client->user->email,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
```

## Étape 3 : Création de l'Observer

### 3.1 Générer l'Observer
```bash
php artisan make:observer CompteObserver --model=Compte
```

### 3.2 Configuration de l'Observer
**Fichier : `app/Observers/CompteObserver.php`**

```php
<?php

namespace App\Observers;

use App\Events\CompteCreated;
use App\Models\Compte;

class CompteObserver
{
    /**
     * Handle the Compte "created" event.
     */
    public function created(Compte $compte): void
    {
        event(new CompteCreated($compte));
    }

    /**
     * Handle the Compte "updated" event.
     */
    public function updated(Compte $compte): void
    {
        //
    }

    /**
     * Handle the Compte "deleted" event.
     */
    public function deleted(Compte $compte): void
    {
        //
    }

    /**
     * Handle the Compte "restored" event.
     */
    public function restored(Compte $compte): void
    {
        //
    }

    /**
     * Handle the Compte "force deleted" event.
     */
    public function forceDeleted(Compte $compte): void
    {
        //
    }
}
```

## Étape 4 : Enregistrement de l'Observer

### 4.1 Modification du AppServiceProvider
**Fichier : `app/Providers/AppServiceProvider.php`**

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Enregistrer l'observer pour le modèle Compte
        \App\Models\Compte::observe(\App\Observers\CompteObserver::class);
    }
}
```

## Étape 5 : Configuration des Events et Listeners

### 5.1 Modification du EventServiceProvider
**Fichier : `app/Providers/EventServiceProvider.php`**

```php
<?php

namespace App\Providers;

use App\Events\CompteCreated;
use App\Listeners\SendClientNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        CompteCreated::class => [
            SendClientNotification::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
```

## Étape 6 : Création de la classe Mail

### 6.1 Générer la classe Mail
```bash
php artisan make:mail CompteCreeMail
```

### 6.2 Configuration de la classe Mail
**Fichier : `app/Mail/CompteCreeMail.php`**

```php
<?php

namespace App\Mail;

use App\Models\Compte;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CompteCreeMail extends Mailable
{
    use Queueable, SerializesModels;

    public Compte $compte;

    /**
     * Create a new message instance.
     */
    public function __construct(Compte $compte)
    {
        $this->compte = $compte;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Votre compte a été créé avec succès'
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.compte.cree',
            with: [
                'compte' => $this->compte,
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
```

## Étape 7 : Création du Template d'Email

### 7.1 Créer le répertoire
```bash
mkdir -p resources/views/emails/compte
```

### 7.2 Créer le template Markdown
**Fichier : `resources/views/emails/compte/cree.blade.php`**

```blade
<x-mail::message>
    # Félicitations {{ $compte->client->user->name }} 🎉

    Votre compte a été créé avec succès.

    **Numéro du compte :** {{ $compte->numero_compte }}
    **Type de compte :** {{ ucfirst($compte->type) }}
    **Devise :** {{ $compte->devise }}
    **Solde :** {{ $compte->solde }}

    {{-- <x-mail::button :url="route('comptes.show', $compte->id)">
        Consulter mon compte
    </x-mail::button> --}}

    Merci pour votre confiance,<br>
    {{ config('app.name') }}
</x-mail::message>
```

## Étape 8 : Configuration du Mail

### 8.1 Configuration dans .env
**Fichier : `.env`**

```env
MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-email@domain.com
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@yourdomain.com"
MAIL_FROM_NAME="${APP_NAME}"
```

### 8.2 Configuration pour les tests locaux
Pour les tests en développement, vous pouvez utiliser :

```env
MAIL_MAILER=log
```

Cela enregistrera les emails dans `storage/logs/laravel.log` au lieu de les envoyer.

## Étape 9 : Test du système

### 9.1 Créer un compte via l'API
```bash
curl -X POST "http://localhost:8000/api/v1/comptes" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "type": "cheque",
    "soldeInitial": 500000,
    "devise": "FCFA",
    "client": {
      "titulaire": "Test User",
      "email": "test@example.com",
      "telephone": "771234567",
      "nci": "1234567890123",
      "adresse": "Test Address"
    }
  }'
```

### 9.2 Vérifier les logs
```bash
tail -f storage/logs/laravel.log | grep -i "email\|mail"
```

### 9.3 Vérifier que l'observer fonctionne
```bash
php artisan tinker --execute="echo 'Observer: ' . (\App\Models\Compte::getEventDispatcher() ? 'OK' : 'NOK');"
```

## Étape 10 : Dépannage

### 10.1 Email non envoyé
**Vérifications :**
- Configuration MAIL_* dans `.env`
- Relations Eloquent chargées dans le listener
- Template d'email existe
- Logs Laravel pour les erreurs

### 10.2 Observer non déclenché
**Vérifications :**
- Observer enregistré dans `AppServiceProvider`
- Cache vidé : `php artisan config:clear`
- Modèle utilise `HasFactory` et `SoftDeletes`

### 10.3 Event non dispatché
**Vérifications :**
- Event importé dans l'observer
- Listener enregistré dans `EventServiceProvider`
- Queue worker si listener en file d'attente

## Étape 11 : Optimisations

### 11.1 Mettre le listener en file d'attente
Pour améliorer les performances, modifiez le listener :

```php
class SendClientNotification implements ShouldQueue
{
    use InteractsWithQueue;

    // ... reste du code
}
```

### 11.2 Configuration de la queue
```bash
php artisan queue:table
php artisan migrate
php artisan queue:work
```

### 11.3 Templates d'emails multiples
Créez différents templates pour différents types de comptes :

```php
// Dans CompteCreeMail
public function content(): Content
{
    $template = match($this->compte->type) {
        'cheque' => 'emails.compte.cheque',
        'epargne' => 'emails.compte.epargne',
        default => 'emails.compte.cree'
    };

    return new Content(
        markdown: $template,
        with: ['compte' => $this->compte]
    );
}
```

## Flux complet du système

1. **Création du compte** : `CompteService::creerCompte()`
2. **Observer déclenché** : `CompteObserver::created()`
3. **Event dispatché** : `event(new CompteCreated($compte))`
4. **Listener exécuté** : `SendClientNotification::handle()`
5. **Email envoyé** : `Mail::to()->send(new CompteCreeMail())`
6. **Logs enregistrés** : Succès ou erreur dans les logs

## Commandes importantes

```bash
# Créer les fichiers
php artisan make:event CompteCreated
php artisan make:listener SendClientNotification --event=CompteCreated
php artisan make:observer CompteObserver --model=Compte
php artisan make:mail CompteCreeMail

# Configuration
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# Tests
php artisan tinker
php artisan queue:work  # Si listener en queue
```

## Structure des fichiers créés

```
app/
├── Events/
│   └── CompteCreated.php
├── Listeners/
│   └── SendClientNotification.php
├── Mail/
│   └── CompteCreeMail.php
├── Observers/
│   └── CompteObserver.php
└── Providers/
    ├── AppServiceProvider.php (modifié)
    └── EventServiceProvider.php (modifié)

resources/views/emails/compte/
└── cree.blade.php
```

Ce système garantit que chaque création de compte déclenche automatiquement l'envoi d'un email de confirmation au client, avec une gestion robuste des erreurs et du logging.
