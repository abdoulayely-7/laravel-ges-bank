#!/bin/bash

# Script de build pour Render
echo "Building Docker image..."
docker build -t ges-bank .

echo "Running migrations..."
docker run --rm \
  -e APP_ENV=production \
  -e APP_KEY=$APP_KEY \
  -e DATABASE_URL=$DATABASE_URL \
  ges-bank \
  php artisan migrate --force

echo "Generating Swagger documentation..."
docker run --rm \
  -e APP_ENV=production \
  -e APP_KEY=$APP_KEY \
  ges-bank \
  php artisan l5-swagger:generate

echo "Build completed successfully!"
