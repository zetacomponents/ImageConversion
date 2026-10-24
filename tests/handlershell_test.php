<?php
/**
 * ezcImageConversionHandlerShellTest
 *
 * Licensed to the Apache Software Foundation (ASF) under one
 * or more contributor license agreements.  See the NOTICE file
 * distributed with this work for additional information
 * regarding copyright ownership.  The ASF licenses this file
 * to you under the Apache License, Version 2.0 (the
 * "License"); you may not use this file except in compliance
 * with the License.  You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing,
 * software distributed under the License is distributed on an
 * "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
 * KIND, either express or implied.  See the License for the
 * specific language governing permissions and limitations
 * under the License.
 *
 * @package ImageConversion
 * @version //autogentag//
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */

/**
 * Require base class for handler tests.
 */
require_once "handler_test.php";

/**
 * Test suite for ImageHandlerShell class.
 * This test class contains all tests that are specific to the GD handler.
 *
 * @package ImageConversion
 * @version //autogentag//
 */
class ezcImageConversionHandlerShellTest extends ezcImageConversionHandlerTest
{
	public static function suite()
	{
		return new \PHPUnit\Framework\TestSuite( "ezcImageConversionHandlerShellTest" );
	}

    public function setUp() : void
    {
        try
        {
            $dummy = new ezcImageImagemagickHandler( ezcImageImagemagickBaseHandler::defaultSettings() );
        }
        catch ( Exception $e )
        {
            $this->markTestSkipped( $e->getMessage() );
        }
        $this->handlerClass = "ezcImageImagemagickHandler";
        parent::setUp();
    }

    public function testInitFromSetBinary()
    {
        $settings = ezcImageImagemagickHandler::defaultSettings();
        $settings->options['binary'] = ezcBaseFeatures::getImageConvertExecutable();

        $handler = new ezcImageImagemagickHandler( $settings );

        $filePath = $this->testFiles["jpeg"];

        $ref = $handler->load( $filePath );
        $handler->close( $ref );
    }

    public function testSaveDoesNotBlockOnLargeOutput()
    {
        if ( ezcBaseFeatures::os() === 'Windows' || ezcBaseFeatures::findExecutableInPath( 'perl' ) === null )
        {
            $this->markTestSkipped( 'Needs a POSIX shell and perl.' );
        }

        // A fake "convert" that writes 256 KiB to STDOUT, more than a pipe
        // buffer holds. If the handler stops reading STDOUT before the
        // process is done, the writes cannot complete; after 5 seconds the
        // process reports that on STDERR and exits with 3.
        $dir = $this->createTempDir( 'ezcImageConversionLargeOutput' );
        $binary = $dir . DIRECTORY_SEPARATOR . 'convert';
        file_put_contents( $binary, <<<'EOT'
#!/bin/sh
if [ $# -eq 0 ]; then echo 'Version: ImageMagick (fake)'; exit 0; fi
exec perl -MFcntl -e '
    fcntl( STDOUT, F_SETFL, fcntl( STDOUT, F_GETFL, 0 ) | O_NONBLOCK );
    $data = ( "x" x 1023 . "\n" ) x 256;
    $deadline = time + 5;
    while ( length $data ) {
        $written = syswrite( STDOUT, $data );
        if ( defined $written ) { substr( $data, 0, $written ) = ""; }
        elsif ( time > $deadline ) { print STDERR "blocked\n"; exit 3; }
        else { select( undef, undef, undef, 0.01 ); }
    }
    exit 0;
'

EOT
        );
        chmod( $binary, 0755 );

        $settings = ezcImageImagemagickHandler::defaultSettings();
        $settings->options['binary'] = $binary;
        $handler = new ezcImageImagemagickHandler( $settings );

        $ref = $handler->load( $this->testFiles["jpeg"], 'image/jpeg' );
        $handler->save( $ref, $this->getTempPath(), 'image/jpeg' );
        $handler->close( $ref );

        $this->assertFileExists( $this->getTempPath() );
    }

    public function testCompositeImageIsPassedAsAbsolutePath()
    {
        if ( ezcBaseFeatures::os() === 'Windows' )
        {
            $this->markTestSkipped( 'Needs a POSIX shell.' );
        }

        // ImageMagick may read a relative name like "-x.png" as an option,
        // or "label:x.png" as a coder, even when it is quoted for the shell.
        // A fake "convert" records the arguments it receives.
        $dir = realpath( $this->createTempDir( 'ezcImageConversionCompositePath' ) );
        $binary = $dir . DIRECTORY_SEPARATOR . 'convert';
        file_put_contents( $binary, <<<'EOT'
#!/bin/sh
if [ $# -eq 0 ]; then echo 'Version: ImageMagick (fake)'; exit 0; fi
for a in "$@"; do printf '%s\n' "$a"; done > "$0.args"

EOT
        );
        chmod( $binary, 0755 );
        copy( dirname( __FILE__ ) . '/data/watermark.png', $dir . DIRECTORY_SEPARATOR . '-x.png' );
        copy( dirname( __FILE__ ) . '/data/watermark.png', $dir . DIRECTORY_SEPARATOR . 'label:x.png' );

        $settings = ezcImageImagemagickHandler::defaultSettings();
        $settings->options['binary'] = $binary;
        $handler = new ezcImageImagemagickHandler( $settings );

        $ref = $handler->load( $this->testFiles['jpeg'], 'image/jpeg' );
        $cwd = getcwd();
        chdir( $dir );
        try
        {
            $handler->watermarkAbsolute( '-x.png', 10, 10 );
            $handler->watermarkAbsolute( 'label:x.png', 20, 20 );
            $handler->save( $ref, $this->getTempPath(), 'image/jpeg' );
        }
        catch ( Exception $e )
        {
            chdir( $cwd );
            throw $e;
        }
        chdir( $cwd );
        $handler->close( $ref );

        $arguments = file( $binary . '.args', FILE_IGNORE_NEW_LINES );
        $this->assertContains( $dir . DIRECTORY_SEPARATOR . '-x.png', $arguments );
        $this->assertContains( $dir . DIRECTORY_SEPARATOR . 'label:x.png', $arguments );
        $this->assertNotContains( '-x.png', $arguments );
        $this->assertNotContains( 'label:x.png', $arguments );
    }

    public function testLoadSuccess()
    {
        $filePath = $this->testFiles["jpeg"];

        $ref = $this->handler->load( $filePath );

        $refProp = $this->getReferences();
        $imageRef = current( $refProp );

        $this->handler->close( $ref );
        $this->assertSame(
            $filePath,
            $imageRef["file"],
            "Image reference not registered correctly."
        );

        $this->assertSame(
            $imageRef["mime"],
            "image/jpeg",
            "Image reference not registered correctly."
        );

    }

    public function testLoadFailureFilenotexists()
    {
        $filePath = $this->testFiles["nonexistent"];

        try
        {
            $ref = $this->handler->load( $filePath );
        }
        catch ( ezcBaseFileNotFoundException $e )
        {
            return;
        }
        $this->fail( "Required exception not thrown on not existing file." );
    }

    public function testLoadFailureUnknownmimetype()
    {
        $filePath = $this->testFiles["noimage"];

        try
        {
            $ref = $this->handler->load( $filePath );
        }
        catch ( ezcImageMimeTypeUnsupportedException $e )
        {
            return;
        }
        $this->fail( "Required exception not thrown on not existing file." );
    }

    public function testLoadCorruptJpegFailure()
    {
        $origPath = $this->testFiles['corrupt_jpeg'];
        $tmpPath  = $this->getTempPath();

        copy( $origPath, $tmpPath );

        $ref = $this->handler->load( $tmpPath );

        unlink( $tmpPath );

        try
        {
            $this->handler->save( $ref );
            $this->fail( 'Exception not thrown on processing corrupt JPEG.' );
        } catch ( ezcImageFileNotProcessableException $e ) {}

        $this->handler->close( $ref );
    }

    public function testCloseSuccess()
    {
        $srcPath = $this->testFiles["jpeg"];
        $ref = $this->handler->load( $srcPath );

        $refProp = $this->getReferences();
        $tmpFile = $refProp[$ref]["resource"];

        $this->handler->close( $ref );

        $refProp = $this->getReferences();

        $this->assertFalse(
            isset( $refProp[$ref] ),
            "Reference not freed successfully."
        );
        $this->assertFalse(
            file_exists( $tmpFile ),
            "Temporary file not deleted successfully."
        );
    }

    public function testRemoveTempFilesInDtorSuccess()
    {
        $filePath = $this->testFiles["jpeg"];

        $ref = $this->handler->load( $filePath );

        $refProp = $this->getReferences();
        $imageRef = current( $refProp );

        // Manually destruct handler
        unset( $this->handler );

        $this->assertFalse(
            file_exists( $imageRef["resource"] ),
            "Image reference not closed correctly in dtor."
        );

    }

    public function testApplyFilterSingle()
    {

        $srcPath = $this->testFiles["jpeg"];
        $dstPath = $this->getTempPath();

        $ref = $this->handler->load( $srcPath );
        $this->handler->applyFilter( $ref, new ezcImageFilter( "scale", array( "width" => 200, "height" => 200, "direction" => ezcImageGeometryFilters::SCALE_BOTH ) ) );
        $this->handler->save( $ref, $dstPath );
        $this->handler->close( $ref );
        $this->assertImageSimilar(
            $this->getReferencePath(),
            $dstPath,
             "Applying single filter through handler failed.",
            300
        );
    }

    public function testApplyFilterMultiple()
    {
        $srcPath = $this->testFiles["jpeg"];
        $dstPath = $this->getTempPath();

        $ref = $this->handler->load( $srcPath );

        $this->handler->applyFilter( $ref, new ezcImageFilter( "scale", array( "width" => 200, "height" => 200, "direction" => ezcImageGeometryFilters::SCALE_BOTH ) ) );
        $this->handler->applyFilter( $ref, new ezcImageFilter( "crop", array( "x" => 50, "width" => 100, "y" => 50, "height" => 100 ) ) );
        $this->handler->applyFilter( $ref, new ezcImageFilter( "colorspace", array( "space" => ezcImageColorspaceFilters::COLORSPACE_SEPIA ) ) );

        $this->handler->save( $ref, $dstPath );

        $this->handler->close( $ref );
        $this->assertImageSimilar(
            $this->getReferencePath(),
            $dstPath,
            "Applying multiple filter through handler failed.",
            // ezcImageConversionTestCase::DEFAULT_SIMILARITY_GAP
            12000
        );
        // @ todo: Orphan! Remove!
        $this->removeTempDir();
    }

    public function testSaveNewfileQualityLow()
    {
        $srcPath = $this->testFiles["png"];
        $dstPath = $this->getTempPath();

        $handler = new ezcImageGdHandler( ezcImageGdHandler::defaultSettings() );
        $ref = $handler->load( $srcPath );

        $opts = new ezcImageSaveOptions();
        $opts->quality     = 0;

        $handler->save( $ref, $dstPath, "image/jpeg", $opts );

        $this->assertTrue(
            filesize( $dstPath ) < 2000,
            "File saved with too high quality."
        );
    }

    public function testSaveNewfileQualityHigh()
    {
        $srcPath = $this->testFiles["png"];
        $dstPath = $this->getTempPath();

        $handler = new ezcImageGdHandler( ezcImageGdHandler::defaultSettings() );
        $ref = $handler->load( $srcPath );

        $opts = new ezcImageSaveOptions();
        $opts->quality     = 100;

        $handler->save( $ref, $dstPath, "image/jpeg", $opts );

        $this->assertTrue(
            filesize( $dstPath ) > 30000,
            "File saved with too low quality."
        );
    }

    public function testSaveNewfileCompressionLow()
    {
        $srcPath = $this->testFiles["png"];
        $dstPath = $this->getTempPath();

        $handler = new ezcImageGdHandler( ezcImageGdHandler::defaultSettings() );
        $ref = $handler->load( $srcPath );

        $opts = new ezcImageSaveOptions();
        $opts->compression = 0;

        $handler->save( $ref, $dstPath, "image/png", $opts );

        $this->assertTrue(
            filesize( $dstPath ) > 100000,
            "File saved with too high compression."
        );
    }

    public function testSaveNewfileCompressionHigh()
    {
        $srcPath = $this->testFiles["png"];
        $dstPath = $this->getTempPath();

        $handler = new ezcImageGdHandler( ezcImageGdHandler::defaultSettings() );
        $ref = $handler->load( $srcPath );

        $opts = new ezcImageSaveOptions();
        $opts->compression = 9;

        $handler->save( $ref, $dstPath, "image/png", $opts );

        $this->assertTrue(
            filesize( $dstPath ) < 40000,
            "File saved with too low compression."
        );
    }

    public function testConvertTransparentNonTransparent()
    {

        $srcPath = $this->testFiles["png_transparent"];
        $dstPath = $this->getTempPath();

        $ref = $this->handler->load( $srcPath );

        $options = new ezcImageSaveOptions();
        $options->transparencyReplacementColor = array( 255, 0, 0 );

        $this->handler->save( $ref, $dstPath, 'image/jpeg', $options );

        $this->handler->close( $ref );
        $this->assertImageSimilar(
            $this->getReferencePath(),
            $dstPath,
             "Converting transparent background failed.",
            500
        );
    }
}
?>
