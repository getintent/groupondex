<?php

namespace GetIntent\Groupondex;

/**
 * Requests to Groupon API
 *
 * @package Groupondex
 * @author Ivan Kornoukhov
 */
class Request
{
    /**
     * Groupon API request root
     */
    const GROUPON_ROOT = 'http://api.groupon.ru/v3/';

    /**
     * @var int $apiId Groupon API user identifier
     */
    private $apiId;

    /**
     * @var string $apiToken Groupon API token
     */
    private $apiToken;

    /**
     * @param int $apiId
     * @param string $apiToken
     */
    public function __construct($apiId, $apiToken)
    {
        $this
            ->setApiId($apiId)
            ->setApiToken($apiToken);
    }

    /**
     * @param int $apiId
     * @return $this
     */
    private function setApiId($apiId)
    {
        $this->apiId = $apiId;
        return $this;
    }

    /**
     * @return int
     */
    private function getApiId()
    {
        return $this->apiId;
    }

    /**
     * @param string $apiToken
     * @return $this
     */
    private function setApiToken($apiToken)
    {
        $this->apiToken = $apiToken;
        return $this;
    }

    /**
     * @return string
     */
    private function getApiToken()
    {
        return $this->apiToken;
    }

    /**
     * @param int|null $ts
     * @return array
     */
    private function getRequiredRequestParams($ts = null)
    {
        if (is_null($ts)) {
            $ts = time();
        }

        return array(
            'api_id' => $this->getApiId(),
            'signature' => md5($this->getApiId() . '_' . $this->getApiToken() . '_' . $ts),
            'timestamp' => $ts,
        );
    }

    /**
     * @param $action
     * @param array $params
     * @return array
     * @throws Exception
     */
    private function send($action, array $params = array())
    {
        if (!$curl = curl_init()) {
            throw new Exception('No curl connection');
        }

        $params = $this->getRequiredRequestParams() + $params;
        $url = self::GROUPON_ROOT . $action . '?' . http_build_query($params);

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $response = json_decode(curl_exec($curl), true);
        curl_close($curl);

        if (array_key_exists('error', $response)) {
            throw new Exception($response['error']);
        }

        return $response;
    }

    /**
     * Groupon Common API: Get all cities
     *
     * @see https://github.com/GrouponRussia/groupon-api/blob/master/common/cities.md
     *
     * @return array
     * @throws Exception
     */
    public function getAllCities()
    {
        $response = $this->send('cities.json');

        return $response['cities'];
    }

    /**
     * Groupon Common API: Get city info
     *
     * @see https://github.com/GrouponRussia/groupon-api/blob/master/common/cities.md
     *
     * @param $id int City ID
     * @return array
     * @throws Exception
     */
    public function getCity($id)
    {
        $id = (int)$id;
        $response = $this->send("cities/{$id}.json");

        return $response['city'];
    }

    /**
     * Groupon Partner API: Get city offers
     *
     * @see https://github.com/GrouponRussia/groupon-api/blob/master/partners/offers.md
     *
     * @param $cityId int City ID
     * @return array
     * @throws Exception
     */
    public function getOffersByCity($cityId)
    {
        $cityId = (int)$cityId;
        $response = $this->send("/cities/{$cityId}/offers.json");

        return $response['offers'];
    }

    /**
     * Groupon Partner API: Get offer info
     *
     * @see https://github.com/GrouponRussia/groupon-api/blob/master/partners/offers.md
     *
     * @param $id int Offer ID
     * @return array
     * @throws Exception
     */
    public function getOffer($id)
    {
        $id = (int)$id;
        $response = $this->send("offers/{$id}.json");

        return $response['offer'];
    }
}