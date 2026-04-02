#!/bin/bash

# JWT Keys Backup Script
# Run this script to backup your JWT keys before major updates

BACKUP_DIR="storage/keys_backup_$(date +%Y%m%d_%H%M%S)"
KEYS_DIR="storage/keys"

echo "🔐 Creating JWT keys backup..."

# Create backup directory
mkdir -p "$BACKUP_DIR"

# Copy keys if they exist
if [ -f "$KEYS_DIR/jwt_private.pem" ]; then
    cp "$KEYS_DIR/jwt_private.pem" "$BACKUP_DIR/"
    echo "✅ Backed up jwt_private.pem"
fi

if [ -f "$KEYS_DIR/jwt_public.pem" ]; then
    cp "$KEYS_DIR/jwt_public.pem" "$BACKUP_DIR/"
    echo "✅ Backed up jwt_public.pem"
fi

if [ -f "$KEYS_DIR/marketplace_private.pem" ]; then
    cp "$KEYS_DIR/marketplace_private.pem" "$BACKUP_DIR/"
    echo "✅ Backed up marketplace_private.pem"
fi

# Set secure permissions
chmod 600 "$BACKUP_DIR"/*.pem 2>/dev/null

echo "🎉 Backup completed in: $BACKUP_DIR"
echo ""
echo "⚠️  IMPORTANT:"
echo "- Store this backup in a secure location"
echo "- Never commit these keys to version control"
echo "- Keep backups encrypted if storing remotely"