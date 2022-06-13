<?php
  require_once("secrets.php");
  
  print "<h1>Fetching secret from AKEYLESS secret management system at akeyless.io</h1>\n";
  
  function FetchKeylessStatic ($arrNames)
  {
    $AccessID = getenv("KEYLESSID");
    $AccessKey = getenv("KEYLESSKEY");
    $AccessKey = $GLOBALS ["AccessKey"];
    $APIEndpoint = "https://api.akeyless.io";
    $PostData = array();
    $PostData['access-type'] = 'access_key';
    $PostData['access-id'] = "$AccessID";
    $PostData['access-key'] = "$AccessKey";
    $jsonPostData = json_encode($PostData);
    $Service = "/auth";
    $url = $APIEndpoint.$Service;
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $jsonPostData);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_ENCODING , '');
    curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
    curl_setopt($curl, CURLOPT_TIMEOUT, 30);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_HTTP_VERSION , CURL_HTTP_VERSION_1_1);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('accept: application/json','Content-Type: application/json'));
    $response = curl_exec($curl);
    curl_close($curl);
    $arrResponse = json_decode($response, TRUE);
    $token = $arrResponse["token"];
    
    $arrValues = array();
    $Service = "/get-secret-value";
    $url = $APIEndpoint.$Service;
    $PostData = array();
    $PostData["token"] = $token;
    $PostData["names"] = $arrNames;
    $jsonPostData = json_encode($PostData);

    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $jsonPostData);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_ENCODING , '');
    curl_setopt($curl, CURLOPT_MAXREDIRS, 10);
    curl_setopt($curl, CURLOPT_TIMEOUT, 30);
    curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($curl, CURLOPT_HTTP_VERSION , CURL_HTTP_VERSION_1_1);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('accept: application/json','Content-Type: application/json'));
    $response = curl_exec($curl);
    curl_close($curl);
    return json_decode($response, TRUE);
  }

  $arrname = array();

  $arrname[] = "MySecret1";
  $arrname[] = "MyFirstSecret";
  $arrname[] = "/TSC/AnotherTest2";
  $arrname[] = "/Test/MyPathTest";

  $arrSecretValues = FetchKeylessStatic($arrname);

  print "<p>Here are the secret names and corrensponding values</p>\n";
  foreach ($arrSecretValues as $key => $value) 
  {
    print "$key: $value <br>\n";
  }
?>