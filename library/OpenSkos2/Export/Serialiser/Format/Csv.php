<?php

/**
 * OpenSKOS
 *
 * LICENSE
 *
 * This source file is subject to the GPLv3 license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.txt
 *
 * @category   OpenSKOS
 * @package    OpenSKOS
 * @copyright  Copyright (c) 2015 Picturae (http://www.picturae.com)
 * @author     Picturae
 * @license    http://www.gnu.org/licenses/gpl-3.0.txt GPLv3
 */

namespace OpenSkos2\Export\Serialiser\Format;

use OpenSkos2\Rdf\Resource;
use OpenSkos2\Export\Serialiser\FormatAbstract;

class Csv extends FormatAbstract
{
    /**
     * Creates the header of the output.
     * @return string
     */
    public function printHeader()
    {
//        if (empty($propertiesToExport)) {
//            $propertiesToExport = self::getExportableConceptFields();
//        }
        
        // @TODO Beautify properties
        return $this->stringPutCsv($this->getPropertiesToSerialise());
    }
    
    /**
     * Serialises a single resource.
     * @return string
     */
    public function printResource(Resource $resource)
    {
        return $this->stringPutCsv($this->prepareResourceDataForCsv($resource));
    }
    
    /**
     * Creates the footer of the output.
     * @return string
     */
    public function printFooter()
    {
        return '';
    }
    
    /**
     * Prepare concept data for exporting in csv format.
     * 
     * @param Api_Models_Concept $concept
     * @param array $propertiesToExport
     * @return array The result concept data
     */
    protected function prepareResourceDataForCsv(Resource $resource)
    {
        $resourceData = array();

        foreach ($this->getPropertiesToSerialise() as $property) {
            if ($resource->hasProperty($property)) {
                $values = $resource->getProperty($property);
                if (count($values) > 1) {
                    $resourceData[$property] = implode(';', $values);
                } else {
                    $resourceData[$property] = (string)$values[0];
                }
            } else {
                $resourceData[$property] = '';
            }
        }

        return $resourceData;
    }
    
    /**
     * Puts csv in string.
     * @param array $data
     * @return string
     */
    public function stringPutCsv($data)
    {
        $streamHandle = fopen('php://memory', 'rw');
        fputcsv($streamHandle, $data);
        rewind($streamHandle);
        $result = stream_get_contents($streamHandle);
        fclose($streamHandle);
        return $result;
    }
}