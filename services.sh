#!/bin/sh

. /lib/lsb/init-functions

echo "SECOM DHCP server -- $1"

if [ "x$2" = "xrealtime" -o "x$2" = "xall" ]; then

PATH=/sbin:/usr/sbin:/bin:/usr/bin:/usr/local/bin

  if [ "x$1" = "xstart" ]; then

echo -n "Starting realtime server: "
start-stop-daemon --start --quiet --oknodo --pidfile /var/www/secom-dhcp/realtime/realtime.pid \
 --exec /usr/bin/node -- /var/www/secom-dhcp/realtime/app.js
if [ $? != 0 ]; then
  echo "Error!"
  exit $?
fi
echo "Done."

  elif [ "x$1" = "xstop" ]; then

echo -n "Stopping realtime server: "
if [ -f /var/www/secom-dhcp/realtime/realtime.pid ]; then
start-stop-daemon --stop --oknodo --quiet --pidfile /var/www/secom-dhcp/realtime/realtime.pid \
 && rm /var/www/secom-dhcp/realtime/realtime.pid
fi
echo "Done."

  fi
fi

echo "SECOM DHCP server -- done with $1"

