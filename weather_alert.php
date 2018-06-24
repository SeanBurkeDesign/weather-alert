<?php

// Private API Key
require_once 'config.php';

// Set the API request variables
$latitude = 42.259422;
$longitude = -70.910338;
$excludes = 'currently,minutely,hourly,alerts,flags';

// Build the request query
$request = "https://api.darksky.net/forecast/$key/$latitude,$longitude?exclude=$excludes";

// Set the curl variable
$curl = curl_init($request);

// Set the curl options
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

// Execute the curl request
$curl_response = curl_exec($curl);

// Show the info if the request fails
if ($curl_response === false) {
  $info = curl_getinfo($curl);
  curl_close($curl);
  die('Error occured during curl execution. Additional info: ' . var_export($info));
}

// Close connection
curl_close($curl);

// Convert the response into an object
$apiResponse = json_decode($curl_response);

// Show an error if the decoding fails
if (isset($apiResponse->response->status) && $apiResponse->response->status == 'ERROR') {
  die('Error occured: ' . $apiResponse->response->errormessage);
}

// Get to the correct array for daily data
$forecast = $apiResponse->daily->data;

// Loop through the daily array to get date and low temperature
foreach ($forecast as $day) {
  $date = date("F j", $day->time);
  $tempMin = $day->temperatureMin;
  $dayArray = array('Date' => $date, 'Temp' => $tempMin);
  $dailyForecast[] = $dayArray;
}

// Sort the array so that lowest temp is always position 0
usort($dailyForecast, function($a, $b) {
  return $a['Temp'] <=> $b['Temp'];
});

// Values from the sorted array
$lowTemp = round($dailyForecast[0]['Temp']);
$lowTempDate = $dailyForecast[0]['Date'];

// Email alert if temp drops below freezing
if ($lowTemp <= 32) {
  $to = 'sean@seanburkedesign.com';
  $subject = 'Daily Temperature Forecast';
  $message = "Heads up! The lowest temperature for the next 7 days is $lowTemp degrees on $lowTempDate. You should probably turn the water off!";
  mail($to, $subject, $message);

} else {
  $to = 'sean@seanburkedesign.com';
  $subject = 'Daily Temperature Forecast';
  $message = "All good! The lowest temperature for the next 7 days is $lowTemp degrees on $lowTempDate. You probably don't need to worry about turning the water off!";
  mail($to, $subject, $message);
}

?>
