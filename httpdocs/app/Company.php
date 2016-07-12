<?php

/**
 * This file is part of the Huat package.
 */

namespace App;

use MongoDB\BSON\Regex;
use MongoDB\BSON\UTCDatetime;

/**
 * Class Company
 * @package App
 * @author Sabo Duy Nguyen <nhduy88@gmail.com>
 * @website http://cipherpols.com/
 */
class Company
{
    const MNA = 'mna';
    const HOT_MNA = 'hot_mna';
    const TRADING_HALT = 'trading_halt';

    private $client;
    private $collection;
    private $config;
    private $searchConditions;

    /**
     * Company constructor.
     */
    public function __construct()
    {
        $this->searchConditions = [
            static::MNA => [
                '$regex' => new Regex('Tender|Acquisition|Takeover|Offer', 'i'),
            ],
            static::HOT_MNA => [
                '$regex' => new Regex('Request for Trading Halt', 'i'),
            ],
            static::TRADING_HALT => [
                '$regex' => new Regex('Request for Trading Halt', 'i'),
            ],
        ];
        $config = require_once __DIR__ . "/../config.php";
        $databaseName = $config['database']['name'];
        $index = $config['database']['companyTable'];
        $this->client = new \MongoDB\Client($config['database']['host']);
        $this->config = $config;

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
        $result = $this->collection->find($filter, ['limit' => 3000, 'sort' => ['DateTime' => -1]]);
        $result = $result->toArray();
        
        if ($dataFilter == static::HOT_MNA) {
            $result = $this->searchHotMnA($result);
        }

        return $result;
    }

    /**
     * @param $dayPeriod
     * @param $dataFilter
     * @param $excludedCompany
     * @return array
     * @throws \Exception
     */
    public function getQueryFromFilter($dayPeriod, $dataFilter, $excludedCompany)
    {
        if ($dayPeriod > 90) {
            throw new \Exception('Invalid day period');
        }
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

        if ($dataFilter != '') {
            $filter['AnnTitle'] = $this->searchConditions[$dataFilter];
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

    /**
     * Hot M&A mean it is M&A and have Trading Halt with in 3 days
     * @param $tradingHaltData
     * @return mixed
     */
    private function searchHotMnA($tradingHaltData)
    {
        /**
         * @var \DateTime $dateTime
         */
        $timezone = $this->getConfig()['timezone'];
        $mnaResult = [];

        foreach ($tradingHaltData as $i => $company) {

            $dateTime = $company['DateTime']->toDateTime();
            $dateTime->setTimezone(new \DateTimeZone($timezone));

            $startDate = clone $dateTime;
            $startDate = $startDate->modify('-3 days');
            $startDate = $startDate->format('Y-m-d 00:00:00');

            $endDate = clone $dateTime;
            $endDate = $endDate->modify('+3 days');
            $endDate = $endDate->format('Y-m-d 23:59:59');

            $filter = [
                'AnnTitle' => $this->searchConditions[static::MNA],
                'DateTime' => [
                    '$gte' => new UTCDatetime(strtotime($startDate) * 1000),
                    '$lte' => new UTCDatetime(strtotime($endDate)  * 1000),
                ],
                "IssuerName" => $company['IssuerName']
            ];

            $result = $this->collection->find($filter, ['limit' => 3000, 'sort' => ['DateTime' => -1]]);
            $result = $result->toArray();

            if (empty($result)) {
                unset($tradingHaltData[$i]);
            } else {
                $mnaResult = array_merge($mnaResult, $result);
            }
        }

        $tradingHaltData = array_merge($tradingHaltData, $mnaResult);
        usort($tradingHaltData, function ($a, $b) {
            return ($a['DateTime']->toDateTime()->getTimestamp() > $b['DateTime']->toDateTime()->getTimestamp()) ? -1 : 1;
        });

        return $tradingHaltData;
    }
}
