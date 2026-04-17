<?php
$base_dir = '/home/david/public_html/proyectos/locales/';
$base_dir_local = '/home/david/Documentos/Code/DotProject/locales/';

function fix_locales($dir)
{
    if (!is_dir($dir))
        return;
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() == 'inc') {
            $path = $file->getPathname();
            $content = file_get_contents($path);
            $modified = false;

            // Add missing comma to 1LBLMONITORAMENTO if it's there
            if (preg_match('/"1LBLMONITORAMENTO"\s*=>\s*"Monitoring"(\r?\n)/', $content)) {
                $content = preg_replace('/"1LBLMONITORAMENTO"\s*=>\s*"Monitoring"(\r?\n)/', '"1LBLMONITORAMENTO"=>"Monitoring",\\1', $content);
                $modified = true;
            }
            if (preg_match('/"1LBLMONITORAMENTO"\s*=>\s*"Monitoramento"(\r?\n)/', $content)) {
                $content = preg_replace('/"1LBLMONITORAMENTO"\s*=>\s*"Monitoramento"(\r?\n)/', '"1LBLMONITORAMENTO"=>"Monitoramento",\\1', $content);
                $modified = true;
            }

            // Also check other appended strings that might lack a final comma at end of file.
            // But they might be at the very end of the file. If they are followed by another string in core.php concatenation, they need a comma.
            // Easiest is to ensure EVERY line ending with a quote in translation files has a comma.
            $lines = explode("\n", $content);
            foreach ($lines as $i => $line) {
                $trim = trim($line);
                if (preg_match('/"[^"]+"$/', $trim) && !str_starts_with($trim, '##')) {
                    $lines[$i] = $line . ',';
                    $modified = true;
                }
            }
            if ($modified) {
                file_put_contents($path, implode("\n", $lines));
                echo "Fixed $path\n";
            }
        }
    }
}

fix_locales($base_dir);
fix_locales($base_dir_local);
echo "Done fixing locales\n";
