<?php
/**
 * ezcImageConversionTestCase
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
 * Base class for ImageConversion tests.
 *
 * @package ImageConversion
 * @version //autogentag//
 * @subpackage Tests
 */
class ezcImageConversionTestCase extends ezcTestImageCase
{
    // To regenerate all test files, set this to true
    const REGENERATION_MODE = false;

    // Set this to false to keep the temporary test dirs
    const REMOVE_TEMP_DIRS = true;

    const DEFAULT_SIMILARITY_GAP = 10;

    protected static $tempDirs = array();

    protected $testFiles = array();

    protected $referencePath;

    public function __construct()
    {
        parent::__construct();
        $dataDir = dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'data';
        foreach ( glob( $dataDir . DIRECTORY_SEPARATOR . '*' ) as $testFile )
        {
            if ( !is_file( $testFile ) )
            {
                continue;
            }
            $pathInfo = pathinfo( $testFile );
            $this->testFiles[basename( $pathInfo["basename"], "." . $pathInfo["extension"] )] = realpath( $testFile );
        }
        $this->testFiles["nonexistent"] = "nonexistent.jpg";
        $this->referencePath = $dataDir . DIRECTORY_SEPARATOR . 'compare';
    }

    public function __destruct()
    {
        if ( ezcImageConversionTestCase::REMOVE_TEMP_DIRS === true )
        {
            $this->removeTempDir();
            unset( ezcImageConversionTestCase::$tempDirs[get_class( $this )] );
        }
    }

    protected function setUp() : void
    {
        if ( !ezcBaseFeatures::hasExtensionSupport( 'gd' ) )
        {
            $this->markTestSkipped( 'ext/gd is required to run this test.' );
        }
    }

    protected function getTempPath( $index = "" )
    {
        return ezcImageConversionTestCase::REGENERATION_MODE === true
            ? $this->referencePath . DIRECTORY_SEPARATOR . $this->getTestName( $index )
            : $this->getTempBasePath() . DIRECTORY_SEPARATOR . $this->getTestName( $index );
    }

    protected function getReferencePath( $index = "" )
    {
        return $this->referencePath . DIRECTORY_SEPARATOR . $this->getTestName( $index );
    }

    private function getTestName ( $index )
    {
        $trace = debug_backtrace();
        if ( !isset( $trace[2]["class"] ) || !isset( $trace[2]["function"] ) )
        {
            $this->fail( "BROKEN TEST CASE. MISSING OBJECT OR FUNCTION IN BACKTRACE" );
        }
        return $trace[2]["class"] . "_" . $trace[2]["function"] . $index;
    }

    private function getTempBasePath()
    {
        if ( !isset( ezcImageConversionTestCase::$tempDirs[get_class( $this )] ) )
        {
            ezcImageConversionTestCase::$tempDirs[get_class( $this )] = $this->createTempDir( get_class( $this ) );
        }
        return ezcImageConversionTestCase::$tempDirs[get_class( $this )];
    }
}

?>
