<?php




//Get the API Key
$getAPIKey =  callPardotApi('https://pi.pardot.com/api/login/version/3',
    array(
        'email' => getenv('pardotLogin'),
        'password' => getenv('pardotPassword'),
        'user_key' => getenv('pardotUserKey') //available from https://pi.pardot.com/account
    ),
    'POST'
);
print_r($getAPIKey);
$APIKey = $getAPIKey['api_key'];





// Lets look for recent Prospect record changes

$results =  callPardotApi('https://pi.pardot.com/api/prospect/version/3/do/query?',
    array(
        'email' => getenv('pardotLogin'),
        'password' => getenv('pardotPassword'),
        'user_key' => getenv('pardotUserKey'), //available from https://pi.pardot.com/account
	'user_key' => $APIKey, // requested from the server previously
	'last_activity_after' => 'today'
    ),
    'POST'
);
print_r($results);



/**
 * Call the Pardot API and get the --raw-XML-- associative array response back
 *
 * @param string $url the full Pardot API URL to call, e.g. "https://pi.pardot.com/api/prospect/version/3/do/query"
 * @param array $data the data to send to the API - make sure to include your api_key and user_key for authentication
 * @param string $method the HTTP method, one of "GET", "POST", "DELETE"
 * @return string the --raw-XML-- associative array response from the Pardot API
 * @throws Exception if we were unable to contact the Pardot API or something went wrong
 */
function callPardotApi($url, $data, $method = 'GET')
{
    // build out the full url, with the query string attached.
    $queryString = http_build_query($data, null, '&');
    if (strpos($url, '?') !== false) {
        $url = $url . '&' . $queryString;
    } else {
        $url = $url . '?' . $queryString;
    }
echo $url . "\n\n";
    $curl_handle = curl_init($url);

    // wait 5 seconds to connect to the Pardot API, and 30
    // total seconds for everything to complete
    curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($curl_handle, CURLOPT_TIMEOUT, 30);

    // https only, please!
    curl_setopt($curl_handle, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS);

    // ALWAYS verify SSL - this should NEVER be changed. 2 = strict verify
    curl_setopt($curl_handle, CURLOPT_SSL_VERIFYHOST, 2);

    // return the result from the server as the return value of curl_exec instead of echoing it
    curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);

    if (strcasecmp($method, 'POST') === 0) {
        curl_setopt($curl_handle, CURLOPT_POST, true);
    } elseif (strcasecmp($method, 'GET') !== 0) {
        // perhaps a DELETE?
        curl_setopt($curl_handle, CURLOPT_CUSTOMREQUEST, strtoupper($method));
    }

    $pardotApiResponse = curl_exec($curl_handle);
    if ($pardotApiResponse === false) {
        // failure - a timeout or other problem. depending on how you want to handle failures,
        // you may want to modify this code. Some folks might throw an exception here. Some might
        // log the error. May you want to return a value that signifies an error. The choice is yours!

        // let's see what went wrong -- first look at curl
        $humanReadableError = curl_error($curl_handle);

        // you can also get the HTTP response code
        $httpResponseCode = curl_getinfo($curl_handle, CURLINFO_HTTP_CODE);

        // make sure to close your handle before you bug out!
        curl_close($curl_handle);

        throw new Exception("Unable to successfully complete Pardot API call to $url -- curl error: \"".
                                "$humanReadableError\", HTTP response code was: $httpResponseCode");
    }

    // make sure to close your handle before you bug out!
    curl_close($curl_handle);


    // Quick and dirty way of XML -> array

    $xml = simplexml_load_string($pardotApiResponse);
    $json  = json_encode($xml);
    $pardotApiResponseData = json_decode($json, true);

    return $pardotApiResponseData;
}



?>
