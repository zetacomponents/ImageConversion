<?php
/**
 * File containing the ezcImageFileNameInvalidException.
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
 * @version //autogen//
 * @copyright Copyright (C) 2005-2010 eZ Systems AS. All rights reserved.
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache License, Version 2.0
 */

/**
 * Thrown if a given file name contains illegal characters (', ", $).
 *
 * @package ImageConversion
 * @version //autogen//
 */
class ezcImageFileNameInvalidException extends ezcImageException
{
    /**
     * Creates a new ezcImageFileNameInvalidException.
     * 
     * @param string $file The invalid file name.
     * @return void
     */
    function __construct( $file )
    {
        parent::__construct( "The file name '{$file}' contains an illegal character (', \", $)." );
    }
}

?>
