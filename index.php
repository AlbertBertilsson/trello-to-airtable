<html>
  <head>
    <title>Test</title>
  </head>
  <body>
<?php

$verbose = false;
$local = false;

if (isset($_SERVER["HTTP_X_FORWARDED_PROTO"]))
  if (strtolower($_SERVER["HTTP_X_FORWARDED_PROTO"]) != "https")
    if (isset($_GET["debug"]))
      if ($_GET["debug"] != getenv("debug")) $verbose = true;


var_dump($_SERVER);
exit(0);
