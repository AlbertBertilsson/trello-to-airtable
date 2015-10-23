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

/*
//Get airtable
$airtablelisturl = "https://api.airtable.com/v0/appq4IfZYs9aL2s1e/Incident?view=Active";
echo "Call: " . $airtablelisturl . "<br><br>";

$ch = curl_init($airtablelisturl);

$atheaders = array( 
    "Authorization: Bearer " . getenv("airtable-key")
);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($ch, CURLOPT_HTTPHEADER, $atheaders);


$atresult = curl_exec($ch);

if(curl_errno($ch))
{
  echo "Failed to get airtable! Curl error: " . curl_error($ch);
  http_response_code(200);
  exit(0);
}
curl_close ($ch);

echo $atresult . "<br><br>";
*/

/*
//We are not using the definition of the trello lists,
//hard coded them instead because there is not a one to one mapping anyway.
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
*/


//Hard coded status for the trello lists
$listarr = array(
  "55e58b8960a27158e41e7897" => "Meta data (ignore)",
  "55e5af5a7105ece0bb03d417" => "Open",
  "55e58b8960a27158e41e7898" => "Open",
  "55fc2a94fc78778668ac912e" => "Requires backend fix",
  "55e58b8960a27158e41e789a" => "Fixed in Tealium",
  "55e58b8960a27158e41e789b" => "Waiting for verification",
  "55e5ab9aa12abb4548ba60f9" => "Closed",
  "55e6b509ff8d0e999ce55d64" => "Closed",
  );

//Functions for processing card name
function get_cq($name) {
  $matches = array();
  preg_match('/\[CQ([\d]+)\]/', $name, $matches);
  if (isset($matches[1]))
    return "CQ" . $matches[1];

  return "";
}

//Get the cards
$trellocardsurl = "https://api.trello.com/1/boards/" . getenv("trello-board") . 
  "/cards?fields=name,idList&key=" . getenv("trello-key") . 
  "&token=" . getenv("trello-token");

echo "Call: " . $trellocardsurl . "<br><br>";

$cardsjson = file_get_contents($trellocardsurl);
//echo $cardsjson . "<br><br>";
$cards = json_decode($cardsjson);
for ($i = 0; $i < count($cards); $i++) {
  echo "Card: " . $cards[$i]->{'id'} . " " . 
  $cards[$i]->{'name'} . "<br>" .
  get_cq($cards[$i]->{'name'}) . "<br>" .
  $cards[$i]->{'idList'} . " " . 
  $listarr[$cards[$i]->{'idList'}] . "<br><br>";
}


?>
  </body>
</html>

