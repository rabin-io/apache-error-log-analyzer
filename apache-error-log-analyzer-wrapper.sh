#!/bin/bash

MAILTO=webmaster
#MAILTO=root@localhost

HOSTNAME=`hostname -f`
DATE=$(date +%F_%T) # 2013-05-27_09:51:57
SUBJECT="[Apache error Log Analyzer][$DATE] - $HOSTNAME"

DEFAULT_LOG_FILE='/var/log/apache2/error.log'
[[ -z $1 ]] && LOGFILE=$DEFAULT_LOG_FILE || LOGFILE=$1
REPORT_NAME=$(basename $LOGFILE)
TMP_DIR='/tmp'

TMP_FILE="$TMP_DIR/$REPORT_NAME-$DATE.xml"
LAST_REPORT=$(find $TMP_DIR -maxdepth 1 -name "$REPORT_NAME-*.bz2" -print | sort -r | head -1)

#set -x

if [[ -f $LOGFILE ]] 
then
        ERROR_MSG=$( { /usr/local/sbin/apache-error-log-analizer.php $LOGFILE | xmllint --format - > $TMP_FILE; } 2>&1 )
        
        cd $TMP_DIR
        #echo -e "comparing last log : $LAST_REPORT"
        #echo -e "with the file      : $REPORT_NAME-$DATE.xml" 
        
        RET=0
        if [ -z $LAST_REPORT ];
        then
                echo "No old file to comper to"
        else
                bzdiff -q $LAST_REPORT $REPORT_NAME-$DATE.xml > /dev/null
                RET=$?

                if [[ $RET == 0 ]] 
                then
                        #echo processing the same file, removing the tmp file and exit
                        rm -f $TMP_FILE
                        exit 0
                fi

        fi

        bzip2 -9 $TMP_FILE
        LL=$(ls -l $LOGFILE)
        INFO="Debug info:
===============================================================================
All parameters: $@
Processed file: $LOGFILE

$LL

===============================================================================

"
        if [ -z $ERROR_MSG ] 
        then
                ERROR_MSG="Error Messages: no errors =) "       
        else
                ERROR_MSG="Error Messages:\n\n$ERROR_MSG"
        fi

        echo -e "$INFO $ERROR_MSG" | mailx -s "${SUBJECT}" -a $TMP_FILE.bz2 $MAILTO
else
        echo "Cant open file: $LOGFILE" | mailx -s "error - ${SUBJECT}" $MAILTO
        exit 1
fi

