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

namespace OpenSkos2\Validator\Concept;

use OpenSkos2\Concept;
use OpenSkos2\Namespaces\Skos;
use OpenSkos2\Validator\AbstractConceptValidator;

class SinglePrefLabel extends AbstractConceptValidator
{

    /**
     * @param Concept $concept
     * @return bool
     */
    protected function validateConcept(Concept $concept)
    {
        foreach ($concept->retrieveLanguages() as $language) {
            $labels = $concept->retrievePropertyInLanguage(Skos::PREFLABEL, $language);
            if (count($labels) > 1) {
                $this->errorMessages[] = 'Only single pref label per language is allowed. '
                    . 'Found ' . count($labels) . ' for ' . $language;
                return false;
            }
        }
        return true;
    }
}
