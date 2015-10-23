<html>
  <head>
    <title>Test</title>
  </head>
  <body>
<?php

$verbose = true;

if (strtolower($_SERVER["HTTP_X_FORWARDED_PROTO"]) != "https") $verbose = false;
if ($_GET["debug"] != getenv("debug")) $verbose = false;


//Get airtable
$airtablelisturl = "https://api.airtable.com/v0/appq4IfZYs9aL2s1e/Incidents?view=Active";
if ($verbose) echo "Call: " . $airtablelisturl . "<br><br>";

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

if ($verbose) echo $atresult . "<br><br>";


//Hard coded status for the trello lists, only active lists are included
$listarr = array(
  "55e5af5a7105ece0bb03d417" => "Open",
  "55e58b8960a27158e41e7898" => "Open",
  "55fc2a94fc78778668ac912e" => "Requires backend fix",
  "55e58b8960a27158e41e789a" => "Fixed in Tealium",
  "55e58b8960a27158e41e789b" => "Waiting for verification",
  );

//Functions for processing card name
function get_cq($name) {
  $matches = array();
  preg_match('/\[CQ([\d]+)\]/', $name, $matches);
  if (isset($matches[1]))
    return "CQ" . $matches[1];

  return "";
}

function get_inc($name) {
  $matches = array();
  preg_match('/\[INC([\d]+)\]/', $name, $matches);
  if (isset($matches[1]))
    return "INC" . $matches[1];

  return "";
}


function get_title($name) {
  $matches = array();
  preg_match('/.*\](.*)/', $name, $matches);
  if (isset($matches[1]))
    if (strlen(trim($matches[1])) > 0)
      return $matches[1];

  return $name;
}

//Get the cards
$trellocardsurl = "https://api.trello.com/1/boards/" . getenv("trello-board") . 
  "/cards?fields=name,idList&key=" . getenv("trello-key") . 
  "&token=" . getenv("trello-token");

$cardsjson = file_get_contents($trellocardsurl);
$cards = json_decode($cardsjson);

//Go through all trello cards and calculate changes
for ($i = 0; $i < count($cards); $i++) {
  if (!empty($listarr[$cards[$i]->{'idList'}])) {
    if ($verbose) echo $cards[$i]->{'id'} . ";" . 
    get_cq($cards[$i]->{'name'}) . ";" .
    get_inc($cards[$i]->{'name'}) . ";" .
    get_title($cards[$i]->{'name'}) . ";" .
    $listarr[$cards[$i]->{'idList'}] . "<br>";
  }
}






// Log to airtable
$airtablelogurl = "https://api.airtable.com/v0/appq4IfZYs9aL2s1e/TrelloImportLog";
if ($verbose) echo "Call: " . $airtablelogurl . "<br><br>";

$ch = curl_init($airtablelogurl);

$atheaders = array( 
    "Authorization: Bearer " . getenv("airtable-key"),
    "Content-type: application/json"
);

$payload = '{"fields": {"Entry": "Integration run!","Time": "' . date('Y-m-d H:i:s') . '"}}';
curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
curl_setopt($ch, CURLOPT_HTTPHEADER, $atheaders);
curl_setopt( $ch, CURLOPT_POSTFIELDS, $payload );

$atresult = curl_exec($ch);

if(curl_errno($ch))
{
  echo "Failed to get airtable! Curl error: " . curl_error($ch);
  http_response_code(200);
  exit(0);
}
curl_close ($ch);

echo '<br><br>Done!';

?>
  </body>
</html>
