<?php
// Script to translate DotProject module en.inc files to es.inc
$baseDir = '/home/david/Documentos/Code/DotProject/modules';

function translate_bulk($strings)
{
    if (empty($strings))
        return [];

    // Join with a unique delimiter that translation won't mess up
    $delimiter = " \n=@= \n";
    $text = implode($delimiter, $strings);

    $url = "https://translate.googleapis.com/translate_a/single?client=gtx&sl=en&tl=es&dt=t&q=" . urlencode($text);
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    // Add user agent
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    $translated_text = "";
    if ($data && isset($data[0])) {
        foreach ($data[0] as $chunk) {
            $translated_text .= $chunk[0];
        }
    } else {
        return $strings; // Fallback
    }

    $translated_strings = explode("=@=", $translated_text);
    $translated_strings = array_map('trim', $translated_strings);

    // Fallback if count doesn't match
    if (count($translated_strings) !== count($strings)) {
        echo "Warning: Batch count mismatch! Expected " . count($strings) . " got " . count($translated_strings) . "\n";
        return $strings;
    }

    return $translated_strings;
}

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($baseDir));
foreach ($iterator as $file) {
    if ($file->isFile() && $file->getFilename() === 'en.inc') {
        $enFile = $file->getPathname();
        $esFile = dirname($enFile) . '/es.inc';

        echo "Processing: $enFile\n";

        $lines = file($enFile);

        $keys = [];
        $englishStrings = [];
        $lineFormats = []; // To reconstruct

        foreach ($lines as $idx => $line) {
            $line = trim($line);

            if (
                preg_match('/^"([^"]+)"\s*=>\s*"(.*?)"(,)?$/', $line, $matches) ||
                preg_match("/^'([^']+)'\s*=>\s*'(.*?)'(,)?$/", $line, $matches)
            ) {

                $key = $matches[1];
                $englishStr = $matches[2];
                $comma = isset($matches[3]) ? $matches[3] : '';

                $keys[] = $key;
                $englishStrings[] = $englishStr;
                $lineFormats[$idx] = [
                    'type' => 'kv',
                    'key' => $key,
                    'comma' => $comma,
                    'quote' => substr($line, 0, 1) // " or '
                ];
            } else {
                $lineFormats[$idx] = [
                    'type' => 'raw',
                    'content' => $line
                ];
            }
        }

        // Translate in bulk
        $spanishStrings = translate_bulk($englishStrings);

        $esLines = [];
        $strIdx = 0;
        foreach ($lines as $idx => $line) {
            $format = $lineFormats[$idx];
            if ($format['type'] === 'kv') {
                $translated = $spanishStrings[$strIdx];
                // Escape quotes depending on encapsulation type
                $quote = $format['quote'];
                if ($quote === '"') {
                    $translated = str_replace('"', '\"', stripslashes($translated));
                } else {
                    $translated = str_replace("'", "\'", stripslashes($translated));
                }

                $esLines[] = $quote . $format['key'] . $quote . "=>" . $quote . $translated . $quote . $format['comma'];
                $strIdx++;
            } else {
                $esLines[] = $format['content'];
            }
        }

        // Write the es.inc file
        file_put_contents($esFile, implode("\n", $esLines) . "\n");
        echo "Created: $esFile\n";

        // Copy to live environment as well
        $liveDir = str_replace('/home/david/Documentos/Code/DotProject', '/home/david/public_html/proyectos', dirname($esFile));
        if (is_dir($liveDir)) {
            copy($esFile, $liveDir . '/es.inc');
            echo "Copied to live: $liveDir/es.inc\n";
        }

        // Sleep slightly to respect APIs
        sleep(2);
    }
}
echo "Done!\n";
?>