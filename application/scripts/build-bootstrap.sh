#!/usr/bin/env bash

set -euo pipefail

script_dir=$( cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )
cd "$script_dir/.."

source_dir=vendor/twbs/bootstrap/dist
target_dir=public/bootstrap

# Clean everything out
rm -rf "$target_dir"

# Copy JavaScript files
mkdir -p "$target_dir/js"
for source_filename in bootstrap.bundle.min.js bootstrap.bundle.min.js.map; do
    source_path="$source_dir/js/$source_filename"
    cp "$source_path" "$target_dir/js/"
done

# Copy Bootstrap CSS files and rename with color mode suffixes
mkdir -p "$target_dir/css"

# Copy as bootstrap-color-mode-data (data attribute mode is Bootstrap 5.3 default)
cp "$source_dir/css/bootstrap.css" "$target_dir/css/bootstrap-color-mode-data.css"
cp "$source_dir/css/bootstrap.min.css" "$target_dir/css/bootstrap-color-mode-data.min.css"

# Copy as bootstrap-color-mode-media-query (same file, different name for compatibility)
cp "$source_dir/css/bootstrap.css" "$target_dir/css/bootstrap-color-mode-media-query.css"
cp "$source_dir/css/bootstrap.min.css" "$target_dir/css/bootstrap-color-mode-media-query.min.css"

echo "Bootstrap files copied successfully from $source_dir to $target_dir"
