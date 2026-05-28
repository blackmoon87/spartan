<?php

declare(strict_types=1);

/**
 * Spartan Stress & Performance Tester (Headless / CLI)
 */

if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.\n");
}

$targetUrl = 'http://localhost:8085/';
$totalRequests = 100;
$concurrency = 10;

// Parse arguments if provided
if (isset($argv[1])) {
    $targetUrl = $argv[1];
}
if (isset($argv[2])) {
    $totalRequests = (int) $argv[2];
}
if (isset($argv[3])) {
    $concurrency = (int) $argv[3];
}

echo "\n";
echo "==================================================\n";
echo "       SPARTAN STRESS & PERFORMANCE TESTER       \n";
echo "==================================================\n";
echo "Target URL:      $targetUrl\n";
echo "Total Requests:  $totalRequests\n";
echo "Concurrency:     $concurrency\n";
echo "--------------------------------------------------\n";
echo "Running test... Please wait...\n";

$startTime = microtime(true);

$successCount = 0;
$errorCount = 0;
$rateLimitCount = 0;
$latencies = [];

$mh = curl_multi_init();
$activeRequests = [];
$requestsSent = 0;

function createCurlHandle(string $url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_USERAGENT, 'SpartanStressTester/1.0');
    
    if (str_contains($url, '/search/posts')) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['query' => 'welcome']));
    }
    
    if (str_contains($url, '/login')) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['email' => 'author@mail.com', 'password' => 'password123']));
    }
    
    return $ch;
}

// Start executing requests
while ($requestsSent < $totalRequests || count($activeRequests) > 0) {
    // Fill up active queue to concurrency limit
    while (count($activeRequests) < $concurrency && $requestsSent < $totalRequests) {
        $ch = createCurlHandle($targetUrl);
        curl_multi_add_handle($mh, $ch);
        $activeRequests[(int)$ch] = [
            'handle' => $ch,
            'start' => microtime(true)
        ];
        $requestsSent++;
    }

    // Execute handles
    do {
        $status = curl_multi_exec($mh, $active);
    } while ($status === CURLM_CALL_MULTI_PERFORM);

    if ($status !== CURLM_OK) {
        break;
    }

    // Read responses
    while ($info = curl_multi_info_read($mh)) {
        $ch = $info['handle'];
        $chId = (int)$ch;
        
        if (isset($activeRequests[$chId])) {
            $latency = microtime(true) - $activeRequests[$chId]['start'];
            $latencies[] = $latency;

            $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($statusCode === 200) {
                $successCount++;
            } elseif ($statusCode === 429) {
                $rateLimitCount++;
            } else {
                $errorCount++;
            }

            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
            unset($activeRequests[$chId]);
        }
    }

    // Wait for activity
    if ($active) {
        curl_multi_select($mh, 0.1);
    }
}

curl_multi_close($mh);

$endTime = microtime(true);
$totalTime = $endTime - $startTime;

$avgLatency = count($latencies) > 0 ? (array_sum($latencies) / count($latencies)) * 1000 : 0;
$minLatency = count($latencies) > 0 ? min($latencies) * 1000 : 0;
$maxLatency = count($latencies) > 0 ? max($latencies) * 1000 : 0;
$rps = $totalTime > 0 ? $totalRequests / $totalTime : 0;

echo "--------------------------------------------------\n";
echo "TEST RESULTS:\n";
echo "--------------------------------------------------\n";
echo sprintf("Time Taken for Tests:  %.4f seconds\n", $totalTime);
echo sprintf("Completed Requests:    %d\n", $successCount + $rateLimitCount + $errorCount);
echo sprintf("Successful (200 OK):   %d\n", $successCount);
echo sprintf("Rate Limited (429):    %d\n", $rateLimitCount);
echo sprintf("Failed/Other Status:   %d\n", $errorCount);
echo sprintf("Requests per Second:   %.2f RPS\n", $rps);
echo sprintf("Average Latency:       %.2f ms\n", $avgLatency);
echo sprintf("Min Latency:           %.2f ms\n", $minLatency);
echo sprintf("Max Latency:           %.2f ms\n", $maxLatency);
echo "==================================================\n\n";
