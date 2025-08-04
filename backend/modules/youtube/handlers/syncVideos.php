
<?php
// KudoPixel - YouTube Module - Sync Videos Handler
// Our intelligent spy bot that keeps our library up-to-date!

/**
 * This is not a typical API handler. It's a script designed to be run
 * automatically by the server on a schedule (a "Cron Job").
 * It fetches the latest videos from the YouTube channel and updates our local database.
 */

// We need the bootstrap file to get the database connection and config
require_once __DIR__ . '/../../../core/bootstrap.php';

echo "🤖 Starting YouTube Sync Bot...\n";

// --- Step 1: Get the Channel's Uploads Playlist ID ---
// We need this special ID to get a list of all uploaded videos.
$channelUrl = "https://www.googleapis.com/youtube/v3/channels?part=contentDetails&id=" . YOUTUBE_CHANNEL_ID . "&key=" . YOUTUBE_API_KEY;

// Use a function to safely fetch data from the API
function fetch_json($url) {
    $json = @file_get_contents($url);
    if ($json === FALSE) {
        return null;
    }
    return json_decode($json);
}

$channelResponse = fetch_json($channelUrl);
$uploadsPlaylistId = $channelResponse->items[0]->contentDetails->relatedPlaylists->uploads ?? null;

if (!$uploadsPlaylistId) {
    echo "❌ Error: Could not find uploads playlist for the channel.\n";
    exit();
}

echo "✅ Found uploads playlist: {$uploadsPlaylistId}\n";

// --- Step 2: Fetch the Latest Videos from the Playlist ---
$videosUrl = "https://www.googleapis.com/youtube/v3/playlistItems?part=snippet&playlistId={$uploadsPlaylistId}&maxResults=10&key=" . YOUTUBE_API_KEY;
$videosResponse = fetch_json($videosUrl);

if (!isset($videosResponse->items)) {
    echo "❌ Error: Could not fetch videos from the playlist.\n";
    exit();
}

$videos = $videosResponse->items;
echo "✅ Found " . count($videos) . " recent videos. Checking for new ones...\n";

// --- Step 3: Loop and Sync with our Database ---
$newVideosSynced = 0;
foreach ($videos as $video) {
    $snippet = $video->snippet;
    $videoId = $snippet->resourceId->videoId;

    try {
        // Check if this video is already in our library
        $stmt = $pdo->prepare("SELECT id FROM videos WHERE youtube_video_id = ?");
        $stmt->execute([$videoId]);

        if ($stmt->fetch()) {
            // Video already exists, we can skip it.
            continue;
        }
        
        // It's a new video! Let's add it to our library.
        echo "    syncing new video: {$snippet->title}\n";
        
        $insertStmt = $pdo->prepare(
            "INSERT INTO videos (youtube_video_id, title, description, thumbnail_url, published_at) VALUES (?, ?, ?, ?, ?)"
        );
        $insertStmt->execute([
            $videoId,
            $snippet->title,
            $snippet->description,
            $snippet->thumbnails->high->url,
            // Convert YouTube's date format to MySQL's DATETIME format
            date('Y-m-d H:i:s', strtotime($snippet->publishedAt))
        ]);
        $newVideosSynced++;

    } catch (PDOException $e) {
        echo "❌ Database Error: " . $e->getMessage() . "\n";
    }
}

echo "🎉 Sync complete! Added {$newVideosSynced} new videos to the KudoVerse library.\n";
?>
