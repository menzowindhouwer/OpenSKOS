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

///Users/olha/WorkProjects/open-skos-2/OpenSKOS2tempMeertens/library/OpenSkos2/Concept.php
namespace OpenSkos2;

use Exception;
use OpenSkos2\ConceptManager;
use OpenSkos2\Namespaces\DcTerms;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Namespaces\Rdf;
use OpenSkos2\Namespaces\Skos;
use OpenSkos2\Rdf\Literal;
use OpenSkos2\Rdf\Resource;
use OpenSkos2\Rdf\Uri;
use OpenSkos2\Tenant;

require_once dirname(__FILE__) . '/config.inc.php';

class Concept extends Resource
{
    const TYPE = 'http://www.w3.org/2004/02/skos/core#Concept';

    /**
     * All possible statuses
     */
    const STATUS_CANDIDATE = 'candidate';
    const STATUS_APPROVED = 'approved';
    const STATUS_REDIRECTED = 'redirected';
    const STATUS_NOT_COMPLIANT = 'not_compliant';
    const STATUS_REJECTED = 'rejected';
    const STATUS_OBSOLETE = 'obsolete';
    const STATUS_DELETED = 'deleted';
    
    /**
     * Get list of all available concept statuses.
     * @return array
     */
    public static function getAvailableStatuses()
    {
        return [
            self::STATUS_CANDIDATE,
            self::STATUS_APPROVED,
            self::STATUS_REDIRECTED,
            self::STATUS_NOT_COMPLIANT,
            self::STATUS_REJECTED,
            self::STATUS_OBSOLETE,
            self::STATUS_DELETED,
        ];
    }
    
    public static $classes = array(
        'ConceptSchemes' => [
            Skos::CONCEPTSCHEME,
            Skos::INSCHEME,
            Skos::HASTOPCONCEPT,
            Skos::TOPCONCEPTOF,
        ],
        'LexicalLabels' => [
            Skos::ALTLABEL,
            Skos::HIDDENLABEL,
            Skos::PREFLABEL,
        ],
        'Notations' => [
            Skos::NOTATION,
        ],
        'DocumentationProperties' => [
            Skos::CHANGENOTE,
            Skos::DEFINITION,
            Skos::EDITORIALNOTE,
            Skos::EXAMPLE,
            Skos::HISTORYNOTE,
            Skos::NOTE,
            Skos::SCOPENOTE,
        ],
        'SemanticRelations' => [
            Skos::BROADER,
            Skos::BROADERTRANSITIVE,
            Skos::NARROWER,
            Skos::NARROWERTRANSITIVE,
            Skos::RELATED,
            Skos::SEMANTICRELATION,
        ],
        'SkosCollections' =>  [ 
            OpenSkos::INSKOSCOLLECTION, 
            Skos::ORDEREDCOLLECTION, // ??
            Skos::MEMBER, //??
            Skos::MEMBERLIST, //? 
        ],
        'MappingProperties' => [
            Skos::BROADMATCH,
            Skos::CLOSEMATCH,
            Skos::EXACTMATCH,
            Skos::MAPPINGRELATION,
            Skos::NARROWMATCH,
            Skos::RELATEDMATCH,
        ],
    );

    /**
     * Resource constructor.
     * @param string $uri , optional
     */
    public function __construct($uri = null)
    {
        parent::__construct($uri);
        $this->addProperty(Rdf::TYPE, new Uri(self::TYPE));
    }

    
    
     public function getSkosCollection()
    {
        if (!$this->hasProperty(OpenSkos::INSKOSCOLLECTION)) {
            return null;
        } else {
            return $this->getProperty(OpenSkos::INSKOSCOLLECTION)[0]->getValue();
        }
    }
    
    /**
     * Check if the concept is deleted
     *
     * @return boolean
     */
    public function isDeleted()
    {
        if ($this->getStatus() === self::STATUS_DELETED) {
            return true;
        }
        return false;
    }

    /**
     * Gets preview title for the concept.
     * @param string $language
     * @return string
     * @throws Exception
     */
    public function getCaption($language = null)
    {
        if ($this->hasPropertyInLanguage(Skos::PREFLABEL, $language)) {
            return $this->getPropertyFlatValue(Skos::PREFLABEL, $language);
        } else {
            return $this->getPropertyFlatValue(Skos::PREFLABEL);
        }
    }
    
    /**
     * Get openskos:uuid if it exists
     * Identifier for backwards compatability. Always use uri as identifier.
     * @return string|null
     */
    public function getUuid()
    {
        if ($this->hasProperty(OpenSkos::UUID)) {
            return (string)$this->getPropertySingleValue(OpenSkos::UUID);
        } else {
            return null;
        }
    }

    /**
     * Get tenant
     *
     * @return Uri (good) or Literal (obsolete)
     */
    public function getTenant()
    {
        $values = $this->getProperty(OpenSkos::TENANT);
        if (isset($values[0])) {
            return $values[0];
        }else{
            return new Literal(UNKNOWN);
        }
    }
    
  
    
    /**
     * Checks if the concept is top concept for the specified scheme.
     * @param string $conceptSchemeUri
     * @return bool
     */
    public function isTopConceptOf($conceptSchemeUri)
    {
        if (!$this->isPropertyEmpty(Skos::TOPCONCEPTOF)) {
            return in_array($conceptSchemeUri, $this->getProperty(Skos::TOPCONCEPTOF));
        } else {
            return false;
        }
    }
    
    /**
     * Does the concept have any relations or mapping properties.
     * @return bool
     */
    public function hasAnyRelations()
    {
        $relationProperties = array_merge(
            self::$classes['SemanticRelations'],
            self::$classes['MappingProperties']
        );
        foreach ($relationProperties as $relationProperty) {
            if (!$this->isPropertyEmpty($relationProperty)) {
                return true;
            }
        }
        return false;
    }
    
    
    // $existingConcept is null when a concept is created, otherwise it is non-null for "update"
    public function addMetadata($userUri, $params, $existingConcept) {
        $nowLiteral = function () {
            return new Literal(date('c'), null, Literal::TYPE_DATETIME);
        };
        
        $setUri = $this->deriveSetUri($params, $existingConcept);
        if ($setUri !== null) { // othewrise it is either set in this or not derivable
           $this->setProperty(OpenSkos::SET, $setUri);
        }
        
        if ($existingConcept === null) { // a completely new concept under creation
            
            $this->setProperty(DcTerms::CREATOR, new Uri($userUri));
            $this->setProperty(DcTerms::DATESUBMITTED, $nowLiteral());
            $this->unsetProperty(DcTerms::DATEACCEPTED);
            $this->unsetProperty(OpenSkos::ACCEPTEDBY);
            $this->unsetProperty(OpenSkos::DATE_DELETED);
            $this->unsetProperty(OpenSkos::DELETEDBY);
            $this->unsetProperty(OpenSkos::STATUS);
            $this->addProperty(OpenSkos::STATUS, new Literal(Concept::STATUS_CANDIDATE));
        } else {
            
            $this->setProperty(DcTerms::MODIFIED, $nowLiteral());
            $this->addProperty(DcTerms::CONTRIBUTOR, new Uri($userUri));
            
            if ($existingConcept->getStatus() !== $this->getStatus()) {
                $this->unsetProperty(DcTerms::DATEACCEPTED);
                $this->unsetProperty(OpenSkos::ACCEPTEDBY);
                $this->unsetProperty(OpenSkos::DATE_DELETED);
                $this->unsetProperty(OpenSkos::DELETEDBY);

                switch ($this->getStatus()) {
                    case Concept::STATUS_APPROVED:
                        $this->addProperty(DcTerms::DATEACCEPTED, $nowLiteral());
                        $this->addProperty(OpenSkos::ACCEPTEDBY, new Uri($userUri));
                        break;
                    case Concept::STATUS_DELETED:
                        $this->addProperty(OpenSkos::DATE_DELETED, $nowLiteral());
                        $this->addProperty(OpenSkos::DELETEDBY, new Uri($userUri));
                        break;
                }
            }
            
            $this->unsetProperty(OpenSkos::UUID);
            $this->setProperty(OpenSkos::UUID, new Literal($existingConcept->getUuid()));
            
            $creators = $existingConcept->getProperty(DcTerms::CREATOR);
            if (count($creators) < 1) {
                $this->setProperty(DcTerms::CREATOR, new Literal(UNKNOWN));
            } else {
                $this->setProperty(DcTerms::CREATOR, $creators[0]);
            }
            $dateSubmitted = $existingConcept->getProperty(DcTerms::DATESUBMITTED);
            if (count($dateSubmitted) > 0) {
                $this->setProperty(DcTerms::DATESUBMITTED, new Literal($dateSubmitted[0], null, Literal::TYPE_DATETIME));
            }
        }
        
    }
    
    
    /**
     * Generate notation unique per tenant. Based on tenant notations sequence.
     * @return string
     */
    public function selfGenerateNotation(Tenant $tenant, ConceptManager $conceptManager)
    {
        // @TODO Move that and uri generate to separate class.
        // @TODO A raise condition is possible. The validation will fail in that case - so should not be problem.
        
        $notation = 1;
        
        $maxNumericNotation = $conceptManager->fetchMaxNumericNotation($tenant);
        if (!empty($maxNumericNotation)) {
            $notation = $maxNumericNotation + 1;
        }
        
        $this->addProperty(
            Skos::NOTATION,
            new Literal($notation)
        );
    }
    
}
