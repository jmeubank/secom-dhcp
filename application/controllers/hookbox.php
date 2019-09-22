<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Hookbox extends CI_Controller {
  function connect() {
    parse_str($_SERVER['QUERY_STRING'], $_GET);
    return $this->hbok(array('name' => 'hookbox_dhcp_client'));
  }
  function create_channel() {
    parse_str($_SERVER['QUERY_STRING'], $_GET);
    $cname = $this->validate_channel();
    if (!$cname)
      return $this->hberror("Invalid channel");
    if ($cname === 'syslog')
      return $this->hbok(array('history_size' => 30));
    else
      return $this->hbok();
  }
  function subscribe() {
    parse_str($_SERVER['QUERY_STRING'], $_GET);
    if (!$this->validate_channel())
      return $this->hberror("Invalid channel");
    return $this->hbok();
  }
  function publish() {
    parse_str($_SERVER['QUERY_STRING'], $_GET);
    if (!$this->validate_channel())
      return $this->hberror("Invalid channel");
    return $this->hbok();
  }
  function unsubscribe() {
    parse_str($_SERVER['QUERY_STRING'], $_GET);
    return $this->hbok();
  }
  function disconnect() {
    parse_str($_SERVER['QUERY_STRING'], $_GET);
    return $this->hbok();
  }

  function hbok($params = NULL) {
    echo "[true," . (($params === NULL) ? '{}' : json_encode($params)) . "]";
    return;
  }
  function hberror($error) {
    echo json_encode(array(FALSE, array('error' => $error)));
  }
  function validate_channel() {
    $cname = $this->input->post('channel_name');
    if (!$cname)
      return FALSE;
    if ($cname == 'base'
    || $cname == 'lease'
    || $cname == 'syslog'
    || $cname == 'tasks')
      return $cname;
    return FALSE;
  }
}
