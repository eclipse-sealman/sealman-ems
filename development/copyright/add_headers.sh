#!/bin/bash

echo "Usage: ./development/copyright/add_headers.sh <type> (replace)"
echo "<type> is one of \"default\", \"git-diff\" or absolute paths to search for files to be replaced (comma separated)"
echo "(replace) is optional string \"replace\" passed to actually replace files, otherwise it will be a dry run"
echo ""

scriptDir=$(cd -- "$( dirname -- "${BASH_SOURCE[0]}" )" &> /dev/null && pwd)
appDir="${scriptDir/\/development\/copyright//}"

defaultPaths=("app" "bin" "config" "docker" "migrations" "src" "templates" "tests")
givenPaths="${1}"
paths=()

if [ "$givenPaths" == "default" ]; then
    for defaultPath in "${defaultPaths[@]}"; do
        paths=("${paths[@]}" "${appDir}${defaultPath}")
    done

    echo "[INFO] Using default paths \"${paths[@]}\""
elif [ "$givenPaths" == "git-diff" ]; then
    echo "[ERROR] Not yet implemented"
    exit 1
    echo "[INFO] Using files from \"git-diff --name-only\""
else
    IFS=',' read -r -a paths <<< "$givenPaths"
    echo "[INFO] Using given paths \"${paths[@]}\""
fi

if [ -z "$paths" ]; then
    echo "[ERROR] <type> Parameter is empty"
fi

dryRun=true
if [ "$2" == "replace" ]; then
    dryRun=false
fi

if [ "$dryRun" = true ] ; then
    echo "[INFO] Dry run"
else
    echo "[INFO] This script will replace files (NOT a dry run)"
fi

echo ""

extensions=("tsx")

add_header() {
    local file="$1"
    local extension="$2"

    local copyrightFile="${scriptDir}/${extension}.copyright"
    local headerSize=$(wc -c < "$copyrightFile")

    if head -c "${headerSize}" "$file" | cmp -s "${copyrightFile}"; then
        echo "[DEBUG] File \"$file\" already has copyright header. Skipping"
        return
    fi

    echo "[INFO] File \"$file\" is missing copyright header. Adding"

    if [ "$dryRun" = true ] ; then
        # Dry run. Do not replace
        return
    fi

    tmpFile=$(mktemp "${scriptDir}/file.${extension}.XXXXXX")
    cat "${copyrightFile}" > "${tmpFile}"
    cat "${file}" >> "${tmpFile}"
    mv "${tmpFile}" "${file}" 
}


for extension in "${extensions[@]}"; do
    for path in "${paths[@]}"; do
        while IFS= read -r -d '' file; do
            add_header "$file" "$extension"
        done < <(find "${path}" -type f -name "*.${extension}" -print0)
    done
done
