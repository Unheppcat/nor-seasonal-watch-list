#!/usr/bin/env bash

set -euo pipefail

script_dir=$( cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd )
cd "$script_dir/.."

source_dir=vendor/twbs/bootstrap/dist
target_dir=public/bootstrap

# Clean everything out
rm -rf "$target_dir"

# Copy JavaScript file
mkdir -p "$target_dir/js"
for source_filename in bootstrap.bundle.min.js bootstrap.bundle.min.js.map; do
    source_path="$source_dir/js/$source_filename"
    cp "$source_path" "$target_dir/js/"
done

# Build CSS files
mkdir -p "$target_dir/css"
for source_path in $(ls assets/bootstrap/*.scss); do
    filename=${source_path##*/}
    basename=${filename%.*} # strip extension
    # skip partials
    if [[ $basename == _* ]]; then
        continue
    fi
    npx sass --style expanded $source_path "$target_dir/css/$basename.css"
    npx sass --style compressed $source_path "$target_dir/css/$basename.min.css"
done
