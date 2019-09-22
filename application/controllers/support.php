<?php
class Support extends CI_Controller {
  function js($filename) {
    $this->load->js($filename);
  }
  function staticfile($filename) {
    $this->load->file('static/' . $filename);
  }
}
