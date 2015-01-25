<?php

namespace Surikat\Model\Solarium\Support\DataFixtures;

use Surikat\Model\Solarium\Core\Client\Client;

/**
 * @author Baldur Rensch <brensch@gmail.com>
 */
class Executor 
{
    /**
     * @var Client
     */
    private $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @param FixtureInterface[] $fixtures
     */
    public function execute(array $fixtures)
    {
        foreach ($fixtures as $fixture) {
            $fixture->load($this->client);
        }
    }
}
