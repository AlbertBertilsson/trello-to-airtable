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

  $trellohookurl = "https://api.trello.com/1/webhooks/" .
  "?callbackURL=http://iai-test.herokuapp.com&idModel=" . getenv("trello-board") . 
    "&key=" . getenv("trello-key") . 
    "&token=" . getenv("trello-token");

  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
  curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');

  $tresult = curl_exec($ch);
  echo $tresult;

  if(curl_errno($ch)) {
    echo "Failed to add webhook! Curl error: " . curl_error($ch);
  }
  curl_close ($ch);

?>
</body>
</html>
