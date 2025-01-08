#!/bin/bash

function get_lowest_supported_typo3_versions() {
    local TYPO3_VERSIONS_ARRAY=()
    IFS=' ' read -r -a TYPO3_VERSIONS_ARRAY <<< "$TYPO3_VERSIONS"
    if [ ${#TYPO3_VERSIONS_ARRAY[@]} -eq 0 ]; then
        message red "Error! No supported TYPO3 versions found in environment variables."
        exit 1
    fi
    printf "%s\n" "${TYPO3_VERSIONS_ARRAY[@]}" | sort -V | head -n 1
}

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

function intro_typo3() {
    local version=$1
    message magenta "-------------------------------------------------"
    message magenta "|\t\t\t\t\t\t|"
    message magenta "| \t\t     TYPO3 $version     \t\t|"
    message magenta "|\t\t\t\t\t\t|"
    message magenta "-------------------------------------------------"
}

function install_start() {
    local version=$1
    rm -rf /var/www/html/.test/$version/*
    setup_environment $version
    create_symlinks_main_extension
    setup_composer
}

function setup_environment() {
    local version=$1
    BASE_PATH="/var/www/html/.test/$version"
    rm -rf "$BASE_PATH"
    mkdir -p "$BASE_PATH/packages/$EXTENSION_KEY"
    chmod 775 -R $BASE_PATH
    export DATABASE="database_$version"
    export BASE_PATH
    export VERSION="$version"
    export TYPO3_BIN="$BASE_PATH/vendor/bin/typo3"
    mysql -uroot -proot -e "DROP DATABASE IF EXISTS $DATABASE"
}

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

function setup_composer() {
    composer init --name="xima/typo3-$VERSION" --description="TYPO3 $VERSION" --no-interaction --working-dir "$BASE_PATH"
    composer config extra.typo3/cms.web-dir public --working-dir "$BASE_PATH"
    composer config repositories.packages path 'packages/*' --working-dir "$BASE_PATH"
    composer config --no-interaction allow-plugins.typo3/cms-composer-installers true --working-dir "$BASE_PATH"
    composer config --no-interaction allow-plugins.typo3/class-alias-loader true --working-dir "$BASE_PATH"
}

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

function update_typo3() {
    $TYPO3_BIN database:updateschema
    $TYPO3_BIN cache:flush
}

function add_typoscript_set() {
    mysql -uroot -proot $DATABASE -e 'UPDATE sys_template SET include_static_file = CONCAT(include_static_file, ",EXT:xima_typo3_frontend_edit/Configuration/TypoScript") WHERE uid = 1;'
}

function add_site_set() {
  yaml_file="$BASE_PATH/config/sites/main/config.yaml"

  lines_to_add="dependencies:
    - typo3/fluid-styled-content
    - typo3/fluid-styled-content-css
    - xima/xima-typo3-frontend-edit"

  if ! grep -q "xima/xima-typo3-frontend-edit" "$yaml_file"; then
    echo "$lines_to_add" >> "$yaml_file"
  fi
  mysql -uroot -proot $DATABASE -e 'UPDATE sys_template SET clear = 0 WHERE uid = 1;'
}

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
