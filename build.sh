#!/bin/bash
# Build vtiger-ai-assistant module zip

set -e

VERSION=$(grep '<version>' module-src/manifest.xml | sed 's/.*<version>\(.*\)<\/version>.*/\1/')
OUTPUT="vtiger-ai-assistant-v${VERSION}.zip"
BUILDDIR="/tmp/ai-assistant-build"

echo "Building AIAssistant v${VERSION}..."

# Clean
rm -rf "$BUILDDIR"
mkdir -p "$BUILDDIR/AIAssistant"

# Copy module files
cp module-src/manifest.xml "$BUILDDIR/AIAssistant/"
cp -r module-src/modules "$BUILDDIR/AIAssistant/"
cp -r module-src/layouts "$BUILDDIR/AIAssistant/"

# Remove config files that shouldn't be in the zip
rm -f "$BUILDDIR/AIAssistant/modules/AIAssistant/config_ai.php"

# Build zip
cd "$BUILDDIR"
zip -r "/tmp/$OUTPUT" AIAssistant/ -x "*.DS_Store" "*__MACOSX*"

# Clean
rm -rf "$BUILDDIR"

echo "Built: /tmp/$OUTPUT"
echo "Install via vtiger Settings > Module Manager > Import Module"
