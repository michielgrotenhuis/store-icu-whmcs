<?php
/**
 * This file returns the logo for the module
 * 
 * Logo should be 100x100px PNG with transparent background
 */

// Placeholder image generation - in a real module you would include an actual logo file
header('Content-Type: image/png');
$im = imagecreatetruecolor(100, 100);
$white = imagecolorallocate($im, 255, 255, 255);
$blue = imagecolorallocate($im, 0, 114, 198);
$black = imagecolorallocate($im, 0, 0, 0);
imagefilledrectangle($im, 0, 0, 100, 100, $blue);
imagefilledrectangle($im, 10, 10, 90, 90, $white);
$text = 'Store.icu';
imagettftext($im, 14, 0, 15, 55, $black, 'arial.ttf', $text);
imagepng($im);
imagedestroy($im);
