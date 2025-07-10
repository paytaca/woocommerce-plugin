#!/bin/bash

# Name of the output zip file
ZIP_FILE="woocommerce-plugin-paytaca.zip"

# Name of the directory to compress
SOURCE_DIR="."

# Zip command
zip -r "$ZIP_FILE" "$SOURCE_DIR" \
  -x "*.git*" \
  -x "*node_modules*" \
  -x "*tests*" \
  -x "*.DS_Store*" \
  -x "__MACOSX/*"
