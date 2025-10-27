#!/bin/bash

# Script de build pour Render
echo "Building Docker image..."
docker build -t ges-bank .

echo "Running migrations..."
docker run --rm \
  -e APP_ENV=production \
  -e APP_KEY=$APP_KEY \
  -e DB_CONNECTION=pgsql \
  -e DB_HOST=dpg-d3v8l6ali9vc73ckvc00-a.oregon-postgres.render.com \
  -e DB_PORT=5432 \
  -e DB_DATABASE=bank_laravel_api \
  -e DB_USERNAME=bank_laravel_api_user \
  -e DB_PASSWORD=zNk24j1UwvkG8eM9P4EyUJ8lBj2vzczX \
  ges-bank \
  php artisan migrate --force

echo "Generating Swagger documentation..."
docker run --rm \
  -e APP_ENV=production \
  -e APP_KEY=$APP_KEY \
  ges-bank \
  php artisan l5-swagger:generate

echo "Build completed successfully!"
