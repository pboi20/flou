<?php
namespace Flou;

use Flou\Image;

interface ImageProcessorInterface
{
    public function setImage(Image $image);

    public function getOriginalWidth();

    public function getOriginalHeight();

    public function process();
}
