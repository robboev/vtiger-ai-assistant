#!/bin/bash
# Build AIAssistant module package (vtiger-compatible zip)
# Same pattern as RTLTheme: files at root level in zip
set -e

VERSION=$(grep '<version>' module-src/manifest.xml | sed 's/.*<version>\(.*\)<\/version>.*/\1/')
OUTPUT="/tmp/AIAssistant-v${VERSION}.zip"
SRC="module-src"

if [ ! -d "$SRC" ]; then
    echo "ERROR: $SRC directory not found"
    exit 1
fi

rm -f "$OUTPUT"

cd "$SRC"
zip -r "$OUTPUT" . -x '*.git*' -x '*.DS_Store' -x 'modules/AIAssistant/config_ai.php'
cd ..

echo "Built: $OUTPUT"
ls -lh "$OUTPUT"
echo ""
echo "Install via: vtiger Settings > Module Manager > Import Module"
