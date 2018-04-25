<?php 

require '../vendor/autoload.php';

use GuzzleHttp\Client;

$client = new GuzzleHttp\Client(['base_uri' => 'https://www.estatesales.net']);

$headers = [
    'Cache-Control:no-cache'
];

$form_params = [
    'grant_type' => 'refresh_token',
    'refresh_token' => '17d28ad3e7604ce28e9212270584531953056298'
];

// Send a request to https://foo.com/api/test
$response = $client->request('POST', '/token', [ 'headers' => $headers, 'form_params' => $form_params ]);

echo json_decode($response->getBody())->access_token;