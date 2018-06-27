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

namespace OpenSkos2;

use OpenSkos2\Namespaces\Org;
use OpenSkos2\Namespaces\OpenSkos;
use OpenSkos2\Namespaces\DcTerms;
use OpenSkos2\Namespaces\Rdf;
use OpenSkos2\Namespaces\VCard;
use OpenSkos2\Rdf\Resource;
use OpenSkos2\Rdf\Literal;
use OpenSkos2\Rdf\Uri;
use Rhumsaa\Uuid\Uuid;

/**
 * Representation of tenant.
 */
class Tenant extends Resource
{

    const TYPE = Org::FORMALORG;




    public function __get($name)
    {
        if ($name === 'code'){
            return $this->getCode()->getValue();
        }

        $trace = debug_backtrace();
        trigger_error(
            'Undefined property via __get(): ' . $name .
            ' in ' . $trace[0]['file'] .
            ' on line ' . $trace[0]['line'],
            E_USER_NOTICE);
        return null;
    }
  
    public function __construct($uri = null)
    {
        parent::__construct($uri);
        $this->setProperty(Rdf::TYPE, new Uri(self::TYPE));
    }

    /**
     * Is the notation required to be unique per tenant, not per scheme.
     * @return bool
     */
    public function isNotationUniquePerTenant()
    {
        $val = $this->getPropertySingleValue(OpenSkos::NOTATIONUNIQUEPERTENANT);
        return $this->toBool($val);
    }

    /**
     * If the notation has to be generated.
     * @return bool
     */
    public function isNotationAutoGenerated()
    {
        $val = $this->getPropertySingleValue(OpenSkos::NOTATIONAUTOGENERATED);
        return $this->toBool($val);
    }

    /**
     * @return bool
     */
    public function isEnableSkosXl()
    {
        $val = $this->getPropertySingleValue(OpenSkos::ENABLESKOSXL);
        return $this->toBool($val);
    }

    /**
     * @return bool
     */
    public function isEnableStatusesSystems()
    {
        $val = $this->getPropertySingleValue(OpenSkos::ENABLESTATUSSESSYSTEMS);
        return $this->toBool($val);
    }
    /**
     * @return bool
     */
    public function getEnableSkosXl()
    {
        $val = $this->getPropertySingleValue(OpenSkos::ENABLESKOSXL);
        return $val;
    }

    /**
     * @return bool
     */
    public function getEnableStatusesSystems()
    {
        $val = $this->getPropertySingleValue(OpenSkos::ENABLESTATUSSESSYSTEMS);
        return $val;
    }

    public function getName()
    {
        return $this->getPropertySingleValue(OpenSkos::NAME);
    }

    public function getCode()
    {
        return $this->getPropertySingleValue(OpenSkos::CODE);
    }

    public function getOrganisationUnit()
    {
        return $this->getPropertySingleValue(VCard::ORGUNIT);
    }

    public function getEmail()
    {
        return $this->getPropertySingleValue(VCard::EMAIL);
    }

    public function getStreetAddress()
    {
        return $this->getPropertySingleValue(VCard::ADR);
    }

    public function getLocality()
    {
        return $this->getPropertySingleValue(VCard::LOCALITY);
    }

    public function getPostalCode()
    {
        return $this->getPropertySingleValue(VCard::PCODE);
    }

    public function getCountryName()
    {
        return $this->getPropertySingleValue(VCard::COUNTRY);
    }

    public function getWebsite()
    {
        return $this->getPropertySingleValue(OpenSkos::WEBPAGE);
    }


    public function getPublisherUri()
    {
        return $this->uri;
    }

    public function getTenant()
    {
        return $this->getCode();
    }

    public function getSet()
    {
        return null;
    }

    /**
     * Ensures the concept has metadata for tenant, set, creator, date submited, modified and other like this.
     * @param \OpenSkos2\Tenant $tenant
     * @param \OpenSkos2\Set $set
     * @param \OpenSkos2\Person $person
     * @param \OpenSkos2\PersonManager $personManager
     * @param \OpenSkos2\LabelManager | null  $labelManager
     * @param  \OpenSkos2\Rdf\Resource | null $existingResource,
     * optional $existingResource of one of concrete child types used for update
     * override for a concerete resources when necessary
     */
    public function ensureMetadata(
        \OpenSkos2\Tenant $tenant = null,
        \OpenSkos2\Collection $set = null,
        \OpenSkos2\Person $person = null,
        \OpenSkos2\PersonManager $personManager = null,
        \OpenSkos2\SkosXl\LabelManager $labelManager = null,
        $existingConcept = null,
        $forceCreationOfXl = false
    ) {
    
        $nowLiteral = function () {
            return new Literal(date('c'), null, Literal::TYPE_DATETIME);
        };


        $uuid = Uuid::uuid4();
        $this->uri = "http://tenant/{$uuid}";

        $forFirstTimeInOpenSkos = [
            OpenSkos::UUID => new Literal($uuid),
            DcTerms::DATESUBMITTED => $nowLiteral()
        ];

        foreach ($forFirstTimeInOpenSkos as $property => $defaultValue) {
            if (!$this->hasProperty($property)) {
                $this->setProperty($property, $defaultValue);
            }
        }

        if($person !== null) { //Person may be null, because a user links to a tenant. Chicken and Egg
            $this->resolveCreator($person, $personManager);
            $this->setModified($person);
        }
    }

    // TODO: discuss the rules for generating Uri's for non-concepts
    protected function assembleUri(
        \OpenSkos2\Tenant $tenant = null,
        \OpenSkos2\Collection $collection = null,
        $uuid = null,
        $notation = null,
        $customInit = null
    ) {
        
        if (empty($customInit)) {
            $apiOptions = OpenSKOS_Application_BootstrapAccess::getOption('api');
            $prefix=$apiOptions['baseUri'];
            return $prefix."/".$uuid;
        }
        if (count($customInit)===0) {
            $apiOptions = OpenSKOS_Application_BootstrapAccess::getOption('api');
            $prefix=$apiOptions['baseUri'];
            return $prefix."/".$uuid;
        }
        $baseUri = $customInit['uriprefix'];
        return $baseUri . "" . $uuid;
    }

    /**
     * @param $code string Tenant Code
     */
    public static function codeToUri($code){

        $query = sprintf('SELECT ?uri WHERE { ?uri  <%s> "%s" } ' , OpenSkos::CODE, $code);
        $response = self::query($query);
        $items = [];
        foreach ($sparqlQueryResult as $resource) {
            $uri = $resource->uri->getUri();
            $name = $resource->name->getValue();
            $items[$name] = $uri;
        }
        return $items;
        return $result;


    }
    /**
     * @return Zend_Form
     */
    public function getForm()
    {
        static $form;
        if (null === $form) {
            $form = new \Zend_Form();
            $form
                    ->addElement('text', 'name', array('label' => _('Name'), 'required' => true))
                    ->addElement('text', 'organisationUnit', array('label' => _('Organisation unit')))
                    ->addElement('text', 'website', array('label' => _('Website')))
                    ->addElement('text', 'email', array('label' => _('E-mail')))
                    ->addElement('text', 'streetAddress', array('label' => _('Street Address')))
                    ->addElement('text', 'locality', array('label' => _('Locality')))
                    ->addElement('text', 'postalCode', array('label' => _('Postal Code')))
                    ->addElement('text', 'countryName', array('label' => _('Country Name')))
                    ->addElement('checkbox', 'enableStatusesSystem', array(
                        'label' => _('Enable the statuses system for concepts'),
                        'required' => false
                    ))
                    ->addElement('checkbox', 'enableSkosXl', array(
                        'label' => _('Enable the use of Skos-XL over simple labels'),
                        'required' => false
                    ))
                    ->addElement('submit', 'submit', array('label' => _('Submit')))
            ;
            $form->getElement('email')->addValidator(new \Zend_Validate_EmailAddress());


            $form->getElement('enableStatusesSystem')->getDecorator('Label')
                    ->setTagClass('decorator-with-helptext hand-cursor')
                    ->setOption('data-helptext-id', 'decorator-helptext-statuses');

            $form->getElement('enableSkosXl')->getDecorator('Label')
                    ->setTagClass('decorator-with-helptext hand-cursor')
                    ->setOption('data-helptext-id', 'decorator-helptext-skosxl');

            $form->setDefaults($this->datatoArray());
        }
        return $form;
    }

    public function dataToArray()
    {
        $dataOut = array();

        $dataOut['name'] = $dataOut['id'] = $this->getName();
        $dataOut['organisationUnit'] = $this->getOrganisationUnit();
        $dataOut['website'] = $this->getWebsite();
        $dataOut['email'] = $this->getEmail();
        $dataOut['streetAddress'] = $this->getStreetAddress();
        $dataOut['locality'] = $this->getLocality();
        $dataOut['postalCode'] = $this->getPostalCode();
        $dataOut['countryName'] = $this->getCountryName();

        $dataOut['enableStatusesSystem'] = $this->getEnableStatusesSystems( );
        $dataOut['enableSkosXl'] = $this->getEnableSkosXl( );

        return $dataOut;

    }

    public function arrayToData($dataIn)
    {

        foreach ($dataIn as $key => $val){
            switch($key){
                case 'code':
                    $this->setProperty(OpenSkos::CODE, new Literal($val));
                    break;
                case 'name':
                    $this->setProperty(OpenSkos::NAME, new Literal($val));
                    break;
                case 'organisationUnit':
                    $this->setProperty(VCard::ORGUNIT, new Literal($val));
                    break;
                case 'email':
                    $this->setProperty(VCard::EMAIL, new Literal($val));
                    break;
                case 'website':
                    $this->setProperty(OpenSkos::WEBPAGE, new Literal($val));
                    break;
                case 'streetAddress':
                    $this->setProperty(VCard::ADR, new Literal($val));
                    break;
                case 'locality':
                    $this->setProperty(VCard::LOCALITY, new Literal($val));
                    break;
                case 'postalCode':
                    $this->setProperty( VCard::PCODE, new Literal($val));
                    break;
                case 'countryName':
                    $this->setProperty(VCard::COUNTRY, new Literal($val));
                    break;
                case 'enableStatusesSystem':
                    $this->setProperty(OpenSkos::ENABLESTATUSSESSYSTEMS, new Literal($val));
                    break;
                case 'enableSkosXl':
                    $this->setProperty(OpenSkos::ENABLESKOSXL, new Literal($val));
                    break;
            }
        }
        return $this;
    }

}
