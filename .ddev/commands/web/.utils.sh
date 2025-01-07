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

intro_typo3() {
    local TYPO3=$1
    message magenta "-------------------------------------------------"
    message magenta "| \t\t\tTYPO3\t\t\t|"
    message magenta "-------------------------------------------------"
    message magenta "| \t\t\t$TYPO3\t\t\t|"
    message magenta "-------------------------------------------------"
    message magenta ""
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
