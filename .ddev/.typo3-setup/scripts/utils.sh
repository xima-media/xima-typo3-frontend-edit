#!/bin/bash

#ddev-generated
# If you want to take over this file and customize it, remove the line above
# and ddev will respect it and won't overwrite the file.

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
  export BASE_PATH="/var/www/html/.Build/$VERSION"
  intro_typo3
  message blue "Pre Setup for TYPO3 $VERSION"
  install_start
  message blue "Install Composer Packages"
}

# Function to perform post-setup tasks for TYPO3 installation.
# It changes the current directory to the base path, sets the TYPO3 installation database name,
# and calls the appropriate post-setup function based on the TYPO3 version.
# After that, it imports data and updates TYPO3.
function post_setup() {
  message blue "Post Setup for TYPO3 $VERSION"
  cd $BASE_PATH
  TYPO3_INSTALL_DB_DBNAME=$DATABASE

  if [ "$VERSION" == "11" ]; then
    post_setup_11
  elif [ "$VERSION" == "12" ]; then
    post_setup_12
  elif [ "$VERSION" == "13" ]; then
    post_setup_13
  fi

  import_data
  update_typo3
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
    rm -rf /var/www/html/.Build/$VERSION/*
    setup_environment
    create_symlinks_main_extension
    create_symlinks_additional_extensions
    setup_composer
}

# Function to set up the environment for TYPO3 installation.
# It sets the base path for the TYPO3 installation, removes any existing files in the base path,
# creates necessary directories, sets permissions, and exports environment variables.
# Additionally, it drops the existing database for the TYPO3 version.
function setup_environment() {
    rm -rf "$BASE_PATH"
    mkdir -p "$BASE_PATH/packages/$EXTENSION_KEY"
    chmod 775 -R $BASE_PATH
    export DATABASE="database_$VERSION"
    if [ "$VERSION" == "11" ]; then
        export TYPO3_BIN="$BASE_PATH/vendor/bin/typo3cms"
    else
        export TYPO3_BIN="$BASE_PATH/vendor/bin/typo3"
    fi
    mysql -uroot -proot -e "DROP DATABASE IF EXISTS $DATABASE"
}

# Function to create symlinks for the main extension.
# It iterates over the items in the current directory, excluding certain directories and files,
# and creates symbolic links for the remaining items in the specified base path.
function create_symlinks_main_extension() {
    local exclusions=(".*" "Documentation" "Documentation-GENERATED-temp" "var")
    for item in ./*; do
        local base_name=$(basename "$item")
        for exclusion in "${exclusions[@]}"; do
            if [[ $base_name == "$exclusion" ]]; then
                continue 2
            fi
        done
        ln -sr "$item" "$BASE_PATH/packages/$EXTENSION_KEY/$base_name"
    done
}

# Function to create symlinks for additional extensions.
# It iterates over the directories in the specified path and creates symbolic links
# for each directory in the base path.
function create_symlinks_additional_extensions() {
    for dir in Tests/.typo3-setup/packages/*/; do
        ln -sr "$dir" "$BASE_PATH/packages/$(basename "$dir")"
    done
}

# Function to set up Composer for TYPO3 installation.
# It initializes a new Composer project in the specified base path,
# configures the TYPO3 web directory, sets up the repository for packages,
# and allows necessary Composer plugins.
function setup_composer() {
    composer init --name="xima/typo3-$VERSION" --description="TYPO3 $VERSION" --no-interaction --working-dir "$BASE_PATH"
    composer config extra.typo3/cms.web-dir public --working-dir "$BASE_PATH"
    composer config repositories.packages path 'packages/*' --working-dir "$BASE_PATH"
    composer config --no-interaction allow-plugins.typo3/cms-composer-installers true --working-dir "$BASE_PATH"
    composer config --no-interaction allow-plugins.typo3/class-alias-loader true --working-dir "$BASE_PATH"
}

# Function to set up TYPO3 configuration.
# It changes the current directory to the base path, sets the TYPO3 installation database name,
# and configures various TYPO3 settings such as debug mode, error display, trusted hosts pattern,
# mail transport, and graphics processor.
function setup_typo3() {
    cd $BASE_PATH
    export TYPO3_INSTALL_DB_DBNAME=$DATABASE
    $TYPO3_BIN configuration:set 'BE/debug' 1
    $TYPO3_BIN configuration:set 'FE/debug' 1
    $TYPO3_BIN configuration:set 'SYS/devIPmask' '*'
    $TYPO3_BIN configuration:set 'SYS/displayErrors' 1
    $TYPO3_BIN configuration:set 'SYS/trustedHostsPattern' '.*.*'
    $TYPO3_BIN configuration:set 'MAIL/transport' 'smtp'
    $TYPO3_BIN configuration:set 'MAIL/transport_smtp_server' 'localhost:1025'
    $TYPO3_BIN configuration:set 'GFX/processor' 'ImageMagick'
    $TYPO3_BIN configuration:set 'GFX/processor_path' '/usr/bin/'
}

# Function to update TYPO3.
# It updates the TYPO3 database schema and flushes the cache.
function update_typo3() {
    $TYPO3_BIN database:updateschema
    $TYPO3_BIN cache:flush
}

# Function to import data into TYPO3.
# It sets the public directory and export directory paths, checks if the data file exists,
# and if it does, it copies the data file to the export directory and imports the data using TYPO3's import/export tool.
function import_data() {
    PUBLIC_DIR="/var/www/html/.Build/${VERSION}/public"
    EXPORT_DIR="${PUBLIC_DIR}/fileadmin/user_upload/_temp_/importexport"
    DATA_FILE="/var/www/html/Tests/.typo3-setup/data/data.xml"

    if [ ! -f "$DATA_FILE" ]; then
        message yellow "Data file $DATA_FILE not found. Skipping import."
        return
    fi

    mkdir -p $EXPORT_DIR
    cp /$DATA_FILE $EXPORT_DIR
    $TYPO3_BIN impexp:import -vvv --force-uid "$EXPORT_DIR/data.xml"
}

# Function to perform post-setup tasks for TYPO3 version 11.
# It sets up TYPO3 by running the installation setup, configuring TYPO3 settings,
# and modifying configuration files to enable deprecations and adjust base paths.
function post_setup_11 {
  $TYPO3_BIN install:setup -n --database-name $DATABASE
  setup_typo3
  $TYPO3_BIN configuration:set 'GFX/processor_path_lzw' '/usr/bin/'

  sed -i "/'deprecations'/,/^[[:space:]]*'disabled' => true,/s/'disabled' => true,/'disabled' => false,/" /var/www/html/.Build/$VERSION/public/typo3conf/LocalConfiguration.php

  sed -i -e "s/base: ht\//base: \//g" /var/www/html/.Build/$VERSION/config/sites/main/config.yaml
  sed -i -e 's/base: \/en\//base: \//g' /var/www/html/.Build/$VERSION/config/sites/main/config.yaml
}

# Function to perform post-setup tasks for TYPO3 version 12.
# It sets up TYPO3 by running the installation setup, configuring TYPO3 settings,
# and modifying configuration files to enable deprecations and adjust base paths.
function post_setup_12 {
  $TYPO3_BIN install:setup -n --database-name $DATABASE
  setup_typo3

  sed -i "/'deprecations'/,/^[[:space:]]*'disabled' => true,/s/'disabled' => true,/'disabled' => false,/" /var/www/html/.Build/$VERSION/config/system/settings.php

  sed -i -e "s/base: ht\//base: \//g" /var/www/html/.Build/$VERSION/config/sites/main/config.yaml
  sed -i -e 's/base: \/en\//base: \//g' /var/www/html/.Build/$VERSION/config/sites/main/config.yaml
}

# Function to perform post-setup tasks for TYPO3 version 13.
# It creates the TYPO3 database, sets up TYPO3 by running the installation setup,
# configures TYPO3 settings, and modifies configuration files to enable deprecations.
function post_setup_13 {
  mysql -h db -u root -p"root" -e "CREATE DATABASE $DATABASE;"
  $TYPO3_BIN  setup -n --dbname=$DATABASE --password=$TYPO3_DB_PASSWORD --create-site="https://${VERSION}.${EXTENSION_NAME}.ddev.site" --admin-user-password=$TYPO3_SETUP_ADMIN_PASSWORD
  setup_typo3

  sed -i "/'deprecations'/,/^[[:space:]]*'disabled' => true,/s/'disabled' => true,/'disabled' => false,/" /var/www/html/.Build/$VERSION/config/system/settings.php
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
