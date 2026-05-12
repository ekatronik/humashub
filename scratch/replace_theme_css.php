<?php
$dir = new RecursiveDirectoryIterator('c:\xampp\htdocs\humashub\admin');
$ite = new RecursiveIteratorIterator($dir);
$files = new RegexIterator($ite, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);

$css_pattern = '/<link\s+rel="stylesheet"\s+href="[^"]*assets\/css\/style\.css">/i';
$css_replacement = '<link rel="stylesheet" href="<?php echo (basename(dirname($_SERVER[\'PHP_SELF\'])) == \'admin\') ? \'../\' : \'../../\'; ?>assets/themes/<?php echo get_setting(\'app_theme\', \'default\'); ?>/style.css">';

$body_pattern = '/<body>/i';
$body_replacement = '<body data-theme="<?php echo get_setting(\'theme_mode\', \'light\'); ?>">';

$count = 0;
foreach ($files as $file) {
    $path = $file[0];
    $content = file_get_contents($path);
    $changed = false;
    
    if (preg_match($css_pattern, $content)) {
        $content = preg_replace($css_pattern, $css_replacement, $content);
        $changed = true;
    }
    
    if (preg_match($body_pattern, $content)) {
        $content = preg_replace($body_pattern, $body_replacement, $content);
        $changed = true;
    }
    
    if ($changed) {
        file_put_contents($path, $content);
        $count++;
        echo "Updated: $path\n";
    }
}
echo "Total files updated: $count\n";
