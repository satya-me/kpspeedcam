<?php
// Load your XML data
$xmlString = <<<XML
<frameZones>
      <beam index="1">
        <posX_px>0</posX_px>
        <posY_px>348</posY_px>
        <width_px>0</width_px>
        <height_px>462</height_px>
      </beam>
      <beam index="2">
        <posX_px>0</posX_px>
        <posY_px>348</posY_px>
        <width_px>0</width_px>
        <height_px>462</height_px>
      </beam>
      <beam index="3">
        <posX_px>0</posX_px>
        <posY_px>348</posY_px>
        <width_px>64</width_px>
        <height_px>462</height_px>
      </beam>
      <beam index="4">
        <posX_px>68</posX_px>
        <posY_px>348</posY_px>
        <width_px>154</width_px>
        <height_px>462</height_px>
      </beam>
      <beam index="5">
        <posX_px>227</posX_px>
        <posY_px>348</posY_px>
        <width_px>153</width_px>
        <height_px>462</height_px>
      </beam>
      <beam index="6">
        <posX_px>386</posX_px>
        <posY_px>348</posY_px>
        <width_px>153</width_px>
        <height_px>462</height_px>
      </beam>
      <beam index="7">
        <posX_px>544</posX_px>
        <posY_px>348</posY_px>
        <width_px>153</width_px>
        <height_px>462</height_px>
      </beam>
      <beam index="8">
        <posX_px>702</posX_px>
        <posY_px>348</posY_px>
        <width_px>152</width_px>
        <height_px>462</height_px>
      </beam>
      <beam index="9">
        <posX_px>860</posX_px>
        <posY_px>348</posY_px>
        <width_px>152</width_px>
        <height_px>462</height_px>
      </beam>
      <beam index="10">
        <posX_px>1017</posX_px>
        <posY_px>348</posY_px>
        <width_px>153</width_px>
        <height_px>462</height_px>
      </beam>
      <beam index="11">
        <posX_px>1175</posX_px>
        <posY_px>348</posY_px>
        <width_px>153</width_px>
        <height_px>462</height_px>
      </beam>
      <beam index="12">
        <posX_px>1333</posX_px>
        <posY_px>348</posY_px>
        <width_px>153</width_px>
        <height_px>462</height_px>
      </beam>
      <beam index="13">
        <posX_px>1492</posX_px>
        <posY_px>348</posY_px>
        <width_px>154</width_px>
        <height_px>462</height_px>
      </beam>
      <beam index="14">
        <posX_px>1651</posX_px>
        <posY_px>348</posY_px>
        <width_px>154</width_px>
        <height_px>462</height_px>
      </beam>
      <beam index="15">
        <posX_px>1810</posX_px>
        <posY_px>348</posY_px>
        <width_px>109</width_px>
        <height_px>462</height_px>
      </beam>
      <beam index="16">
        <posX_px>1971</posX_px>
        <posY_px>348</posY_px>
        <width_px>0</width_px>
        <height_px>462</height_px>
      </beam>
    </frameZones>
XML;

$xml = simplexml_load_string($xmlString);

// Create a new image canvas
$imagePath = 'https://kpspeedcam.com/app/storage/app/unzipMedia/ticket23-10-25_12-43-33_980/full_4135_39808.jpg';

// Load the image
$image = imagecreatefromjpeg($imagePath);

// Set the colors
$white = imagecolorallocate($image, 255, 255, 255);
$black = imagecolorallocate($image, 0, 0, 0);
$red = imagecolorallocate($image, 255, 0, 0); // Rectangle color

// Fill the background with white
imagefilledrectangle($image, 0, 0, $width, $height, $white);

// Loop through each beam in the XML
foreach ($xml->beam as $beam) {
    $posX = (int) $beam->posX_px;
    $posY = (int) $beam->posY_px;
    $width = (int) $beam->width_px;
    $height = (int) $beam->height_px;

    // Draw a rectangle for each set of coordinates
    if ($width > 0) {
        imagerectangle($image, $posX, $posY, $posX + $width, $posY + $height, $red);
    }
}

// Output the image
header('Content-Type: image/jpeg');
imagejpeg($image);

// Free up memory
imagedestroy($image);
?>
