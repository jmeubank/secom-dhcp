<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Home extends CI_Controller {
  function index() {
    $c = $this->doctrine->em->createQuery(
      'SELECT c.changed FROM models\DbChanged c'
    )->getSingleResult();
    $this->load->view('home', array('dbchanged' => $c['changed']));
  }
}
