#!/bin/bash
#ddev-generated
# If you want to take over this file and customize it, remove the line above
# and ddev will respect it and won't overwrite the file.

# Verbose flag — set VERBOSE=1 (or pass -v/--verbose to `ddev install`) to
# disable the spinner and stream all command output live.
VERBOSE="${VERBOSE:-0}"

# Repo-owned customizations (composer packages, TYPO3 settings, ...) that
# survive `ddev add-on get` upgrades. See .setup/project.sh.example.
if [ -f "/var/www/html/.ddev/.setup/project.sh" ]; then
    source /var/www/html/.ddev/.setup/project.sh
fi

# Internal state shared between _progress, _done and _progress_exit_trap.
SPINNER_PID=0
PROGRESS_ACTIVE=0
PROGRESS_LOG=""
PROGRESS_LABEL=""

# Function to display a spinner at the current cursor position (simple colored style)
function _spinner() {
    local chars="|/-\\"
    local delay=0.1
    local i=0

    while true; do
        printf "\b\e[36m%c\e[0m" "${chars:$i:1}"
        ((i++))
        if [ $i -eq ${#chars} ]; then
            i=0
        fi
        sleep $delay
    done
}

function _progress() {
    PROGRESS_LABEL="$1"
    printf "%s... " "$1"
    # Spinner mode only in interactive terminals outside CI and verbose runs.
    if [[ "$VERBOSE" -eq 0 ]] && [[ -z "$CI" ]] && [[ -z "$GITHUB_ACTIONS" ]] && [[ -z "$GITLAB_CI" ]] && [[ -z "$JENKINS_URL" ]] && [[ -t 1 ]]; then
      PROGRESS_LOG="$(mktemp)"
      printf " "
      _spinner &
      SPINNER_PID=$!
      # Save stdout/stderr, capture command output to a logfile so we can
      # replay it on failure instead of swallowing it into /dev/null.
      exec 3>&1 4>&2
      exec >"$PROGRESS_LOG" 2>&1
      PROGRESS_ACTIVE=1
    else
      PROGRESS_LOG=""
      printf "\n"
    fi
}

# Stop the spinner (if running) and restore the original stdout/stderr file
# descriptors. Idempotent — safe to call when no progress region is active.
function _stop_spinner() {
    [ $PROGRESS_ACTIVE -eq 1 ] || return 0
    kill $SPINNER_PID 2>/dev/null || true
    wait $SPINNER_PID 2>/dev/null || true
    SPINNER_PID=0
    exec 1>&3 2>&4
    PROGRESS_ACTIVE=0
}

# Replay the captured progress log to the user's terminal, then remove it.
# No-op when there is no logfile (verbose / non-interactive mode).
function _replay_progress_log() {
    [ -n "$PROGRESS_LOG" ] && [ -f "$PROGRESS_LOG" ] || return 0
    message red "--- Captured output (${PROGRESS_LABEL# }) ---"
    cat "$PROGRESS_LOG"
    message red "--- End of captured output ---"
    rm -f "$PROGRESS_LOG"
    PROGRESS_LOG=""
}

# Discard the captured progress log without replaying it (success path).
function _clear_progress_log() {
    [ -n "$PROGRESS_LOG" ] && [ -f "$PROGRESS_LOG" ] || return 0
    rm -f "$PROGRESS_LOG"
    PROGRESS_LOG=""
}

function _done() {
    local rc=$?
    local had_spinner=$PROGRESS_ACTIVE
    _stop_spinner

    if [ $rc -ne 0 ]; then
      if [ $had_spinner -eq 1 ]; then
        printf "\b\e[0m\e[31m✘\e[39m\n"
      else
        printf "\e[31m✘\e[39m\n"
      fi
      _replay_progress_log
      exit $rc
    fi

    if [ $had_spinner -eq 1 ]; then
      printf "\b\e[0m\e[32m✔\e[39m\n"
    else
      printf "\e[32m✔\e[39m\n"
    fi
    _clear_progress_log
}

# EXIT trap: surfaces errors from `set -e` aborts mid-block. Stops any running
# spinner, restores file descriptors, and dumps the captured log so the user
# sees the actual command output that failed.
function _progress_exit_trap() {
    [ $PROGRESS_ACTIVE -eq 1 ] || return 0
    _stop_spinner
    printf "\b\e[0m\e[31m✘\e[39m\n"
    _replay_progress_log
}
trap _progress_exit_trap EXIT


# Function to get the lowest supported TYPO3 version from the environment variable TYPO3_VERSIONS
# It reads the TYPO3_VERSIONS environment variable, splits it into an array, and sorts the versions.
# If no versions are found, it prints an error message and exits with status 1.
# Otherwise, it prints the lowest version.
function get_lowest_supported_typo3_versions() {
    local TYPO3_VERSIONS_ARRAY=()
    IFS=' ' read -r -a TYPO3_VERSIONS_ARRAY <<< "$TYPO3_VERSIONS"
    if [ ${#TYPO3_VERSIONS_ARRAY[@]} -eq 0 ]; then
        message red "Error! No supported TYPO3 versions found in environment variables."
        exit 1
    fi
    printf "%s\n" "${TYPO3_VERSIONS_ARRAY[@]}" | sort -V | head -n 1
}

# Function to get the supported TYPO3 versions from the environment variable TYPO3_VERSIONS.
# It checks if the TYPO3_VERSIONS environment variable is set and not empty.
# If the variable is unset or empty, it prints an error message and returns 1.
# Otherwise, it splits the TYPO3_VERSIONS variable into an array and prints the supported versions.
function get_supported_typo3_versions() {
    if [ -z "${TYPO3_VERSIONS+x}" ]; then
        message red "TYPO3_VERSIONS is unset. Please set it before running this function."
        return 1
    else
        local TYPO3_VERSIONS_ARRAY=()
        IFS=' ' read -r -a TYPO3_VERSIONS_ARRAY <<< "$TYPO3_VERSIONS"
        if [ ${#TYPO3_VERSIONS_ARRAY[@]} -eq 0 ]; then
            message red "Error! No supported TYPO3 versions found in environment variables."
            return 1
        fi
        printf "%s\n" "${TYPO3_VERSIONS_ARRAY[@]}"
    fi
}

# Function to check if a given TYPO3 version is supported.
# It takes one argument, the TYPO3 version to check.
# The function reads the supported TYPO3 versions from the environment variable TYPO3_VERSIONS.
# If the provided version is not in the list of supported versions, it prints an error message and exits with status 1.
# If the provided version is supported, it returns 0.
#
# Arguments:
#   $1 - The TYPO3 version to check.
#
# Returns:
#   0 if the provided TYPO3 version is supported.
#   1 if the provided TYPO3 version is not supported or if no version is provided.
function check_typo3_version() {
    local TYPO3=$1
    local SUPPORTED_TYPO3_VERSIONS=()
    local found=0

    if [ -z "$TYPO3" ]; then
        message red "No TYPO3 version provided. Please set one of the supported TYPO3 versions as argument: $(get_supported_typo3_versions_comma_separated)"
        exit 1
    fi

    while IFS= read -r line; do
        SUPPORTED_TYPO3_VERSIONS+=("$line")
    done < <(get_supported_typo3_versions)

    for version in "${SUPPORTED_TYPO3_VERSIONS[@]}"; do
        if [[ "$version" == "$TYPO3" ]]; then
            found=1
            break
        fi
    done

    if [[ $found -eq 0 ]]; then
        message red "TYPO3 version '$TYPO3' is not supported."
        exit 1
    fi

    return 0
}

# Function to perform pre-setup tasks for TYPO3 installation.
# It exports the provided TYPO3 version to the VERSION environment variable,
# displays an introductory message for the TYPO3 version, and starts the installation process.
#
# Arguments:
#   $1 - The TYPO3 version to set up.
#
# Usage:
#   pre_setup <TYPO3_VERSION>
function pre_setup() {
  export VERSION=$1
  export MODE="${MODE:-composer}"
  export BASE_PATH="/var/www/html/.Build/$VERSION"

  message magenta "Install TYPO3 $VERSION (${MODE})"
  _progress " ├─ Prepare environment"
    intro_typo3
    message blue "Pre Setup for TYPO3 $VERSION (${MODE})"
  _done

  # Classic (non-Composer) installs rebuild the same version slot without a
  # Composer project, so they follow a dedicated preparation path. The Composer
  # path stays byte-for-byte identical to before.
  if [ "$MODE" = "classic" ]; then
    classic_install_start
  else
    install_start
    install_composer_packages
    run_hook "post-composer"
  fi
}

# Function to perform post-setup tasks for TYPO3 installation.
# It changes the current directory to the base path, sets the TYPO3 installation database name,
# and calls the appropriate post-setup function based on the TYPO3 version.
# After that, it imports data and updates TYPO3.
function post_setup() {
  # Classic installs finalise through a dedicated path (core CLI binary,
  # explicit extension activation, docroot-relative config). The Composer path
  # below stays unchanged.
  if [ "${MODE:-composer}" = "classic" ]; then
    if [ -n "$DEMO_PROFILE" ]; then
      message yellow "Demo content requires Composer mode - skipping the '${DEMO_PROFILE}' profile."
    fi
    classic_post_setup
    # classic_post_setup leaves the cwd at $BASE_PATH/public; cd back to
    # $BASE_PATH first so a hook script sees the same cwd as in Composer mode.
    cd "$BASE_PATH" || { message red "Failed to change to $BASE_PATH"; return 1; }
    run_hook "post-install"
    printf " └─ \033[33mTYPO3 %s (classic) setup completed!\033[0m Open: https://%s.%s.%s\n" \
           "$VERSION" "$VERSION" "$DDEV_SITENAME" "$DDEV_TLD"
    return
  fi

  prepare_acceptance_testing

  cd "$BASE_PATH" || { message red "Failed to change to $BASE_PATH"; return 1; }
  TYPO3_INSTALL_DB_DBNAME=$DATABASE

  _progress " ├─ Setup TYPO3"
    if [ "$VERSION" == "11" ]; then
      post_setup_11
    elif [ "$VERSION" == "12" ]; then
      post_setup_12
    elif [ "$VERSION" == "13" ]; then
      post_setup_13
    elif [ "$VERSION" == "14" ]; then
      post_setup_14
    fi
  _done

  run_hook "post-typo3-setup"

  if [ -n "$DEMO_PROFILE" ]; then
    _progress " ├─ Install demo content"
      install_demo
    _done
  fi

  _progress " ├─ Import data"
    import_xml_data
    import_sql_data
    import_site_configs
    run_fixture_scripts
  _done
  _progress " ├─ Update TYPO3"
    update_typo3
  _done
  run_hook "post-install"

  printf " └─ \033[33mTYPO3 %s setup completed!\033[0m Open in your browser: https://%s.%s.%s\n" \
    "$VERSION" "$VERSION" "$DDEV_SITENAME" "$DDEV_TLD"
}

# Function to set a TYPO3 configuration value in a classic-mode instance by
# editing typo3conf/system/settings.php directly. Classic mode runs against
# the bare TYPO3 core CLI binary, which - unlike composer mode's
# helhum/typo3-console - has no 'configuration:set' command at all.
#
# Arguments:
#   $1 - Slash-separated config path (e.g. "SYS/trustedHostsPattern").
#   $2 - Value to set. "true"/"false" become booleans, numeric strings
#        become int/float, everything else stays a string - matching
#        configuration:set's own value coercion in composer mode.
function classic_configuration_set() {
    local settings_file="$BASE_PATH/public/typo3conf/system/settings.php"
    php -r '
        [$settingsFile, $path, $value] = array_slice($argv, 1);
        if ($value === "true") {
            $value = true;
        } elseif ($value === "false") {
            $value = false;
        } elseif (is_numeric($value)) {
            $value += 0;
        }
        $config = require $settingsFile;
        $ref = &$config;
        foreach (explode("/", $path) as $key) {
            if (!isset($ref[$key]) || !is_array($ref[$key])) {
                $ref[$key] = [];
            }
            $ref = &$ref[$key];
        }
        $ref = $value;
        file_put_contents($settingsFile, "<?php\nreturn " . var_export($config, true) . ";\n");
    ' -- "$settings_file" "$1" "$2"
}

# Function to perform post-setup tasks for a classic (non-Composer) install.
# It runs the env-driven TYPO3 setup against the core CLI binary, activates the
# extensions the classic way (classic mode does NOT auto-activate them),
# applies the development configuration, imports the fixtures and updates the
# database schema. Import and configuration helpers are reused unchanged — they
# operate on $TYPO3_BIN and the public/ paths within the same version slot.
function classic_post_setup() {
  cd "$BASE_PATH/public" || { message red "Failed to change to $BASE_PATH/public"; return 1; }
  mysql -h db -u root -proot -e "CREATE DATABASE IF NOT EXISTS $DATABASE;"

  _progress " ├─ Setup TYPO3 (classic)"
    # env-driven, reuses the existing TYPO3_DB_* / TYPO3_SETUP_* variables.
    # In classic mode this writes to typo3conf/system/settings.php.
    $TYPO3_BIN setup -n \
        --server-type="$TYPO3_SERVER_TYPE" \
        --dbname="$DATABASE" \
        --password="$TYPO3_DB_PASSWORD" \
        --admin-user-password="$TYPO3_SETUP_ADMIN_PASSWORD" \
        --create-site="https://${VERSION}.${DDEV_SITENAME}.${DDEV_TLD}"
  _done

  _progress " ├─ Activate extensions (classic)"
    # Classic mode does NOT auto-activate extensions found in typo3conf/ext
    # (unlike Composer mode since v11). Activation is PackageStates-based and
    # must be done explicitly. The core CLI ships hidden commands
    # 'extension:activate' / 'extension:deactivate' for exactly this case.
    $TYPO3_BIN extension:activate "$EXTENSION_KEY"
    for dir in /var/www/html/Tests/Acceptance/Fixtures/packages/*/; do
        [ -d "$dir" ] || continue
        $TYPO3_BIN extension:activate "$(basename "$dir")"
    done
    # impexp is an optional system extension and needed for the XML fixture
    # import below — activate it only if XML fixtures exist.
    if ls /var/www/html/Tests/Acceptance/Fixtures/*.xml >/dev/null 2>&1; then
        $TYPO3_BIN extension:activate impexp || true
    fi
    # extension:setup performs the post-activation steps (db schema, defaults).
    $TYPO3_BIN extension:setup
  _done

  _progress " ├─ Configure TYPO3 (classic)"
    classic_configuration_set 'SYS/trustedHostsPattern' "${VERSION}.${DDEV_SITENAME}.${DDEV_TLD}"
    classic_configuration_set 'BE/debug' true
    classic_configuration_set 'FE/debug' true
    classic_configuration_set 'SYS/devIPmask' '*'
    classic_configuration_set 'SYS/displayErrors' 1
    for entry in "${TYPO3_SETTINGS[@]}"; do
        eval "classic_configuration_set $entry"
    done
  _done

  _progress " ├─ Import data"
    import_xml_data; import_sql_data; import_site_configs; run_fixture_scripts
  _done
  _progress " ├─ Update TYPO3"
    # No separate schema update here: database:updateschema is a
    # helhum/typo3-console command unavailable on the bare core CLI, and
    # extension:setup above already updated the schema for every extension
    # it just activated.
    $TYPO3_BIN cache:flush
  _done
}

# Function to install demo content for the current TYPO3 version, controlled
# by the DEMO_PROFILE environment variable (set via `ddev install --demo`).
#
# Profiles:
#   introduction (default) - installs typo3/cms-introduction and removes the
#     generated "main" site, since the Introduction Package brings its own.
#     Not available on TYPO3 14 (no released version supports it yet) - falls
#     back to the bootstrap profile with a message instead of failing.
#   bootstrap - installs bk2k/bootstrap-package only, for a themed but empty
#     site. Works on every supported version, including 14.
#   custom - installs no package here; relies entirely on whatever the repo
#     already provides under Tests/Acceptance/Fixtures/ (XML/SQL/site config/
#     shell script fixtures, imported later in post_setup()).
function install_demo() {
    local profile="${DEMO_PROFILE:-introduction}"

    if [ "$profile" == "introduction" ] && [ "$VERSION" == "14" ]; then
        message yellow "typo3/cms-introduction has no TYPO3 14 release yet - falling back to the bootstrap profile."
        profile="bootstrap"
    fi

    case "$profile" in
        introduction)
            composer req typo3/cms-introduction:'*' --no-progress -n -d "$BASE_PATH"
            $TYPO3_BIN extension:setup --extension=introduction
            rm -rf "$BASE_PATH/config/sites/main"
            ;;
        bootstrap)
            composer req bk2k/bootstrap-package:'*' --no-progress -n -d "$BASE_PATH"
            ;;
        custom) ;;
        *)
            message red "Unknown demo profile '$profile'. Supported: introduction, bootstrap, custom."
            return 1
            ;;
    esac
}

# Function to run an optional, repo-owned install hook if it exists.
# Hooks live at .ddev/.setup/hooks/<name>.sh and are sourced (not executed as
# a subprocess) so they have access to $VERSION, $BASE_PATH, $TYPO3_BIN,
# $DATABASE, $EXTENSION_KEY and the message/_progress/_done helpers. Because
# they are sourced into a caller running under `set -e`, a failing command in
# a hook aborts the install and its captured output is replayed - unlike the
# best-effort Tests/Acceptance/Fixtures/*.sh scripts, a hook failure is not
# swallowed.
#
# Supported hook names: pre-install, post-composer, post-typo3-setup, post-install.
function run_hook() {
    local hook_name="$1"
    local hook_file="/var/www/html/.ddev/.setup/hooks/${hook_name}.sh"

    if [ ! -f "$hook_file" ]; then
        return 0
    fi

    _progress " ├─ Hook: $hook_name"
      source "$hook_file"
    _done
}

# Function to display an introductory message for the TYPO3 version.
# It prints a formatted message with the TYPO3 version in magenta color.
function intro_typo3() {
    message magenta "-------------------------------------------------"
    message magenta "|\t\t\t\t\t\t|"
    message magenta "| \t\t     TYPO3 $VERSION     \t\t|"
    message magenta "|\t\t\t\t\t\t|"
    message magenta "-------------------------------------------------"
}

# Function to start the installation process for TYPO3.
# It removes any existing files in the test directory for the specified version,
# sets up the environment, creates symlinks for the main and additional extensions,
# and sets up Composer for the TYPO3 installation.
function install_start() {
    run_hook "pre-install"

    rm -rf /var/www/html/.Build/$VERSION/*
    _progress " ├─ Setup environment"
      setup_environment
    _done
    _progress " ├─ Create symlinks"
      create_symlinks_main_extension
      create_symlinks_additional_extensions
    _done
    _progress " ├─ Setup composer"
      setup_composer
    _done
}

# Function to set up the environment for TYPO3 installation.
# It sets the base path for the TYPO3 installation, removes any existing files in the base path,
# creates necessary directories, sets permissions, and exports environment variables.
# Additionally, it drops the existing database for the TYPO3 version.
function setup_environment() {
    rm -rf "$BASE_PATH"
    mkdir -p "$BASE_PATH/packages/$EXTENSION_KEY"
    chmod 775 -R $BASE_PATH
    # Mode marker read by the exec wrappers (ddev <v> …) to pick the right
    # binary and working directory. Missing marker falls back to "composer".
    echo "composer" > "$BASE_PATH/.install-mode"
    export DATABASE="database_$VERSION"
    if [ "$VERSION" == "11" ]; then
        export TYPO3_BIN="$BASE_PATH/vendor/bin/typo3cms"
    else
        export TYPO3_BIN="$BASE_PATH/vendor/bin/typo3"
    fi
    compute_typo3_server_type
    export TYPO3_INSTALL_WEB_SERVER_CONFIG="$TYPO3_SERVER_TYPE"
    mysql -uroot -proot -e "DROP DATABASE IF EXISTS $DATABASE"
}

# Function to derive TYPO3's own apache/iis/other webserver type from
# DDEV_WEBSERVER_TYPE, exporting it as TYPO3_SERVER_TYPE. Derived at runtime
# rather than hardcoded, so the same setup works whether the project uses
# apache-fpm or nginx-fpm. Consumed two ways: composer mode passes it as
# TYPO3_INSTALL_WEB_SERVER_CONFIG (helhum/typo3-console only generates a
# webserver config file for "apache" (.htaccess) or "iis" (web.config) -
# nginx has no per-directory config file, so anything else is "other",
# which generates none); classic mode passes it as --server-type to TYPO3
# core's own setup command, which requires one of apache/iis/other and
# throws a TypeError if none is given non-interactively.
function compute_typo3_server_type() {
    if [ -n "${TYPO3_SERVER_TYPE:-}" ]; then
        export TYPO3_SERVER_TYPE
        return 0
    fi
    if [ "$DDEV_WEBSERVER_TYPE" == "apache-fpm" ]; then
        export TYPO3_SERVER_TYPE="apache"
    else
        export TYPO3_SERVER_TYPE="other"
    fi
}

# Function to create symlinks for the main extension.
# It iterates over the items in the current directory, excluding certain directories and files,
# and creates symbolic links for the remaining items in the given target directory.
# The target directory defaults to the Composer package path, so callers that
# omit the argument keep the previous behaviour; the classic path passes the
# TER-style typo3conf/ext/<key> directory instead.
#
# Arguments:
#   $1 - Target directory (default: $BASE_PATH/packages/$EXTENSION_KEY).
function create_symlinks_main_extension() {
    local target="${1:-$BASE_PATH/packages/$EXTENSION_KEY}"
    mkdir -p "$target"
    local exclusions=()
    read -r -a exclusions <<< "${SYMLINK_EXCLUSIONS:-Documentation Documentation-GENERATED-temp var vendor public}"
    for item in ./*; do
        local base_name; base_name=$(basename "$item")
        for exclusion in "${exclusions[@]}"; do
            if [[ $base_name == "$exclusion" ]]; then
                continue 2
            fi
        done
        ln -sr "$item" "$target/$base_name"
    done
}

# Function to create symlinks for additional extensions.
# It iterates over the directories in the specified path and creates symbolic links
# for each directory in the base path.
function create_symlinks_additional_extensions() {
    for dir in Tests/Acceptance/Fixtures/packages/*/; do
        ln -sr "$dir" "$BASE_PATH/packages/$(basename "$dir")"
    done

    for extra_dir in "${FIXTURE_EXTENSION_DIRS[@]}"; do
        for dir in "$extra_dir"/*/; do
            [ -d "$dir" ] && ln -sr "$dir" "$BASE_PATH/packages/$(basename "$dir")"
        done
    done
}

# Function to start a classic (non-Composer) installation for the current
# version. It rebuilds the same version slot from scratch: it prepares the
# environment, downloads the matching TYPO3 sources from get.typo3.org and
# wires up the classic docroot symlinks. There is deliberately no Composer
# step here — see classic_create_symlinks for why.
function classic_install_start() {
    rm -rf "$BASE_PATH"
    _progress " ├─ Setup environment"
      classic_setup_environment
    _done
    _progress " ├─ Download TYPO3 $VERSION sources"
      classic_download_sources
    _done
    _progress " ├─ Create symlinks"
      classic_create_symlinks
    _done
}

# Function to set up the environment for a classic TYPO3 installation.
# It creates the classic docroot layout (typo3conf/ext, fileadmin, typo3temp),
# writes the "classic" mode marker, exports the database name and the TYPO3
# binary (the core CLI shipped in the sources, NOT vendor/bin), and drops any
# existing database for the version.
function classic_setup_environment() {
    mkdir -p "$BASE_PATH/public/typo3conf/ext"
    mkdir -p "$BASE_PATH/public/fileadmin" "$BASE_PATH/public/typo3temp"
    chmod 775 -R "$BASE_PATH"
    echo "classic" > "$BASE_PATH/.install-mode"
    export DATABASE="database_$VERSION"
    export TYPO3_BIN="$BASE_PATH/public/typo3/sysext/core/bin/typo3"   # core binary, NOT vendor/bin
    compute_typo3_server_type
    mysql -uroot -proot -e "DROP DATABASE IF EXISTS $DATABASE"
}

# Function to download the TYPO3 sources for the current major version.
# get.typo3.org/<major> redirects to the current source tarball of that major,
# which is unpacked into a version-specific source directory.
function classic_download_sources() {
    local src_dir="$BASE_PATH/typo3_src-$VERSION"
    mkdir -p "$src_dir"
    # get.typo3.org/<major> redirects to the current source tarball of that major.
    # -f is essential: without it, an HTML error page would be piped into tar.
    curl -fsSL "https://get.typo3.org/$VERSION" | tar xz --strip-components=1 -C "$src_dir"
}

# Function to create the symlinks for a classic docroot.
# It wires up the classic source symlink trio (typo3_src, typo3, index.php),
# copies the shipped _.htaccess into the docroot, and symlinks the main
# extension plus the fixture packages into typo3conf/ext/<key>.
#
# No Composer autoloader is used on purpose: the extension is linked like a TER
# install so TYPO3 has to resolve its classes from the `autoload` key in
# ext_emconf.php (v12/v13) resp. from the extension's composer.json (v14) —
# which is exactly the fidelity this mode exists to test.
function classic_create_symlinks() {
    ( cd "$BASE_PATH/public"
      ln -sf "../typo3_src-$VERSION" typo3_src
      ln -sf typo3_src/typo3         typo3
      ln -sf typo3_src/index.php     index.php )
    # Apache rewrites for slug-based frontend URLs: the source package ships a
    # ready-made _.htaccess in its root — copy it into the docroot.
    if [ -f "$BASE_PATH/typo3_src-$VERSION/_.htaccess" ]; then
        cp "$BASE_PATH/typo3_src-$VERSION/_.htaccess" "$BASE_PATH/public/.htaccess"
    fi
    # Main extension → typo3conf/ext/<underscored key>, faithful to a TER layout.
    # No composer autoloader: TYPO3 must resolve classes from ext_emconf.php
    # (v12/v13) resp. the extension's composer.json (v14).
    ( cd /var/www/html
      create_symlinks_main_extension "$BASE_PATH/public/typo3conf/ext/$EXTENSION_KEY" )
    # Sitepackage + additional fixture packages, same target dir.
    for dir in /var/www/html/Tests/Acceptance/Fixtures/packages/*/; do
        [ -d "$dir" ] || continue
        local pkg_key target
        pkg_key="$(basename "$dir")"
        target="$BASE_PATH/public/typo3conf/ext/$pkg_key"
        if [ -f "${dir}ext_emconf.php" ]; then
            ln -sr "$dir" "$target"
        else
            # Classic-mode package discovery only scans for ext_emconf.php
            # (PackageManager::scanAvailablePackages), so a composer.json-only
            # fixture like the default sitepackage would never be found. Link
            # its contents individually into a real directory instead of
            # symlinking the whole thing, so a generated ext_emconf.php can
            # live alongside them without touching the fixture's own source
            # tree (which is shared, unmodified, across every version slot).
            mkdir -p "$target"
            ( cd "$dir"
              for item in ./*; do
                  ln -sr "$item" "$target/$(basename "$item")"
              done )
            classic_generate_ext_emconf "$target" "$pkg_key"
        fi
    done
}

# Function to generate a minimal ext_emconf.php for a classic-mode extension
# directory that ships only a composer.json. TYPO3's classic-mode package
# discovery (PackageManager::scanAvailablePackages) only recognizes
# directories containing an ext_emconf.php, so a Composer-manifest-only
# fixture package would otherwise never be found or activatable.
#
# Arguments:
#   $1 - Target directory (must already contain a composer.json).
#   $2 - Extension key, matching the directory name under typo3conf/ext.
function classic_generate_ext_emconf() {
    local target="$1" ext_key="$2"
    [ -f "$target/composer.json" ] || return 0
    php -r '
        [$target, $extKey] = array_slice($argv, 1);
        $composer = json_decode(file_get_contents("$target/composer.json"), true) ?? [];
        $emConf = [
            "title" => $composer["description"] ?? $extKey,
            "description" => $composer["description"] ?? "",
            "version" => $composer["version"] ?? "1.0.0",
            "state" => "stable",
        ];
        if (!empty($composer["autoload"])) {
            $emConf["autoload"] = $composer["autoload"];
        }
        file_put_contents(
            "$target/ext_emconf.php",
            "<?php\n\$EM_CONF[\$_EXTKEY] = " . var_export($emConf, true) . ";\n"
        );
    ' -- "$target" "$ext_key"
}

# Function to set up Composer for TYPO3 installation.
# It initializes a new Composer project in the specified base path,
# configures the TYPO3 web directory, sets up the repository for packages,
# and allows necessary Composer plugins.
function setup_composer() {
    composer init --name="test/typo3-$VERSION" --description="TYPO3 $VERSION" --no-interaction --working-dir "$BASE_PATH"
    composer config extra.typo3/cms.web-dir public --working-dir "$BASE_PATH"
    composer config repositories.packages path 'packages/*' --working-dir "$BASE_PATH"
    composer config --no-interaction allow-plugins.typo3/cms-composer-installers true --working-dir "$BASE_PATH"
    composer config --no-interaction allow-plugins.typo3/class-alias-loader true --working-dir "$BASE_PATH"
    # These are throwaway dev instances, including of TYPO3 versions past
    # their security support window - Composer's advisory audit must not
    # block installing them.
    composer config --no-interaction audit.block-insecure false --working-dir "$BASE_PATH"

    for entry in "${COMPOSER_CONFIG[@]}"; do
        composer config --no-interaction $entry --working-dir "$BASE_PATH"
    done
}

# Function to set up TYPO3 configuration.
# It changes the current directory to the base path, sets the TYPO3 installation database name,
# and configures various TYPO3 settings such as debug mode, error display, trusted hosts pattern,
# mail transport, and graphics processor.
function setup_typo3() {
    cd "$BASE_PATH" || { message red "Failed to change to $BASE_PATH"; return 1; }
    export TYPO3_INSTALL_DB_DBNAME=$DATABASE
    $TYPO3_BIN configuration:set 'BE/debug' 1
    $TYPO3_BIN configuration:set 'FE/debug' 1
    $TYPO3_BIN configuration:set 'SYS/devIPmask' '*'
    $TYPO3_BIN configuration:set 'SYS/displayErrors' 1
    $TYPO3_BIN configuration:set 'SYS/trustedHostsPattern' "$VERSION.$DDEV_SITENAME.$DDEV_TLD"
    $TYPO3_BIN configuration:set 'MAIL/transport' 'smtp'
    $TYPO3_BIN configuration:set 'MAIL/transport_smtp_server' 'localhost:1025'
    $TYPO3_BIN configuration:set 'GFX/processor' 'ImageMagick'
    $TYPO3_BIN configuration:set 'GFX/processor_path' '/usr/bin/'

    for entry in "${TYPO3_SETTINGS[@]}"; do
        eval "$TYPO3_BIN configuration:set $entry"
    done
}

# Function to update TYPO3.
# It updates the TYPO3 database schema and flushes the cache.
function update_typo3() {
    $TYPO3_BIN database:updateschema
    $TYPO3_BIN cache:flush
}

# Function to install required Composer packages for TYPO3.
function install_composer_packages() {
  _progress " ├─ Install composer packages"
    local packages=(
        "typo3/cms-base-distribution:^$VERSION"
        "$PACKAGE_NAME:*@dev"
        "helhum/typo3-console:*"
    )

    if [ ${#SITEPACKAGE_PACKAGES[@]} -gt 0 ]; then
        for entry in "${SITEPACKAGE_PACKAGES[@]}"; do
            eval "packages+=(\"$entry\")"
        done
    else
        packages+=("test/sitepackage:*@dev")
    fi

    for entry in "${ADDITIONAL_PACKAGES[@]}"; do
        eval "packages+=(\"$entry\")"
    done

    composer req "${packages[@]}" --no-progress -n -d $BASE_PATH
  _done
}

# Function to install Codeception and related packages using Composer.
# It checks if the codeception.yml file exists in the extension's package directory.
# If the file exists, it installs the necessary Codeception packages, creates symlinks for
# the codeception.yml file and acceptance tests directory, and builds the Codeception configuration.
function prepare_acceptance_testing() {
  if [ ! -f "$BASE_PATH/packages/$EXTENSION_KEY/codeception.yml" ]; then
      return
  fi
  _progress " ├─ Prepare acceptance testing"
    composer config --no-interaction allow-plugins.codeception/c3 true --working-dir "$BASE_PATH"
    composer req --dev codeception/codeception:'*' \
              codeception/module-asserts:'*' \
              codeception/module-cli:'*' \
              codeception/module-db:'*' \
              codeception/module-phpbrowser:'*' \
              codeception/module-webdriver:'*' \
              eliashaeussler/typo3-codeception-helper:'*' \
              typo3/testing-framework:'*' \
              --no-progress -n -d $BASE_PATH

    ln -sr "$BASE_PATH/packages/$EXTENSION_KEY/codeception.yml" "$BASE_PATH/codeception.yml"
    mkdir -p "$BASE_PATH/Tests"
    ln -sr "$BASE_PATH/packages/$EXTENSION_KEY/Tests/Acceptance" "$BASE_PATH/Tests/Acceptance"

    $BASE_PATH/vendor/bin/codecept build -c $BASE_PATH/codeception.yml
  _done
}

# Function to import XML data into TYPO3.
# It sets the public directory and export directory paths, checks for all .xml files in the FIXTURE_DIR,
# and imports each XML file using TYPO3's import/export tool.
function import_xml_data() {
    PUBLIC_DIR="/var/www/html/.Build/${VERSION}/public"
    EXPORT_DIR="${PUBLIC_DIR}/fileadmin/user_upload/_temp_/importexport"
    FIXTURE_DIR="/var/www/html/Tests/Acceptance/Fixtures"

    mkdir -p $EXPORT_DIR

    for XML_FILE in "$FIXTURE_DIR"/*.xml; do
        if [ -f "$XML_FILE" ]; then
            FILENAME=$(basename "$XML_FILE")
            message yellow "Importing XML file $FILENAME..."
            cp "$XML_FILE" "$EXPORT_DIR/"
            $TYPO3_BIN impexp:import -vvv --force-uid "$EXPORT_DIR/$FILENAME"
        fi
    done

    # Check if no XML files were found
    if ! ls "$FIXTURE_DIR"/*.xml >/dev/null 2>&1; then
        message yellow "No XML files found in $FIXTURE_DIR. Skipping XML import."
    fi
}

# Function to import SQL data into TYPO3.
# It checks if the SQL data files exists, and if it does, it imports the SQL
# data into the TYPO3 database using the MySQL command.
function import_sql_data() {
    FIXTURE_DIR="/var/www/html/Tests/Acceptance/Fixtures"

    for DATA_FILE in "$FIXTURE_DIR"/*.sql; do
        if [ -f "$DATA_FILE" ]; then
            message yellow "Importing $DATA_FILE..."
            mysql -h db -u root -p"root" "$DATABASE" < "$DATA_FILE"
        fi
    done

    if ! ls "$FIXTURE_DIR"/*.sql >/dev/null 2>&1; then
        message yellow "No SQL files found in $FIXTURE_DIR. Skipping SQL import."
    fi
}

# Function to import site configuration YAML fixtures.
# It copies each directory under Tests/Acceptance/Fixtures/sites/ to the
# TYPO3 site configuration path for the current version, replacing
# __VERSION__ with the TYPO3 version and __SITENAME__ with DDEV_SITENAME
# in config.yaml. VERSION_PLACEHOLDER is still supported for backwards
# compatibility with fixtures written before __VERSION__ was introduced.
function import_site_configs() {
    FIXTURE_DIR="/var/www/html/Tests/Acceptance/Fixtures/sites"

    if [ ! -d "$FIXTURE_DIR" ]; then
        return
    fi

    # Classic mode reads site configs from public/typo3conf/sites/, whereas the
    # Composer mode uses config/sites/. Pick the target accordingly.
    if [ "${MODE:-composer}" = "classic" ]; then
        TARGET_BASE="/var/www/html/.Build/$VERSION/public/typo3conf/sites"
    else
        TARGET_BASE="/var/www/html/.Build/$VERSION/config/sites"
    fi

    mkdir -p "$TARGET_BASE"

    for SITE_DIR in "$FIXTURE_DIR"/*/; do
        if [ -d "$SITE_DIR" ]; then
            SITE_NAME=$(basename "$SITE_DIR")
            message yellow "Importing site config $SITE_NAME..."
            mkdir -p "$TARGET_BASE/$SITE_NAME"
            cp -r "$SITE_DIR"* "$TARGET_BASE/$SITE_NAME/"
            if [ -f "$TARGET_BASE/$SITE_NAME/config.yaml" ]; then
                sed -i \
                    -e "s/__VERSION__/$VERSION/g" \
                    -e "s/VERSION_PLACEHOLDER/$VERSION/g" \
                    -e "s/__SITENAME__/$DDEV_SITENAME/g" \
                    "$TARGET_BASE/$SITE_NAME/config.yaml"
            fi
        fi
    done
}

# Function to run shell script fixtures from Tests/Acceptance/Fixtures/.
# Executes any *.sh scripts found in the fixture directory. Failures are
# logged but do not abort the setup, so external services (e.g. Solr,
# Elasticsearch) that are not yet reachable during early provisioning
# do not break the installation.
function run_fixture_scripts() {
    FIXTURE_DIR="/var/www/html/Tests/Acceptance/Fixtures"

    for SCRIPT in "$FIXTURE_DIR"/*.sh; do
        if [ -f "$SCRIPT" ]; then
            message yellow "Running fixture script $(basename "$SCRIPT")..."
            bash "$SCRIPT" || message red "Fixture script $(basename "$SCRIPT") failed (continuing)"
        fi
    done
}

# Function to perform post-setup tasks for TYPO3 version 11.
# It sets up TYPO3 by running the installation setup, configuring TYPO3 settings,
# and modifying configuration files to enable deprecations and adjust base paths.
function post_setup_11 {
  $TYPO3_BIN install:setup -n --database-name $DATABASE
  setup_typo3
  $TYPO3_BIN configuration:set 'GFX/processor_path_lzw' '/usr/bin/'

  sed -i "/'deprecations'/,/^[[:space:]]*'disabled' => true,/s/'disabled' => true,/'disabled' => false,/" /var/www/html/.Build/$VERSION/public/typo3conf/LocalConfiguration.php

  sed -i -E "s|^base: .*|base: /|" /var/www/html/.Build/$VERSION/config/sites/main/config.yaml
}

# Function to perform post-setup tasks for TYPO3 version 12.
# It sets up TYPO3 by running the installation setup, configuring TYPO3 settings,
# and modifying configuration files to enable deprecations and adjust base paths.
function post_setup_12 {
  $TYPO3_BIN install:setup -n --database-name $DATABASE
  setup_typo3

  sed -i "/'deprecations'/,/^[[:space:]]*'disabled' => true,/s/'disabled' => true,/'disabled' => false,/" /var/www/html/.Build/$VERSION/config/system/settings.php

  sed -i -E "s|^base: .*|base: /|" /var/www/html/.Build/$VERSION/config/sites/main/config.yaml
}

# Function to perform post-setup tasks for TYPO3 version 13.
# It creates the TYPO3 database, sets up TYPO3 by running the installation setup,
# configures TYPO3 settings, and modifies configuration files to enable deprecations.
function post_setup_13 {
  mysql -h db -u root -p"root" -e "CREATE DATABASE $DATABASE;"
  $TYPO3_BIN  setup -n --dbname=$DATABASE --password=$TYPO3_DB_PASSWORD --create-site="https://${VERSION}.${DDEV_SITENAME}.${DDEV_TLD}" --admin-user-password=$TYPO3_SETUP_ADMIN_PASSWORD
  setup_typo3

  sed -i "/'deprecations'/,/^[[:space:]]*'disabled' => true,/s/'disabled' => true,/'disabled' => false,/" /var/www/html/.Build/$VERSION/config/system/settings.php

  sed -i -E "s|^base: .*|base: /|" /var/www/html/.Build/$VERSION/config/sites/main/config.yaml
}

# Function to perform post-setup tasks for TYPO3 version 14.
# It creates the TYPO3 database, sets up TYPO3 by running the installation setup,
# configures TYPO3 settings, and modifies configuration files to enable deprecations.
function post_setup_14 {
  mysql -h db -u root -p"root" -e "CREATE DATABASE $DATABASE;"
  $TYPO3_BIN  setup -n --dbname=$DATABASE --password=$TYPO3_DB_PASSWORD --create-site="https://${VERSION}.${DDEV_SITENAME}.${DDEV_TLD}" --admin-user-password=$TYPO3_SETUP_ADMIN_PASSWORD
  setup_typo3

  sed -i "/'deprecations'/,/^[[:space:]]*'disabled' => true,/s/'disabled' => true,/'disabled' => false,/" /var/www/html/.Build/$VERSION/config/system/settings.php

  sed -i -E "s|^base: .*|base: /|" /var/www/html/.Build/$VERSION/config/sites/main/config.yaml
}

# Function to display a colored message.
# It takes two arguments: the color and the message to display.
# The function supports the following colors: red, green, yellow, blue, magenta, cyan.
# If an unsupported color is provided, the message is displayed without color.
#
# Usage:
#   message <color> <message>
#
# Arguments:
#   color   - The color to use for the message (red, green, yellow, blue, magenta, cyan).
#   message - The message to display.
message() {
    local color=$1
    local message=$2

    case $color in
        red)
            echo -e "\033[31m$message\033[0m"
            ;;
        green)
            echo -e "\033[32m$message\033[0m"
            ;;
        yellow)
            echo -e "\033[33m$message\033[0m"
            ;;
        blue)
            echo -e "\033[34m$message\033[0m"
            ;;
        magenta)
            echo -e "\033[35m$message\033[0m"
            ;;
        cyan)
            echo -e "\033[36m$message\033[0m"
            ;;
        *)
            echo -e "$message"
            ;;
    esac
}
export -f message
