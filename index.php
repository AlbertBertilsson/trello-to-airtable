<html>
  <head>
    <title>Test</title>
  </head>
  <body>
<?php
if (strtolower($_SERVER["HTTP_X_FORWARDED_PROTO"])) != 'https') {
  echo "HTTPS only!";
  http_response_code(403);
  exit(0);
}
/*
echo getenv('test-key');
echo "<br>";
while (list($var,$value) = each ($_SERVER)) {
  echo "$var => $value <br>";
}
*/
?>
  </body>
</html>
