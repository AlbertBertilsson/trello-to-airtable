<html>
  <head>
    <title>Test</title>
  </head>
  <body>
<?php
echo getenv('test-key');
echo "<br>";
while (list($var,$value) = each ($_SERVER)) {
  echo "$var => $value <br />";
}
?>
  </body>
</html>
