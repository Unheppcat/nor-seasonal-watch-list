# Git Hooks

This directory contains git hooks that can be installed to automate project tasks.

## Installation

Run the installation script from the project root:

```bash
./bin/install-git-hooks.sh
```

This will copy all hooks from `bin/hooks/` to `.git/hooks/` and make them executable.

**Note:** Git hooks are local to each developer's machine and are not tracked by git. After cloning the repository, each developer should run the installation script.

## Available Hooks

### pre-commit

**Purpose:** Automatically increments the `asset_version` parameter in `config/packages/version.yaml` whenever asset files are modified.

**Trigger:** Runs before each commit is created.

**Behavior:**
- Checks if any files in the following directories are being committed:
  - `application/public/css/`
  - `application/public/img/`
  - `application/public/js/`
- If asset files are detected:
  - Reads the current `asset_version` value
  - Increments it by 1
  - Updates `config/packages/version.yaml`
  - Adds the updated version file to the commit

**Example:**
```bash
# Make a change to a CSS file
echo "/* new style */" >> public/css/layout.css

# Stage and commit
git add public/css/layout.css
git commit -m "Update styles"

# Hook automatically runs and outputs:
# ✓ Asset version bumped: 46 → 47
#   (Files in public/css, public/img, or public/js were modified)
```

**Why This Matters:**
The `asset_version` is appended to static asset URLs (CSS, JS files) as a query parameter to bust browser caches. When the version changes, browsers will fetch fresh copies of the assets instead of using cached versions.

Before this hook, developers had to manually remember to increment the version number whenever they modified assets.

## Uninstalling Hooks

To disable a hook, simply delete it from `.git/hooks/`:

```bash
rm .git/hooks/pre-commit
```

To reinstall, run `./bin/install-git-hooks.sh` again.

## Customizing Hooks

The hook scripts are stored in `bin/hooks/` (tracked by git) and copied to `.git/hooks/` (not tracked).

To modify a hook:
1. Edit the file in `bin/hooks/`
2. Run `./bin/install-git-hooks.sh` to reinstall
3. Test by staging relevant files and running: `.git/hooks/<hook-name>`
