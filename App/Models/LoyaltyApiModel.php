<?php

namespace App\Models;

/**
 * model to interact with the node.js loyalty api
 */
class LoyaltyApiModel {
    /**
     * @var string the base url of the node.js api (adjust port if needed)
     */
    private $apiUrl = 'http://localhost:3000/api/player';

    /**
     * fetches the total loyalty points for a given customer from node.js
     *
     * @param string $loyaltyId the unique loyalty identifier
     * @return int the available points balance
     */
    public function getPoints(string $loyaltyId): int {
        // initialize curl session
        $ch = curl_init();
        
        // build the url with the customer loyalty id
        $url = $this->apiUrl . '/' . urlencode($loyaltyId) . '/points';
        
        // configure curl options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5); // 5 seconds timeout
        
        // execute the request
        $response = curl_exec($ch);
        
        // check if there was a curl network error
        if (curl_errno($ch)) {
            // close curl and safely return 0
            curl_close($ch);
            return 0;
        }
        
        // get the http response code
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        // check if the api responded successfully
        if ($httpCode === 200 && $response) {
            // decode the json response into an array
            $data = json_decode($response, true);
            
            // verify if points key exists in response
            if (isset($data['points'])) {
                // return the integer value of points
                return (int)$data['points'];
            }
        }
        
        // fallback to 0 if anything fails
        return 0;
    }

    /**
     * consumes loyalty points from the node.js api
     *
     * @param string $loyaltyId the unique loyalty identifier
     * @param int $points the number of points to consume
     * @return bool true if successfully consumed, false otherwise
     */
    public function consumePoints(string $loyaltyId, int $points): bool {
        // initialize curl session
        $ch = curl_init();
        
        // build the url to consume points
        $url = $this->apiUrl . '/' . urlencode($loyaltyId) . '/consume';
        
        // prepare json payload
        $payload = json_encode(['pointsToConsume' => $points]);

        // configure curl options
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

        // execute the request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // return true if the api responds with a 200 ok
        return ($httpCode === 200);
    }
}