<?php
function getRealTimeHealthNews($limit = 4) {
    $rss_url = "http://feeds.bbci.co.uk/news/health/rss.xml";
    $cache_file = __DIR__ . "/news_cache.json";
    $cache_time = 3600; // 1 hour

    // Check cache
    if (file_exists($cache_file) && (time() - filemtime($cache_file) < $cache_time)) {
        return json_decode(file_get_contents($cache_file), true);
    }

    $news = [];
    try {
        // Use curl for better compatibility
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $rss_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $data = curl_exec($ch);
        curl_close($ch);

        if ($data) {
            $xml = simplexml_load_string($data);
            if ($xml) {
                $count = 0;
                foreach ($xml->channel->item as $item) {
                    if ($count >= $limit) break;

                    $title = (string)$item->title;
                    $link = (string)$item->link;
                    $description = (string)$item->description;
                    $pubDate = (string)$item->pubDate;
                    
                    // Format date
                    $date = date("l d, F Y", strtotime($pubDate));

                    // Default image if none found
                    $image = "images/news-" . (($count % 3) + 1) . ".jpg";
                    
                    // Try to find image in media tags
                    $media = $item->children('media', true);
                    if ($media && $media->thumbnail) {
                        $image = (string)$media->thumbnail->attributes()->url;
                    } elseif ($media && $media->content) {
                        $image = (string)$media->content->attributes()->url;
                    }

                    $news[] = [
                        'title' => $title,
                        'link' => $link,
                        'description' => $description,
                        'date' => $date,
                        'image' => $image,
                        'author' => 'BBC Health'
                    ];
                    $count++;
                }
            }
        }
    } catch (Exception $e) {
        // Fallback or error logging
    }

    // If fetch failed, return some default data so site doesn't look broken
    if (empty($news)) {
        $news = [
            [
                'title' => 'Staying Healthy in 2025',
                'link' => '#',
                'description' => 'Tips for maintaining a healthy lifestyle.',
                'date' => date("l d, F Y"),
                'image' => 'images/news-1.jpg',
                'author' => 'Health Desk'
            ]
        ];
    } else {
        file_put_contents($cache_file, json_encode($news));
    }

    return $news;
}
?>
