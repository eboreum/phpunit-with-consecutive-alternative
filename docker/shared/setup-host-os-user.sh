#!/bin/bash

set -e

source "/assert-uid-and-gid-exist.sh"

assert-uid-and-gid-exist

if [[ -z $(getent group 'host-os-user') ]] && [[ $(getent group | awk -F ':' '{print$3}' | grep -Ec "^$GID$") -ne 1 ]]; then
    commandToExecute="addgroup -g $GID host-os-user"

    echo $commandToExecute
    eval $commandToExecute
else
    echo "Group $GID already exists."
fi

groupName=$(getent group | grep -E -m 1 "^[^:]+:[^:]+:$GID(:|$)" | awk -F ':' '{print$1}')
echo "Group name: $groupName"

if [[ -z "$groupName" ]]; then
    echo "No group name found."

    exit 1
fi

if [[ -z $(getent passwd 'host-os-user') ]] && [[ $(getent passwd | awk -F ':' '{print$3}' | grep -Ec "^$UID$") -ne 1 ]]; then
    commandToExecute="adduser -u $UID -G '$groupName' -D host-os-user"

    echo $commandToExecute
    eval $commandToExecute
else
    echo "User $UID already exists."
fi

mkdir -p /home/host-os-user
chown -R $UID:$GID /home/host-os-user

echo "Directory '/home/host-os-user' created."
