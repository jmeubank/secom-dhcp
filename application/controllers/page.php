<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
use Doctrine\ORM\Query;

class Page extends CI_Controller {
  function config_server() {
    $this->load->view('config_server');
  }

  function config_network($obj_id) {
    $network = $this->doctrine->em->createQuery(
      'SELECT n FROM models\Network n WHERE n.id = ?1'
    )->setParameter(1, $obj_id)
    ->getSingleResult(Query::HYDRATE_ARRAY);
    $this->load->view('config_network', array('network' => $network));
  }

  function config_subnet($obj_id) {
    $subnet = $this->doctrine->em->createQuery(
      'SELECT s FROM models\Subnet s WHERE s.address = ?1'
    )->setParameter(1, $obj_id . '%')
    ->getSingleResult(Query::HYDRATE_ARRAY);
    $this->load->view('config_subnet', array('subnet' => $subnet));
  }

  function config_pool($obj_id) {
    $pool = $this->doctrine->em->createQuery(
      'SELECT p FROM models\Pool p WHERE p.start = ?1'
    )->setParameter(1, $obj_id)
    ->getSingleResult(Query::HYDRATE_ARRAY);
    $this->load->view('config_pool', array('pool' => $pool));
  }

  function config_fixed($obj_id) {
    $fixed = $this->doctrine->em->createQuery(
      'SELECT h FROM models\FixedHost h WHERE h.ip = ?1'
    )->setParameter(1, $obj_id)
    ->getSingleResult(Query::HYDRATE_ARRAY);
    $this->load->view('config_fixed', array('fixed' => $fixed));
  }

  function syslog() {
    $this->load->view('syslog');
  }

  function history() {
    parse_str($_SERVER['QUERY_STRING'], $_GET);
    $this->load->view('history', array(
      'searchby' => $_GET['searchby'],
      'term' => $_GET['term']
    ));
  }
}
