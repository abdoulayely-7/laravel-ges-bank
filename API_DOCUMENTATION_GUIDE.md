# Guide Complet pour Documenter une API GET avec Swagger dans Laravel

## Introduction
Ce guide explique étape par étape comment documenter votre API GET `/comptes` en utilisant Swagger (L5-Swagger) dans un projet Laravel. Même un débutant pourra suivre ces instructions facilement.

## Prérequis
- Un projet Laravel fonctionnel
- L5-Swagger installé (`composer require darkaonline/l5-swagger`)
- Une route API existante (GET /api/v1/comptes)
- Un contrôleur avec la méthode index
- Une ressource API (optionnel mais recommandé)

## Étape 1 : Installation de L5-Swagger
Si ce n'est pas déjà fait, installez L5-Swagger :

```bash
composer require darkaonline/l5-swagger
```

Puis publiez la configuration :
```bash
php artisan vendor:publish --provider "L5Swagger\L5SwaggerServiceProvider"
```

## Étape 2 : Configuration de base
Vérifiez que votre fichier `config/l5-swagger.php` contient les bonnes configurations :

```php
'annotations' => [
    base_path('app'),
],
```

Cela permet à Swagger de scanner vos fichiers dans le dossier `app/` pour trouver les annotations.

## Étape 3 : Documenter le contrôleur
Ouvrez votre contrôleur (par exemple `app/Http/Controllers/CompteController.php`) et ajoutez les annotations Swagger en haut du fichier.

### 3.1 Ajouter les informations générales de l'API
Au début du fichier, après le namespace, ajoutez :

```php
/**
 * @OA\Info(
 *     title="API de Gestion Bancaire",
 *     version="1.0.0",
 *     description="API pour la gestion des comptes bancaires"
 * )
 *
 * @OA\Server(
 *     url="http://localhost:8000/api/v1",
 *     description="Serveur de développement"
 * )
 */
class CompteController extends Controller
```

### 3.2 Documenter la méthode GET
Avant votre méthode `index()`, ajoutez une annotation détaillée :

```php
/**
 * @OA\Get(
 *     path="/comptes",
 *     summary="Récupérer la liste des comptes",
 *     description="Retourne une liste paginée des comptes avec possibilité de filtrage et tri",
 *     operationId="getComptes",
 *     tags={"Comptes"},
 *     // Ici on définit les paramètres de requête
 *     @OA\Parameter(
 *         name="page",
 *         in="query",
 *         description="Numéro de la page",
 *         required=false,
 *         @OA\Schema(type="integer", default=1)
 *     ),
 *     // Ajoutez d'autres paramètres selon vos besoins...
 *     @OA\Response(
 *         response=200,
 *         description="Liste des comptes récupérée avec succès",
 *         @OA\JsonContent(
 *             @OA\Property(property="success", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Liste des comptes récupérée avec succès"),
 *             @OA\Property(property="data", type="object", ...)
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Paramètres invalides",
 *         @OA\JsonContent(...)
 *     ),
 *     @OA\Response(
 *         response=500,
 *         description="Erreur serveur",
 *         @OA\JsonContent(...)
 *     )
 * )
 */
public function index(CompteIndexRequest $request)
{
    // Votre code existant...
}
```

## Étape 4 : Documenter la ressource API (optionnel mais recommandé)
Si vous utilisez une ressource API, documentez-la aussi :

Ouvrez `app/Http/Resources/CompteResource.php` et ajoutez :

```php
/**
 * @OA\Schema(
 *     schema="CompteResource",
 *     title="Compte Resource",
 *     description="Représentation d'un compte bancaire",
 *     @OA\Property(property="id", type="string", format="uuid", description="Identifiant unique du compte"),
 *     @OA\Property(property="numeroCompte", type="string", description="Numéro du compte"),
 *     // Ajoutez toutes les propriétés...
 * )
 */
class CompteResource extends JsonResource
{
    // Votre code existant...
}
```

## Étape 5 : Générer la documentation
Une fois les annotations ajoutées, générez la documentation :

```bash
php artisan l5-swagger:generate
```

Cette commande va :
1. Scanner vos fichiers pour trouver les annotations `@OA\*`
2. Générer un fichier JSON (`storage/api-docs/api-docs.json`)
3. Créer l'interface Swagger UI

## Étape 6 : Accéder à la documentation
Ouvrez votre navigateur et allez à l'URL configurée (généralement) :
```
cl
```

Vous devriez voir l'interface Swagger avec :
- La liste de vos endpoints
- Les paramètres disponibles
- Les exemples de réponses
- Un bouton "Try it out" pour tester l'API

## Étape 7 : Tester l'API
Dans l'interface Swagger :
1. Cliquez sur l'endpoint GET /comptes
2. Cliquez sur "Try it out"
3. Remplissez les paramètres si nécessaire
4. Cliquez sur "Execute"
5. Vérifiez la réponse dans la section "Responses"

## Dépannage courant

### Erreur "$ref not found"
Si vous avez une erreur comme `$ref "#/components/schemas/Pagination" not found`, cela signifie que vous référencez un schéma qui n'existe pas. Solution :
- Soit définir le schéma directement dans la réponse
- Soit créer le schéma séparément avec `@OA\Schema`

### Documentation ne se met pas à jour
Si vos changements n'apparaissent pas :
1. Videz le cache : `php artisan config:clear`
2. Régénérez : `php artisan l5-swagger:generate`

### Interface Swagger ne s'affiche pas
Vérifiez :
1. Que le serveur Laravel tourne : `php artisan serve`
2. Que la route est bien définie dans `routes/api.php`
3. Que les permissions sur `storage/api-docs/` sont correctes

## Exemple complet d'annotations

Voici un exemple complet pour un contrôleur simple :

```php
<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompteIndexRequest;
use App\Services\CompteService;
use App\Traits\ApiResponseTrait;

/**
 * @OA\Info(
 *     title="API de Gestion Bancaire",
 *     version="1.0.0",
 *     description="API pour la gestion des comptes bancaires"
 * )
 *
 * @OA\Server(
 *     url="http://localhost:8000/api/v1",
 *     description="Serveur de développement"
 * )
 */
class CompteController extends Controller
{
    use ApiResponseTrait;

    /**
     * @OA\Get(
     *     path="/comptes",
     *     summary="Récupérer la liste des comptes",
     *     description="Retourne une liste paginée des comptes",
     *     operationId="getComptes",
     *     tags={"Comptes"},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Numéro de la page",
     *         required=false,
     *         @OA\Schema(type="integer", default=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Liste récupérée"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     )
     * )
     */
    public function index(CompteIndexRequest $request)
    {
        try {
            $data = $this->compteService->rechercherEtPaginer($request->validated());
            return $this->success($data, 'Liste des comptes récupérée avec succès');
        } catch (\Throwable $e) {
            return $this->error("Erreur serveur : " . $e->getMessage(), 500);
        }
    }
}
```

## Conclusion
Suivez ces étapes dans l'ordre et votre API sera parfaitement documentée avec Swagger. N'oubliez pas de régénérer la documentation après chaque modification des annotations avec `php artisan l5-swagger:generate`.
