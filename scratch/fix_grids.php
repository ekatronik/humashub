<?php
$dir = new RecursiveDirectoryIterator('c:\xampp\htdocs\humashub\admin');
$ite = new RecursiveIteratorIterator($dir);
$files = new RegexIterator($ite, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);

$pattern = '/style="display:\s*grid;\s*grid-template-columns:\s*1fr\s+1fr;[^"]*"/i';
$replacement = 'class="grid-2"';

// Wait, what if there's already a class attribute?
// <div class="card" style="..."> -> <div class="card grid-2">
// It's safer to just replace style="..." with class="grid-2", 
// but what if they have <div class="something" style="display: grid...">?
// Let's do two patterns:
$pattern1 = '/class="([^"]*)"\s+style="display:\s*grid;\s*grid-template-columns:\s*1fr\s+1fr;[^"]*"/i';
$replacement1 = 'class="$1 grid-2"';

$pattern2 = '/style="display:\s*grid;\s*grid-template-columns:\s*1fr\s+1fr;[^"]*"/i';
$replacement2 = 'class="grid-2"';

$count = 0;
foreach ($files as $file) {
    $path = $file[0];
    $content = file_get_contents($path);
    $changed = false;
    
    if (preg_match($pattern1, $content)) {
        $content = preg_replace($pattern1, $replacement1, $content);
        $changed = true;
    } elseif (preg_match($pattern2, $content)) {
        $content = preg_replace($pattern2, $replacement2, $content);
        $changed = true;
    }
    
    if ($changed) {
        file_put_contents($path, $content);
        $count++;
        echo "Updated: $path\n";
    }
}
echo "Total files updated: $count\n";
