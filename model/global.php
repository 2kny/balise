<?php

  function sum_array($array, $column) {
    $sum = 0;
    foreach($array as $entry) {
      $sum += $entry[$column];
    }
    return $sum;
  }
