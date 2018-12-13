<?php

namespace App\User;

use PageController;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\ClientException;
use SilverStripe\Security\Member;

class UserCreatorPageController extends PageController
{

    /**
     * @var GuzzleHttp\Client
     */
    public $client;

    /**
     * @var string
     */
    public $url;

    /**
     * @var Member
     */
    private $member;

    private static $dependencies = [
        'client' => '%$UserGenClient',
        'url' => 'https://randomuser.me/api/',
    ];

    public function init()
    {
        parent::init();
        $data = $this->getUserData();
        $this->updateMember($data['results'][0]);
    }

    /**
     * Makes API call to get data on a random user
     * @return array
     */
    public function getUserData()
    {
        try {
            $response = $this->client->request('GET', $this->url);
        } catch (ConnectException $e) {
            $this->httpError(503, sprintf('Unable to connect to %s, error: %s', $this->url, $e->getMessage()));
        } catch (ClientException $e) {
            $this->httpError(503, sprintf('Unable to connect to %s, error: %s', $this->url, $e->getMessage()));
        }

        if($response->getStatusCode() !== 200) {
            $this->httpError(503, sprintf('Non-200 response code received from %s: %s', $this->url, $response->getStatusCode()));
        }

        $result = json_decode($response->getBody(), true);
        if (!$result) {
            $this->httpError(503, sprintf('Unable to parse response as JSON, or empty response body from %s', $this->url));
        }

        return $result;
    }

    /**
     * Updates a Member based on supplied data
     * first name, last name, email, profile photo and a Cell number should be updated
     * @param array $data
     * @return type
     */
    public function updateMember($data)
    {
        $reqFields = ['name', 'email', 'cell', 'picture'];
        foreach ($reqFields as $field) {
            if (empty($data[$field])) {
                $this->httpError(503, sprintf('Missing "%s" in API data', $field));
            }
        }

        $member = Member::create();

        $member->FirstName = $data['name']['first'];
        $member->Surname = $data['name']['last'];
        $member->Email = $data['email'];
        $member->Cell = $data['cell'];
        $member->ProfilePic = $data['picture']['large'];

        $member->write();

        $this->member = $member;
    }

    /**
     * @return Member
     */
    public function getGeneratedMember()
    {
        return $this->member;
    }
}
