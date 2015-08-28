<?php
/**
 * Created by PhpStorm.
 * User: jsmit
 * Date: 26/08/2015
 * Time: 14:34
 */

namespace OpenSkos2\Rdf;


use OpenSkos2\Bridge\EasyRdf;
use OpenSkos2\Exception\ResourceAlreadyExistsException;
use OpenSkos2\Exception\ResourceNotFoundException;

class ResourceManager
{
    /**
     * @var \EasyRdf_Sparql_Client
     */
    protected $client;

    /**
     * @var string
     */
    private $graph;

    /**
     * ResourceManager constructor.
     * @param \EasyRdf_Sparql_Client $client
     * @param string $graph
     */
    public function __construct(\EasyRdf_Sparql_Client $client, $graph = null)
    {
        $this->client = $client;
        $this->graph = $graph;
    }

    /**
     * @param Resource $resource
     * @throws ResourceAlreadyExistsException
     */
    public function insert(Resource $resource)
    {
        $this->client->insert(EasyRdf::resourceToGraph($resource));
    }

    /**
     * @param Resource $resource
     * @throws ResourceNotFoundException
     */
    public function update(Resource $resource)
    {

    }

    /**
     * @param Resource $resource
     */
    public function delete(Resource $resource)
    {

    }

    /**
     * @param string $query
     * @return ResourceCollection
     */
    public function fetch($query = null)
    {

    }
    
    /**
     * @param string $uri
     * @return Resource
     * @throws ResourceNotFoundException
     */
    public function fetchByUri($uri)
    {
        // @TODO Add the graph here in the query.
        $result = $this->client->query('DESCRIBE <' . $uri . '>');
        $resources = EasyRdf::graphToResourceCollection($result);
        
        if (count($resources) == 0) {
            throw new ResourceNotFoundException(
                'The requested resource <' . $uri . '> was not found.'
            );
        }
        
        // We can not have more than one resource with same uri.
        
        return $resources[0];
    }
}
