#!/bin/bash
#
# Manually run this to sync remote sites with latest SVN version of Onnac. I run this on
# several production sites myself. I generally keep SVN stable, but obviously there are no
# guarantees provided -- which is why the revision parameter must be specified, to ensure you
# know which revision you are installing, which you already tested to ensure it worked :)
#
# Install:
#	Change ONNACSVN and ONNACEXP at the beginning of the script
#	Change the update_site function call at the end of the script
#
# Requires lftp and svn to be installed
#	lftp: http://lftp.yar.ru/
#	svn: http://subversion.tigris.org/

# change these to be a directory you choose
ONNACSVN=/home/onnac/trunk/
ONNACEXP=/home/onnac/export


if [ "$1" == "" ]; then
    echo "Usage: $0 <revision>"
    echo "Revision may be specified as a number or HEAD"
    exit
else
    REVISION="$1"
fi

function update_site {

	USER=$1
	PASS=$2
	SITE=$3
	NOSSL=$4
	FILE=`mktemp`

	echo
	echo "Updating $SITE..."
	echo

	chmod 600 $FILE

	# dynamically create script for lftp, better than passing on
	# command line, since other users can see that
	if [ "$NOSSL" == "yes" ]; then 
		echo "set ftp:ssl-allow false" >> $FILE; 
	fi
	echo "open -u $USER,$PASS $SITE" >> $FILE
	echo "mirror -R -vv --ignore-size" >> $FILE 
			
	cd $ONNACEXP
	lftp -f $FILE

	rm $FILE

	echo
	echo "$SITE completed!"
	echo
}

function error_exit {
    echo "$1"
    exit 1
}

if [ -d "$ONNAC_EXP" ]; then
    rm -rf $ONNACEXP
fi

(svn update --revision $REVISION $ONNACSVN && svn export $ONNACSVN $ONNACEXP/) || error_exit "There was an error updating from SVN"

# delete unnecessary stuff
rm -rf $ONNACEXP/Documentation $ONNACEXP/install

# update_site [username] [password] [domain of ftp site] [disable SSL (needed for some old/broken ftp software)]
update_site my_username my_password my_domain.com no

