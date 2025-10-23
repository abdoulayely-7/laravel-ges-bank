#!/bin/bash

# Script de build pour Render
echo "Building Docker image..."
docker build -t ges-bank .

echo "Running migrations..."
docker run --rm \
  -e APP_ENV=production \
  -e APP_KEY=$APP_KEY \
  -e DB_CONNECTION=pgsql \
  -e DB_HOST=$DB_HOST \
  -e DB_PORT=$DB_PORT \
  -e DB_DATABASE=$DB_DATABASE \
  -e DB_USERNAME=$DB_USERNAME \
  -e DB_PASSWORD=$DB_PASSWORD \
  ges-bank \
  php artisan migrate --force

echo "Generating Swagger documentation..."
docker run --rm \
  -e APP_ENV=production \
  -e APP_KEY=$APP_KEY \
  ges-bank \
  php artisan l5-swagger:generate

echo "Build completed successfully!"
