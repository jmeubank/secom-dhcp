<?php
class MY_Loader extends CI_Loader {
  function css($path, $return = FALSE) {
    return $this->file('application/views/' . $path . '.css', $return);
  }
  function js($path, $return = FALSE) {
    return $this->file('application/views/' . $path . '.js', $return);
  }
}
