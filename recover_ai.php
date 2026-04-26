<?php
$logPath = 'C:\Users\malek\.gemini\antigravity\brain\760d4986-5cca-4737-a4de-5ff5946b871a\.system_generated\logs\overview.txt';
$content = file_get_contents($logPath);

// The log contains JSON API calls or formatted tool calls.
// Let's search for "CodeContent" and "TargetFile"
$lines = explode("\n", $content);

$filesToRecover = [
    'ai_engine.php',
    'TFIDFEngine.php',
    'GraphEngine.php',
    'MinMaxEngine.php',
    'ai_client.js'
];

$recovered = [];
$currentFile = null;
$currentContent = [];
$inCodeBlock = false;

// Actually, in the overview.txt, tool calls are logged. If it's too hard to parse manually, we can just search for "class TFIDFEngine"
echo "Log size: " . strlen($content) . " bytes\n";
$matches = [];
preg_match_all('/TargetFile[^>]*?>([^<]+)<.*?CodeContent[^>]*?>([\s\S]*?)<\/CodeContent>/is', $content, $matches, PREG_SET_ORDER);

foreach ($matches as $match) {
    $targetFile = trim($match[1]);
    $codeContent = trim($match[2]);
    foreach ($filesToRecover as $f) {
        if (strpos($targetFile, $f) !== false) {
            $recovered[$f] = $codeContent;
            echo "Found $f (" . strlen($codeContent) . " bytes)\n";
        }
    }
}

// Since overview.txt might store JSON encoded tool calls, let's also try regex for JSON.
preg_match_all('/"TargetFile"\s*:\s*"([^"]+)"\s*,\s*"CodeContent"\s*:\s*"((?:[^"\\\\]|\\\\.)*)"/is', $content, $jsonMatches, PREG_SET_ORDER);
foreach ($jsonMatches as $match) {
    $targetFile = trim($match[1]);
    $codeContent = stripcslashes($match[2]);
    foreach ($filesToRecover as $f) {
        if (strpos($targetFile, $f) !== false && !isset($recovered[$f])) {
            $recovered[$f] = $codeContent;
            echo "Found $f via JSON (" . strlen($codeContent) . " bytes)\n";
        }
    }
}

if (!is_dir('model/AI')) {
    mkdir('model/AI', 0777, true);
}

if (isset($recovered['ai_engine.php'])) file_put_contents('controller/ai_engine.php', $recovered['ai_engine.php']);
if (isset($recovered['TFIDFEngine.php'])) file_put_contents('model/AI/TFIDFEngine.php', $recovered['TFIDFEngine.php']);
if (isset($recovered['GraphEngine.php'])) file_put_contents('model/AI/GraphEngine.php', $recovered['GraphEngine.php']);
if (isset($recovered['MinMaxEngine.php'])) file_put_contents('model/AI/MinMaxEngine.php', $recovered['MinMaxEngine.php']);
if (isset($recovered['ai_client.js'])) file_put_contents('script/ai_client.js', $recovered['ai_client.js']);

echo "Done recovering files.\n";
?>
