#!/bin/bash

# Rollback script for GPT-5 mini enablement

# Define the previous version to rollback to
PREVIOUS_VERSION=""

# Check if the previous version is provided
if [ -z "$PREVIOUS_VERSION" ]; then
  echo "No previous version specified. Please provide a version to rollback to."
  exit 1
fi

# Stop the current application
echo "Stopping the current application..."
# Command to stop the application (e.g., systemctl, pm2, etc.)
# systemctl stop gpt5-mini-enablement

# Checkout the previous version
echo "Rolling back to version $PREVIOUS_VERSION..."
# Command to checkout the previous version (e.g., git checkout)
# git checkout $PREVIOUS_VERSION

# Start the application
echo "Starting the application..."
# Command to start the application (e.g., systemctl, pm2, etc.)
# systemctl start gpt5-mini-enablement

echo "Rollback to version $PREVIOUS_VERSION completed successfully."