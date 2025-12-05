#!/bin/bash

# This script deploys the GPT-5 mini enablement application to the production environment.

set -e

echo "Starting deployment..."

# Pull the latest code from the repository
git pull origin main

# Install dependencies
npm install --production

# Build the application
npm run build

# Run migrations if necessary
# Uncomment the following line if you have a migration step
# npm run migrate

# Start the application
npm start

echo "Deployment completed successfully."