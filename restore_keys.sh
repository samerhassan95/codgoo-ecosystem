#!/bin/bash

# JWT Keys Restoration Script
# Usage: ./restore_keys.sh <backup_directory>

if [ $# -eq 0 ]; then
    echo "❌ Error: Please provide backup directory path"
    echo "Usage: ./restore_keys.sh <backup_directory>"
    echo "Example: ./restore_keys.sh storage/keys_backup_20240402_141500"
    exit 1
fi

BACKUP_DIR="$1"
KEYS_DIR="storage/keys"

if [ ! -d "$BACKUP_DIR" ]; then
    echo "❌ Error: Backup directory '$BACKUP_DIR' does not exist"
    exit 1
fi

echo "🔐 Restoring JWT keys from backup..."

# Create keys directory if it doesn't exist
mkdir -p "$KEYS_DIR"

# Restore keys
if [ -f "$BACKUP_DIR/jwt_private.pem" ]; then
    cp "$BACKUP_DIR/jwt_private.pem" "$KEYS_DIR/"
    chmod 600 "$KEYS_DIR/jwt_private.pem"
    echo "✅ Restored jwt_private.pem"
fi

if [ -f "$BACKUP_DIR/jwt_public.pem" ]; then
    cp "$BACKUP_DIR/jwt_public.pem" "$KEYS_DIR/"
    chmod 600 "$KEYS_DIR/jwt_public.pem"
    echo "✅ Restored jwt_public.pem"
fi

if [ -f "$BACKUP_DIR/marketplace_private.pem" ]; then
    cp "$BACKUP_DIR/marketplace_private.pem" "$KEYS_DIR/"
    chmod 600 "$KEYS_DIR/marketplace_private.pem"
    echo "✅ Restored marketplace_private.pem"
fi

echo "🎉 Keys restoration completed!"
echo ""
echo "🔧 Next steps:"
echo "1. Clear configuration cache: php artisan config:clear"
echo "2. Test authentication endpoints"
echo "3. Check application logs for any issues"