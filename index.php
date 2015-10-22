<html>
  <head>
    <title>Test</title>
  </head>
  <body>
<?php

if (strtolower($_SERVER["HTTP_X_FORWARDED_PROTO"]) != "https") {
  echo "HTTPS only!";
  http_response_code(403);
  exit(0);
}

echo "HTTPS good!<br>";


if ($_GET["debug"] != getenv("debug")) {
  echo "Debug parameter required!";
  http_response_code(403);
  exit(0);
}

echo "Debug paramater good!<br><br>";


$trellolistsurl = "https://api.trello.com/1/boards/" . getenv("trello-board") . 
  "?lists=open&list_fields=name&fields=name,desc&key=" . getenv("trello-key") . 
  "&token=" . getenv("trello-token");

echo "Call: " . $trellolistsurl . "<br><br>";

$listsjson = file_get_contents($trellolistsurl);
//echo $listsjson . "<br><br>";
$lists = json_decode($listsjson);
$listarr = array();
for ($i = 0; $i < count($lists->{'lists'}); $i++) {
  $listarr[$lists->{'lists'}[$i]->{'id'}] = $lists->{'lists'}[$i]->{'name'};
  //echo "Lists: " . $lists->{'lists'}[$i]->{'id'} . " " . $lists->{'lists'}[$i]->{'name'} . "<br>";
}
//echo var_dump($listarr);
//echo "<br><br>";


$trellocardsurl = "https://api.trello.com/1/boards/" . getenv("trello-board") . 
  "/cards?fields=name,idList&key=" . getenv("trello-key") . 
  "&token=" . getenv("trello-token");

echo "Call: " . $trellocardsurl . "<br><br>";

$cardsjson = file_get_contents($trellocardsurl);
echo $cardsjson . "<br><br>";
$cards = json_decode($cardsjson);
for ($i = 0; $i < count($cards); $i++) {
  echo "Card: " . $cards[$i]->{'id'} . " " . 
  $cards[$i]->{'name'} . " " . 
  $cards[$i]->{'idList'} . " " . 
  $listarr[$cards[$i]->{'id'}] . "<br>";
}


?>
  </body>
</html>

