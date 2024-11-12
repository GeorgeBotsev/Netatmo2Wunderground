<?php
// Netatmo API credentials
define('NETATMO_CLIENT_ID', 'DEFINE CLIENT ID HERE');
define('NETATMO_CLIENT_SECRET', 'DEFINE CLIENT SECRET HERE');

// Wunderground station credentials
define('WUNDERGROUND_STATION_ID', 'DEFINE WUNDERGROUND STATION HERE');
define('WUNDERGROUND_API_KEY', 'DEFINE WUNDERGROUND API KEY HERE');

define('REDIRECT_URI', 'FULL PATH TO THIS FILE ON YOUR SERVER'); // Change this to your actual URI

// Path to save the refresh token
define('TOKEN_FILE', 'netatmo_tokens.json');

// Function to get the initial access token and refresh token using authorization code
function getAccessToken($authorizationCode) {
    $url = "https://api.netatmo.com/oauth2/token";
    $data = array(
        'grant_type' => 'authorization_code',
        'client_id' => NETATMO_CLIENT_ID,
        'client_secret' => NETATMO_CLIENT_SECRET,
        'redirect_uri' => REDIRECT_URI,
        'code' => $authorizationCode
    );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));

    $result = curl_exec($ch);
    $response = json_decode($result, true);
    curl_close($ch);

    if (isset($response['access_token']) && isset($response['refresh_token'])) {
        file_put_contents(TOKEN_FILE, json_encode([
            'access_token' => $response['access_token'],
            'refresh_token' => $response['refresh_token'],
            'expires_in' => time() + $response['expires_in']
        ]));
        return $response['access_token'];
    } else {
        echo "Failed to obtain access token.\n";
        print_r($response);
        return null;
    }
}

// Function to refresh the access token using the refresh token
function refreshAccessToken() {
    if (!file_exists(TOKEN_FILE)) {
        echo "Authorization code required. Please generate it first.\n";
        return null;
    }

    $tokens = json_decode(file_get_contents(TOKEN_FILE), true);
    if (time() < $tokens['expires_in']) {
        return $tokens['access_token'];
    }

    $url = "https://api.netatmo.com/oauth2/token";
    $data = array(
        'grant_type' => 'refresh_token',
        'client_id' => NETATMO_CLIENT_ID,
        'client_secret' => NETATMO_CLIENT_SECRET,
        'refresh_token' => $tokens['refresh_token']
    );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));

    $result = curl_exec($ch);
    $response = json_decode($result, true);
    curl_close($ch);

    if (isset($response['access_token'])) {
        file_put_contents(TOKEN_FILE, json_encode([
            'access_token' => $response['access_token'],
            'refresh_token' => $response['refresh_token'],
            'expires_in' => time() + $response['expires_in']
        ]));
        return $response['access_token'];
    } else {
        echo "Failed to refresh access token.\n";
        print_r($response);
        return null;
    }
}

// Function to get Netatmo weather data
function getNetatmoData($accessToken) {
    $url = "https://api.netatmo.com/api/getstationsdata";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Authorization: Bearer $accessToken"));

    $result = curl_exec($ch);
    curl_close($ch);

    return json_decode($result, true);
}

// Function to calculate dew point
function calculateDewPoint($temperature, $humidity) {
    $a = 17.27;
    $b = 237.7;
    $alpha = (($a * $temperature) / ($b + $temperature)) + log($humidity / 100);
    $dewPoint = ($b * $alpha) / ($a - $alpha);
    return $dewPoint;
}

// Function to post data to Wunderground
function postToWunderground($data) {
    $url = "https://weatherstation.wunderground.com/weatherstation/updateweatherstation.php";
    $urlWithParams = $url . '?' . http_build_query($data);
    file_get_contents($urlWithParams);
}

// Main script execution
if (!file_exists(TOKEN_FILE)) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['authorization_code'])) {
        $authorizationCode = $_POST['authorization_code'];
        getAccessToken($authorizationCode);
    } else {
        echo "<p>Authorization required. Open this URL to authorize:</p>";
        echo "<p><a href=\"https://api.netatmo.com/oauth2/authorize?client_id=" . NETATMO_CLIENT_ID . "&redirect_uri=" . urlencode(REDIRECT_URI) . "&scope=read_station&response_type=code\" target=\"_blank\">Authorize Netatmo Application</a></p>";
        echo "<p>After granting access, paste the authorization code below:</p>";
        echo '<form method="POST">
                <label for="authorization_code">Authorization Code:</label>
                <input type="text" id="authorization_code" name="authorization_code" required>
                <button type="submit">Submit</button>
              </form>';
    }
} else {
    $accessToken = refreshAccessToken();
    if ($accessToken) {
        $netatmoData = getNetatmoData($accessToken);

        if ($netatmoData && isset($netatmoData['body']['devices'][0])) {
            $device = $netatmoData['body']['devices'][0];
            $data = [];

            // Indoor pressure (main station)
            if (isset($device['dashboard_data']['Pressure'])) {
                $data['baromin'] = $device['dashboard_data']['Pressure'] * 0.02953; // hPa to inHg
            }

            // Find outdoor and other sensor data
            foreach ($device['modules'] as $module) {
                switch ($module['type']) {
                    case 'NAModule1': // Outdoor Module
                        $data['tempf'] = $module['dashboard_data']['Temperature'] * 9 / 5 + 32; // Celsius to Fahrenheit
                        $data['humidity'] = $module['dashboard_data']['Humidity'];
                        // Calculate dew point
                        $dewPointC = calculateDewPoint($module['dashboard_data']['Temperature'], $module['dashboard_data']['Humidity']);
                        $data['dewptf'] = $dewPointC * 9 / 5 + 32; // Dew point in Fahrenheit
                        break;
                        break;
                    case 'NAModule2': // Wind Module
                        $data['winddir'] = $module['dashboard_data']['WindAngle'];
                        $data['windspeedmph'] = $module['dashboard_data']['WindStrength'] * 0.621371; // km/h to mph
                        $data['windgustmph'] = $module['dashboard_data']['GustStrength'] * 0.621371; // km/h to mph
                        break;
                    case 'NAModule3': // Rain Module
                        $data['rainin'] = $module['dashboard_data']['sum_rain_1'] * 0.0393701; // mm to inches
                        $data['dailyrainin'] = $module['dashboard_data']['sum_rain_24'] * 0.0393701; // mm to inches
                        break;
                }
            }

            // Add fixed Wunderground credentials and date
            $data['ID'] = WUNDERGROUND_STATION_ID;
            $data['PASSWORD'] = WUNDERGROUND_API_KEY;
            $data['dateutc'] = 'now';
            $data['action'] = 'updateraw';

            // Send data to Wunderground
            postToWunderground($data);
            echo "Data successfully posted to Wunderground.\n";
            // Additional code to save data to a JSON file

            // Define the path to the JSON file
            $jsonFile = 'netatmo_data.json';

            // Gather the data to save to JSON
            $additionalData = [
                        'temperature' => isset($data['tempf']) ? ($data['tempf'] - 32) * 5 / 9 : null, // Convert Fahrenheit back to Celsius for consistency
                        'humidity' => $data['humidity'] ?? null,
                        'pressure' => isset($data['baromin']) ? $data['baromin'] / 0.02953 : null, // Convert inHg back to hPa
                        'dew_point' => isset($data['dewptf']) ? ($data['dewptf'] - 32) * 5 / 9 : null, // Convert Fahrenheit back to Celsius
                        'wind_direction' => $data['winddir'] ?? null,
                        'wind_speed' => isset($data['windspeedmph']) ? $data['windspeedmph'] / 0.621371 : null, // Convert mph to km/h
                        'wind_gust' => isset($data['windgustmph']) ? $data['windgustmph'] / 0.621371 : null, // Convert mph to km/h
                        'precipitation' => isset($data['rainin']) ? $data['rainin'] / 0.0393701 : null, // Convert inches to mm
                        'accumulated_precipitation' => isset($data['dailyrainin']) ? $data['dailyrainin'] / 0.0393701 : null, // Convert inches to mm
                        'timestamp' => time(),
            ];
        
            // Load existing data from the JSON file, if it exists
            if (file_exists($jsonFile)) {
                        $existingData = json_decode(file_get_contents($jsonFile), true);
            } else {
                        $existingData = [];
            }
        
            // Append the new data entry to the existing array
            $existingData[] = $additionalData;
        
            // Save the updated data back to the JSON file
            file_put_contents($jsonFile, json_encode($existingData, JSON_PRETTY_PRINT));
        
            echo "Data also saved locally to JSON file.\n";
        } else {
            echo "Failed to retrieve data from Netatmo.\n";
        }
    } else {
        echo "Failed to obtain or refresh access token.\n";
    }
}
