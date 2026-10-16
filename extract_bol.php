<?php
/**
 * Extract structured data from bills of lading using the Photon Commerce API.
 *
 * Submits a bill of lading (PDF or image) and returns shipper/consignee details,
 * port routing, container data, and risk scores including BOL number, vessel,
 * voyage, loading/discharge ports, and full container specifications.
 *
 * Processing times (Managed Agents):
 *   Trial accounts:  up to 24 hours
 *   Production:      5 minutes to 24 hours
 *
 * AI extraction (seconds, no Managed Agents):
 *   Contact support@photoncommerce.com to activate.
 *   Once active, submit to /api/v4 instead of /api/pro.
 *
 * Requires: composer require guzzlehttp/guzzle
 *
 * Docs:    https://apidocs.photoncommerce.com
 * Sandbox: https://sandbox-api.photoncommerce.com/api/v4/register (20 free calls)
 */

require 'vendor/autoload.php';

use GuzzleHttp\Client;

const CLIENT_ID  = 'YOUR_CLIENT_ID';
const USERNAME   = 'YOUR_USERNAME';
const API_KEY    = 'YOUR_API_KEY';
const PASSWORD   = 'YOUR_PASSWORD';
const SECRET_KEY = 'YOUR_SECRET_KEY';

// Sandbox: https://sandbox-api.photoncommerce.com  (20 free calls, no card needed)
// Production: https://api.photoncommerce.com
const BASE_URL = 'https://sandbox-api.photoncommerce.com';

$client = new Client(['timeout' => 30]);

$authHeaders = [
    'CLIENT-ID'     => CLIENT_ID,
    'AUTHORIZATION' => 'apikey ' . USERNAME . ':' . API_KEY,
    'PASSWORD'      => PASSWORD,
    'SECRET-KEY'    => SECRET_KEY,
];

function submitBol(
    Client $client, array $headers,
    ?string $filePath = null, ?string $url = null,
    ?string $webhookUrl = null, ?string $authToken = null,
    ?string $id = null, ?string $subaccount = null,
    ?int $pageStart = null, ?int $pageEnd = null
): string {
    if (!$filePath && !$url) throw new \InvalidArgumentException('Provide either filePath or url.');

    $query = ['doctype' => 'bol'];
    if ($url)        $query['url']         = $url;
    if ($webhookUrl) $query['webhook_url'] = $webhookUrl;
    if ($authToken)  $query['auth_token']  = $authToken;
    if ($id)         $query['ID']          = $id;
    if ($subaccount) $query['subaccount']  = $subaccount;
    if ($pageStart)  $query['page_start']  = $pageStart;
    if ($pageEnd)    $query['page_end']    = $pageEnd;

    // For AI extraction (seconds), replace /api/pro with /api/v4 — contact support@photoncommerce.com to activate.
    $options = ['headers' => $headers, 'query' => $query];
    if ($filePath) {
        $options['multipart'] = [['name' => 'pdf', 'contents' => fopen($filePath, 'r'), 'filename' => basename($filePath)]];
    }

    $response = $client->post(BASE_URL . '/api/pro', $options);
    return json_decode($response->getBody(), true)['photon_key'];
}

function fetchResult(Client $client, array $headers, string $photonKey): array {
    $response = $client->get(BASE_URL . '/api/v4/json', [
        'headers' => $headers,
        'query'   => ['photon_key' => $photonKey],
    ]);
    $data = json_decode($response->getBody(), true);
    return $data['data'] ?? [];
}

function waitForResult(Client $client, array $headers, string $photonKey, int $pollInterval = 20, int $timeout = 3600): array {
    $deadline = time() + $timeout;
    while (time() < $deadline) {
        $result = fetchResult($client, $headers, $photonKey);
        $status = $result['Status'] ?? '';
        if ($status && $status !== 'pending' && $status !== 'processing') return $result;
        echo "  Status: " . ($status ?: 'pending') . " — retrying in {$pollInterval}s...\n";
        sleep($pollInterval);
    }
    throw new \RuntimeException("Extraction not complete after {$timeout}s");
}

// --- Option A: submit from a local file ---
$photonKey = submitBol($client, $authHeaders, filePath: 'bill_of_lading.pdf');

// --- Option B: submit via a publicly accessible URL ---
// $photonKey = submitBol($client, $authHeaders, url: 'https://example.com/bill_of_lading.pdf');

echo "Submitted. photon_key: {$photonKey}\n";
echo "Waiting for extraction to complete...\n";

$result = waitForResult($client, $authHeaders, $photonKey);

echo "\n--- Bill of Lading Data ---\n";
echo "BOL Number:         " . ($result['BOL_Number']              ?? '') . "\n";
echo "Booking Number:     " . ($result['Booking_Number']          ?? '') . "\n";
echo "Shipper:            " . ($result['Shipper']                 ?? '') . "\n";
echo "Consignee:          " . ($result['Consignee']               ?? '') . "\n";
echo "Notify Party:       " . ($result['Notify_Party']            ?? '') . "\n";
echo "Vessel:             " . ($result['Vessel']                  ?? '') . "\n";
echo "Voyage:             " . ($result['Voyage']                  ?? '') . "\n";
echo "Loading Port:       " . ($result['Loading_Port']            ?? '') . "\n";
echo "Discharge Port:     " . ($result['Discharge_Port']          ?? '') . "\n";
echo "Place of Receipt:   " . ($result['Receipt_Address']         ?? '') . "\n";
echo "Place of Delivery:  " . ($result['Delivery_Address']        ?? '') . "\n";
echo "Total Quantity:     " . ($result['Total_Quantity'] ?? '') . " " . ($result['Total_Quantity_UOM'] ?? '') . "\n";
echo "Total Measurement:  " . ($result['Total_Measurement'] ?? '') . " " . ($result['Total_Measurement_UOM'] ?? '') . "\n";
echo "Fraud Score:        " . ($result['Fraud_Score']             ?? '') . "\n";
echo "Risk Score:         " . ($result['Risk_Score']              ?? '') . "\n";
echo "Anomaly Score:      " . ($result['Anomaly_Score']           ?? '') . "\n";

$containers = $result['Containers'] ?? [];
if (count($containers) > 0) {
    echo "\n--- Containers (" . count($containers) . ") ---\n";
    foreach ($containers as $c) {
        printf("  %-14s  Seal: %-12s  Type: %-4s  %s %s  %s %s\n",
            $c['Container_Number'] ?? '', $c['Seal_Number'] ?? '', $c['Type_and_Size'] ?? '',
            $c['Weight'] ?? '', $c['Weight_UOM'] ?? '',
            $c['Measurement'] ?? '', $c['Measurement_UOM'] ?? '');
    }
}
