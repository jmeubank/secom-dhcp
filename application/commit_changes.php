<?php
require_once('application/helpers/http_helper.php');

function publish_status($status, $message) {
  http2_post_fields("http://rt.dhcp.secom.net:8003/", array(
    'channel_name' => '/tasks',
    'payload' => json_encode(array(
      'status' => $status,
      'message' => (string)$message
    ))
  ));
}

$p = popen('sudo -n TERM=dumb /var/www/dhcpd-extensions/dhcp_check_change 2>&1', 'r');
if ($p === FALSE) {
  publish_status(2, 'Failed to run config change script');
  exit(1);
}
while (!feof($p) && ($line = fgets($p))) {
  if (rtrim($line) != '')
    publish_status(0, rtrim($line));
}
$ret = pclose($p);
if ($ret !== 0) {
  publish_status(2, '(Process ended with status code ' . $ret . ')');
  exit($ret);
}
publish_status(1, 'Done.');
mysql_connect('', '', '');
mysql_select_db('');
mysql_query('UPDATE DbChanged SET changed = 0 WHERE 1;');
http2_post_fields("http://rt.dhcp.secom.net:8003/", array(
  'channel_name' => '/base',
  'payload' => json_encode(array('dbchanged' => FALSE))
));
exit(0);
