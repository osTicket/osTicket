#!/usr/bin/env sh
set -ev

# This is the parent project's repository
OSTICKET_REPO="https://github.com/osTicket/osTicket.git"

# Can't use /usr/bin/realpath on travis image for some reason
PARENT=$(dirname $(pwd))
CHECKOUT_FOLDER="$PARENT/osticket"

# Validate that the environment variable is set by setting it to the repo tag
TAG="v$OSTICKET_VERSION"
if [ $TAG = "v" ]; then echo "osTicket version not set."; exit 1; fi

# Validate that the tag exists in the repo
if git ls-remote --tags $OSTICKET_REPO | grep -q "$TAG"
then 
	echo "Tagged version of osTicket exists, pulling into Travis:"
	# Download osTicket from repo into a folder beneath our working directory called "osticket" which is called from our test scripts
	git clone -b $TAG --single-branch --depth 1 $OSTICKET_REPO $CHECKOUT_FOLDER
else
	echo "Repository does not have a version of osTicket tagged with that version!"
	exit 1;
fi
