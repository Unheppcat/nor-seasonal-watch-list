#!/bin/bash

# Install git hooks from bin/hooks/ to .git/hooks/
# This script should be run once after cloning the repository

SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PROJECT_ROOT="$(dirname "$SCRIPT_DIR")"

# Find the .git directory (could be in current dir or parent)
GIT_DIR="$PROJECT_ROOT/.git"
if [ ! -d "$GIT_DIR" ]; then
    GIT_DIR="$(dirname "$PROJECT_ROOT")/.git"
fi

HOOKS_DIR="$GIT_DIR/hooks"

if [ ! -d "$HOOKS_DIR" ]; then
    echo "Error: .git/hooks directory not found at $HOOKS_DIR"
    echo "Are you in a git repository?"
    exit 1
fi

echo "Installing git hooks..."

# Install pre-commit hook
if [ -f "$SCRIPT_DIR/hooks/pre-commit" ]; then
    cp "$SCRIPT_DIR/hooks/pre-commit" "$HOOKS_DIR/pre-commit"
    chmod +x "$HOOKS_DIR/pre-commit"
    echo "✓ Installed pre-commit hook (auto-bumps asset_version when assets change)"
else
    echo "✗ pre-commit hook not found at $SCRIPT_DIR/hooks/pre-commit"
fi

echo ""
echo "Git hooks installation complete!"
echo ""
echo "The pre-commit hook will automatically increment asset_version in"
echo "config/packages/version.yaml whenever you commit changes to files in:"
echo "  - public/css/"
echo "  - public/img/"
echo "  - public/js/"
