<?php

namespace App;

use MongoDB\BSON\Regex;
use MongoDB\BSON\UTCDatetime;

/**
 * Class Company
 * @package App
 */
class Company
{
    private $client;
    private $collection;
    private $config;

    public function __construct()
    {
        $this->config = require_once __DIR__ . "/../config.php";
        $config = $this->config;
        $databaseName = $config['database']['name'];
        $index = $config['database']['companyTable'];
        $this->client = new \MongoDB\Client($config['database']['host']);

        $this->collection = $this->client->$databaseName->$index;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }
    
    /**
     * @param $dayPeriod
     * @param $dataFilter
     * @param array $excludedCompany
     * @return \MongoDB\Driver\Cursor
     */
    public function search($dayPeriod, $dataFilter, array $excludedCompany)
    {
        $filter = $this->getQueryFromFilter($dayPeriod, $dataFilter, $excludedCompany);

        return $this->collection->find($filter, ['limit' => 3000, 'sort' => ['DateTime' => -1]]);
    }

    /**
     * @param $dayPeriod
     * @param $dataFilter
     * @param $excludedCompany
     * @return array
     */
    public function getQueryFromFilter($dayPeriod, $dataFilter, $excludedCompany)
    {
        $time = strtotime('-' . $dayPeriod . ' days');

        $filter = [
            'DateTime' => [
                '$gte' => new UTCDatetime($time * 1000),
                '$lte' => new UTCDatetime(time() * 1000),
            ],
            "IssuerName" => [
                '$nin' => $excludedCompany
            ]
        ];

        if ($dataFilter == '') {
            return $filter;
        } elseif ($dataFilter == 'mna') {
            $filter['AnnTitle'] = [
                '$regex' => new Regex('Tender|Acquisition|Takeover|Offer', 'i'),
            ];
        } elseif ($dataFilter == 'hot_mna') {

        } elseif ($dataFilter == 'trading_halt') {
            $filter['AnnTitle'] = [
                '$regex' => new Regex('Request for Trading Halt', 'i'),
            ];
        }

        return $filter;
    }

    /**
     * @param $filter
     * @return string[]
     */
    public function getCompanyList($filter)
    {
        return $this->collection->distinct('IssuerName', $filter);
    }
}
