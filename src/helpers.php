<?php

// add a utility function for plucking data
function array_pluck($toPluck = '', $arr = array()) {
  $results = array_map(function($item) use ($toPluck) {
    return $item[$toPluck];
  }, $arr);
  return $results;
}
