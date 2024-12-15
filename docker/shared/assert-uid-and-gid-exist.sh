#!/bin/bash

set -e

function assert-uid-and-gid-exist () {
    local exitCode=0;

    echo "\$UID: $UID"

    if [[ -z "$UID" ]] || [[ $(echo "$UID" | grep -Ec '^[0-9]{2,}$') -ne 1 ]]; then
        echo "\$UID is invalid."
        exitCode=1
    fi

    echo "\$GID: $GID"

    if [[ -z "$GID" ]] || [[ $(echo "$GID" | grep -Ec '^[0-9]{2,}$') -ne 1 ]]; then
        echo "\§GID is invalid."
        exitCode=1
    fi

    return $exitCode
}
