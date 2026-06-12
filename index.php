<?php
header('Content-Type: application/json; charset=utf-8');

$MAX_LIMIT = 300;
$DEFAULT_LIMIT = 100;

function curlGet($url, $timeout = 15) {
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => $timeout,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
    ]);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    return $response;
}

function searchFromFaa($query) {
    $url = 'https://api-faa.my.id/faa/youtube?q=' . urlencode($query);
    $response = curlGet($url);
    
    if (!$response) return [];
    
    $data = json_decode($response, true);
    if (!$data || !isset($data['result'])) return [];
    
    $videos = [];
    foreach ($data['result'] as $video) {
        $link = $video['link'] ?? '';
        preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $link, $matches);
        $videoId = $matches[1] ?? '';
        
        if ($videoId) {
            $videos[] = [
                'id' => $videoId,
                'title' => $video['title'] ?? 'No Title',
                'thumbnail' => $video['imageUrl'] ?? '',
                'channel' => $video['channel'] ?? 'Unknown',
                'duration' => $video['duration'] ?? '',
                'views' => $video['views'] ?? 'N/A',
                'url' => $link
            ];
        }
    }
    
    return $videos;
}

function generateVariations($query) {
    return [
        $query,
        $query . " full",
        $query . " compilation",
        $query . " best of",
        $query . " funny",
        $query . " cartoon",
        $query . " official",
        $query . " 2024",
        $query . " 2023",
        "best " . $query,
        "top " . $query,
        "new " . $query,
        $query . " episode 1",
        $query . " movie",
        $query . " trailer",
        $query . " clip",
        $query . " hd"
    ];
}

$query = isset($_GET['query']) ? trim($_GET['query']) : '';
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : $DEFAULT_LIMIT;
$limit = min($limit, $MAX_LIMIT);

if (empty($query)) {
    echo json_encode([
        'success' => false, 
        'error' => 'Parameter query diperlukan',
        'example' => 'index.php?query=spongebob&limit=100'
    ], JSON_PRETTY_PRINT);
    exit;
}

$variations = generateVariations($query);
$allVideos = [];
$seenIds = [];

foreach ($variations as $varQuery) {
    $videos = searchFromFaa($varQuery);
    foreach ($videos as $video) {
        if (!isset($seenIds[$video['id']])) {
            $seenIds[$video['id']] = true;
            $allVideos[] = $video;
        }
    }
    
    if (count($allVideos) >= $limit) break;
    usleep(100000);
}

$videos = array_slice($allVideos, 0, $limit);

echo json_encode([
    'success' => true,
    'query' => $query,
    'total' => count($videos),
    'limit' => $limit,
    'variations_used' => count($variations),
    'deployed_on' => 'Vercel',
    'author' => 'ZEROZX',
    'videos' => $videos
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>
