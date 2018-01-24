
#!/bin/bash
#
# tomcat
#
# chkconfig: 345 96 30
# description:  Start up the Tomcat servlet engine.
#
# processname: java
# pidfile: /var/run/tomcat.pid
#
### BEGIN INIT INFO
# Provides: tomcat
# Required-Start: $network $syslog
# Required-Stop: $network $syslog
# Should-Start: distcache
# Short-Description: start and stop Apache HTTP Server
# Description: implementation for Servlet 2.5 and JSP 2.1
## END INIT INFO

# Source function library.
. /etc/init.d/functions

## tomcat installation directory
PROCESS_NAME=tomcat-servicename

CATALINA_HOME="/opt/tomcat"

## run as a diffent user
TOMCAT_USER=tomcat

##  Path to the pid, runnning info file
pidfile=${PIDFILE-/var/run/${PROCESS_NAME}.pid};
lockfile=${LOCKFILE-/var/lock/subsys/${PROCESS_NAME}};

RETVAL=0

case "$1" in
 start)
        PID=`pidofproc -p ${pidfile} ${PROCESS_NAME}`
        if [[ (-n ${PID}) && ($PID -gt 0) ]]; then
                logger -s "${PROCESS_NAME}(pid ${PID}) is  already running."
                exit;
        fi
        if [ -f $CATALINA_HOME/bin/startup.sh ];
          then
            logger -s "Starting Tomcat"
            /bin/su -l ${TOMCAT_USER} -c "$CATALINA_HOME/bin/startup.sh -Dprocessname=${PROCESS_NAME}"
            PID=`ps -eaf|grep processname=${PROCESS_NAME}|grep -v grep|awk '{print $2}'`
            RETVAL=$?
            [ $RETVAL = 0 ] && touch ${lockfile}
            [ $RETVAL = 0 ] && echo "${PID}" > ${pidfile}
        fi
        ;;
 stop)
        PID=`pidofproc -p ${pidfile} ${PROCESS_NAME}`
        ## if PID valid run shutdown.sh
        if [[ -z ${PID} ]];then
            logger -s "${PROCESS_NAME} is not running."
            exit;
        fi

        if [[ (${PID} -gt 0) && (-f $CATALINA_HOME/bin/shutdown.sh) ]];
          then
            logger -s "Stopping Tomcat"
            /bin/su -l ${TOMCAT_USER} -c "$CATALINA_HOME/bin/shutdown.sh"
            RETVAL=$?
            [ $RETVAL = 0 ] && rm -f ${lockfile}
            [ $RETVAL = 0 ] && rm -f ${pidfile}
        fi
        ;;
 status)
        status -p ${pidfile} ${PROCESS_NAME}
        RETVAL=$?
        ;;
 restart)
         $0 stop
         $0 start
         ;;
version)
        if [ -f $CATALINA_HOME/bin/version.sh ];
          then
            logger -s "Display Tomcat Version"
            /bin/su -l ${TOMCAT_USER} -c "$CATALINA_HOME/bin/version.sh"
            RETVAL=$?
        fi
        ;;
 *)
         echo $"Usage: $0 {start|stop|restart|status|version}"
        exit 1
        ;;
esac
exit $RETVAL  