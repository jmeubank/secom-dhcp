<?php

function jsonize_success($output_data) {
  $output_data['success'] = true;
  echo json_encode($output_data);
}
function iframe_jsonize_success($output_data) {
  $output_data['success'] = true;
  echo '<html><body><textarea>' . json_encode($output_data) .
  '</textarea></body></html>';
}

function jsonize_error($error_msg) {
  echo json_encode(array('success' => false, 'error' => $error_msg));
}
function iframe_jsonize_error($error_msg) {
  echo '<html><body><textarea>' .
  json_encode(array('success' => false, 'error' => $error_msg))
  . '</textarea></body></html>';
}
