<html>
  <head>
    <title>Test</title>
  </head>
  <body>
<?php

$verbose = false;

if (isset($_SERVER["HTTP_X_FORWARDED_PROTO"]))
  if (strtolower($_SERVER["HTTP_X_FORWARDED_PROTO"]) == "https")
    if (isset($_GET["debug"]))
      if ($_GET["debug"] == getenv("debug"))
        $verbose = true;

if (!$verbose) {
  echo "No access!";
  exit(0);
}

$trellowebhooksurl = "https://api.trello.com/1/tokens/" . getenv("trello-token") . "/webhooks";

$hooks = file_get_contents($trellowebhooksurl);

echo $hooks;

?>
</body>
</html>
