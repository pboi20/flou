<?php

use PHPUnit\Framework\TestCase;
use Flou\Path;
use Flou\Image;

require_once __DIR__ . '/fixtures/CustomImageRenderer.php';
require_once __DIR__ . '/fixtures/CustomImageProcessor.php';

class ImageTest extends TestCase
{
    public static $base_path;
    public static $processed_path;


    public static function setUpBeforeClass()
    {
        $current_dir = dirname(__FILE__);
        self::$base_path = Path::join($current_dir, "fixtures/images");
        self::$processed_path = Path::join(self::$base_path, "image1.flou.jpg");

        self::_cleanup();
    }

    public static function tearDownAfterClass()
    {
        self::_cleanup();
    }

    public static function _cleanup()
    {
        if (file_exists(self::$processed_path)) {
            unlink(self::$processed_path);
        }
    }

    /**
     * Get the original file path from an Image
     */
    public function testGetOriginalFilePath()
    {
        $image = new Flou\Image();

        // Expect `null` until the Image is properly configured
        $this->assertNull($image->getOriginalFilePath());
        $image->setBasePath(self::$base_path);
        $this->assertNull($image->getOriginalFilePath());
        $image->load("image1.jpg");
        $this->assertNotNull($image->getOriginalFilePath());
        $expected_path = Path::join(self::$base_path, "image1.jpg");
        $this->assertEquals($expected_path, $image->getOriginalFilePath());
    }

    /**
     * Get the processed file path from an Image
     */
    public function testGetProcessedFilePath()
    {
        $image = new Flou\Image();

        // Using the default processed file path:
        // Expect `null` until the Image is properly configured
        $this->assertNull($image->getProcessedFilePath());
        $image->setBasePath(self::$base_path);
        $this->assertNull($image->getProcessedFilePath());
        $image->load("image1.jpg");
        $this->assertNotNull($image->getProcessedFilePath());
        $expected_path = Path::join(self::$base_path, "image1.flou.jpg");
        $this->assertEquals($expected_path, $image->getProcessedFilePath());

        // Using a custom processed file path:
        $custom_path = Path::join(self::$base_path, "custom");
        $image->setProcessedPath($custom_path);
        $expected_path = Path::join($custom_path, "image1.flou.jpg");
        $this->assertEquals($expected_path, $image->getProcessedFilePath());

        // Using a custom processed filename:
        $custom_file = "custom.jpg";
        $image->setProcessedFile($custom_file);
        $expected_path = Path::join($custom_path, $custom_file);
        $this->assertEquals($expected_path, $image->getProcessedFilePath());
    }

    /**
     * Get the original URL for an Image
     */
    public function testGetOriginalURL()
    {
        $image = new Flou\Image();

        // Expect `null` until the Image is properly configured
        $this->assertNull($image->getOriginalURL());
        $image->setBasePath(self::$base_path);
        $this->assertNull($image->getOriginalURL());
        $image->setBaseURL("/img");
        $this->assertNull($image->getOriginalURL());
        $image->load("image1.jpg");
        $this->assertNotNull($image->getOriginalURL());
        $expected_url = "/img/image1.jpg";
        $this->assertEquals($expected_url, $image->getOriginalURL());
    }

    /**
     * Get the processed URL for an Image
     */
    public function testGetProcessedURL()
    {
        $image = new Flou\Image();

        // Using the default settings:
        // Expect `null` until the Image is properly configured
        $this->assertNull($image->getProcessedURL());
        $image->setBasePath(self::$base_path);
        $this->assertNull($image->getProcessedURL());
        $image->setBaseURL("/img");
        $this->assertNull($image->getProcessedURL());
        $image->load("image1.jpg");
        $this->assertNotNull($image->getProcessedURL());
        $expected_url = "/img/image1.flou.jpg";
        $this->assertEquals($expected_url, $image->getProcessedURL());

        // Using a custom processed URL:
        $custom_path = Path::join(self::$base_path, "custom");
        $custom_url = "/custom";
        $image->setProcessedPath($custom_path);
        $this->assertNull($image->getProcessedURL());
        $image->setProcessedUrl($custom_url);
        $this->assertNotNull($image->getProcessedURL());
        $expected_url = "/custom/image1.flou.jpg";
        $this->assertEquals($expected_url, $image->getProcessedURL());

        // Using a custom processed filename:
        $custom_file = "custom.jpg";
        $image->setProcessedFile($custom_file);
        $expected_url = "/custom/custom.jpg";
        $this->assertEquals($expected_url, $image->getProcessedURL());
    }

    /**
     * Call `setBasePath` and load an image relative to that path
     */
    public function testLoadWithBasePath()
    {
        $path = Path::join(self::$base_path, "image1.jpg");
        $image = (new Flou\Image())
            ->setBasePath(self::$base_path)
            ->load("image1.jpg");

        // `getOriginalFilePath` returns the full path to the original file
        $this->assertEquals($path, $image->getOriginalFilePath());
    }

    /**
     * Load an image using it's full path
     */
    public function testLoadFullPath()
    {
        $path = Path::join(self::$base_path, "image1.jpg");
        $image = (new Flou\Image())->load($path);

        // `getOriginalFilePath` returns the full path to the original file;
        // the base path was extracted from the full path provided to `load`
        $this->assertEquals($path, $image->getOriginalFilePath());
    }

    /**
     * Process an image using the default output path and default output filename
     */
    public function testProcess()
    {
        $processed_path = self::$processed_path;
        $this->assertFalse(file_exists($processed_path));

        $image = (new Flou\Image())
            ->setBasePath(self::$base_path)
            ->load("image1.jpg");

        // `getProcessedFilePath` returns the expected full path to the processed
        // image (not yet processed)
        $this->assertEquals($processed_path, $image->getProcessedFilePath());
        $this->assertFalse($image->isProcessed());

        // Process and save the image
        $image->process();
        $this->assertTrue($image->isProcessed());
        $this->assertTrue(file_exists($processed_path));
    }

    /**
     * Force-process an image that has already been processed
     *
     * @depends testProcess
     */
    public function testForceProcess()
    {
        $processed_path = self::$processed_path;
        $this->assertTrue(file_exists($processed_path));

        $image = (new Flou\Image())
            ->setBasePath(self::$base_path)
            ->load("image1.jpg")
            ->process();
        $mtime1 = filemtime($processed_path);

        sleep(1);
        $image->process();
        $mtime2 = filemtime($processed_path);

        // The processed image already existed, it was not regenerated
        $this->assertEquals($mtime1, $mtime2);

        sleep(1);
        $image->forceProcess();
        $mtime3 = filemtime($processed_path);

        // The processed image already existed, but it was regenerated by forceProcess
        $this->assertNotEquals($mtime2, $mtime3);
    }

    /**
     * Process an image using a custom processor
     */
    public function testCustomProcessor()
    {
        $processed_path = self::$processed_path;
        $this->assertTrue(file_exists($processed_path));

        $image = (new Flou\Image())
            ->setImageProcessor(new CustomImageProcessor())
            ->setBasePath(self::$base_path)
            ->load("image1.jpg");

        $image->process();
        $this->assertTrue($image->isProcessed());
        $this->assertTrue(file_exists($processed_path));
    }

    /**
     * Get the original width and height of an Image
     *
     * @depends testProcess
     */
    public function testGetOrginalGeometry()
    {
        $processed_path = self::$processed_path;
        $this->assertTrue(file_exists($processed_path));

        $image = (new Flou\Image())
            ->setBasePath(self::$base_path)
            ->load("image1.jpg");

        // Expect `null` until `process()` is called
        $this->assertNull($image->getOriginalWidth());
        $this->assertNull($image->getOriginalHeight());

        $image->process();
        $this->assertNotNull($image->getOriginalWidth());
        $this->assertNotNull($image->getOriginalHeight());
    }

    /**
     * Generate the HTML markup for an image using default settings
     *
     * @see DefaultImageRendererTest
     */
    public function testRender()
    {
        $image = (new Flou\Image())
            ->setBasePath(self::$base_path)
            ->setBaseUrl("/img")
            ->setDescription("Image Description")
            ->load("image1.jpg");

        $html = $image->render();
        $this->assertNotNull($html);
        $this->assertContains("Image Description", $html);
    }

    /**
     * Generate the HTML markup for an image using a custom renderer
     */
    public function testCustomRenderer()
    {
        $image = (new Flou\Image())
            ->setImageRenderer(new CustomImageRenderer())
            ->setBasePath(self::$base_path)
            ->setBaseUrl("/img")
            ->setDescription("Image Description")
            ->load("image1.jpg");

        $html = $image->render();
        $this->assertNotNull($html);
        $this->assertContains("Image Description", $html);
    }
}
