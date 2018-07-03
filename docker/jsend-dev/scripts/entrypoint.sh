#!/bin/bash

# Add local user

USER_ID=${USER_ID:-9001}
GROUP_ID=${GROUP_ID:-9001}

# add group if it doesn't exist yet
getent group ${GROUP_ID} &>/dev/null || groupadd -g ${GROUP_ID} group

# add user if it doesn't exist yet
getent passwd ${USER_ID} &>/dev/null || useradd --shell /bin/bash -u ${USER_ID} -g ${GROUP_ID} -o -c "" -m user

# set correct home
export HOME=/home/user

if [ -e /known_hosts ]; then
	mkdir /home/user/.ssh
	cp /known_hosts /home/user/.ssh/known_hosts
	chown -R user:group /home/user/.ssh/
fi

# su to the new user 
exec /usr/sbin/gosu ${USER_ID} "$@"
