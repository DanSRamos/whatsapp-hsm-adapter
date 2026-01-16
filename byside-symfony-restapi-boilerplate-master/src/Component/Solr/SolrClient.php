<?php

namespace App\Component\Solr;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Uri;

// TODO: add logs
// TODO: move headers params to config
class SolrClient
{
    /** @var mixed[] */
    public $cluster;
    public $node;
    public $getHttpClient;
    public const GET = 'get';

    protected HttpClient $httpClient;

    protected static array $defaultHeaders = [
        'Accept' => 'application/json',
    ];

    protected static array $guzzleOptions = [
        'timeout' => 60,
        'connect_timeout' => 60,
        'debug' => true,
    ];

    protected static array $defaultClientOptions = [
        'retries' => 5,
        'handler' => 'query',
    ];

    protected static array $defaultQueryParams = [
        'wt' => 'json',
    ];

    protected array $options = [];
    protected string $context = '/solr'; // TODO

    /**
     * Constructor method.
     *
     * @param array      $cluster list of nodes of Solr cluster
     * @param array      $options list of options to pass to the client
     * @param mixed|null $logger
     */
    public function __construct(array $cluster = [], array $options = [], protected $logger = null)
    {
        $this->options = array_merge(self::$defaultClientOptions, $options);
        $this->cluster = $cluster;
        $this->node = $this->cluster[array_rand($this->cluster, 1)];
    }

    /**
     * This method creates the HttpClient instance in case that doesn't exists
     * previously.
     */
    protected function getHttpClient(): HttpClient
    {
        if (empty($this->httpClient)) {
            $this->httpClient = new HttpClient(
                [
                    'headers' => self::$defaultHeaders,
                    'params' => self::$guzzleOptions,
                ]
            );
        }

        return $this->httpClient;
    }

    /**
     * Performs a search in Solr cluster.
     *
     * @param array  $query      the query to be searched
     * @param string $collection the default collection to performs the search
     * @param mixed  $method
     *
     * @return array the result of the search
     */
    public function search(array $query, string $collection, string $method = self::GET): mixed
    {
        $retries = 0;
        $success = false;
        $response = null;

        do {
            try {
                $request = new Request($method, $this->getBaseUrl($collection));
                $uri = $this->buildGetQuery($request, $query);
                $response = $this->getHttpClient->request($method, $uri);

                if ($response->getStatusCode() == 200) {
                    $success = true;

                    break;
                }
            } catch (\Exception) {
                $this->node = $this->randomizeNode();
            }

            ++$retries;

            usleep(250000 * $retries);
        } while ($this->options['retries'] > $retries);

        if ($success == true) {
            return $this->buildResponse($response);
        }

        throw new \Exception(sprintf('SolrClient exception - max retries exceeded caused by: %s', (isset($e)) ? $e->getMessage() : $response->getBody()), 0);
    }

    /**
     * Get the base url to perform the search in Solr's cluster.
     *
     * @param string $collection the default collection to performs the search
     */
    public function getBaseUrl(string $collection): string
    {
        return sprintf(
            'http://%s%s/%s/%s?',
            $this->node,
            $this->context,
            $collection,
            $this->options['handler']
        );
    }

    /**
     * In case of error randomize the failed node to other.
     */
    public function randomizeNode()
    {
        $retries = 10;
        $node = false;

        do {
            $key = array_rand($this->cluster, 1);
            $node = $this->cluster[$key];
            --$retries;
        } while (($node == $this->node) && $retries > 0);

        return $node;
    }

    private function buildGetQuery(Request $request, array $query)
    {
        $uri = $request->getUri();

        $queryParams = array_merge($this->defaultQueryParams, $query);

        foreach ($queryParams as $key => $value) {
            $uri = Uri::withQueryValue($uri, $key, $value);
        }

        return $uri;
    }

    /**
     * Builds de response in the configurated format.
     *
     * @param array $response guzzle Response format
     */
    private function buildResponse(Response $response): mixed
    {
        return json_decode($response->getBody()->getContents(), true);
    }
}
