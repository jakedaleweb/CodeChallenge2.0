<?php

namespace App\Tests;

use App\User\UserCreatorPageController;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use SilverStripe\Control\HTTPResponse_Exception;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;

class UserCreatorPageControllerTest extends SapphireTest {

    /**
     * @var array
     */
    protected $history;

    /**
     * @var array
     */
    protected $data;

    /**
     * @var array
     */
    protected $badData;

    public function setUp()
    {
        parent::setUp();
        $this->history = [];
        $this->data = [
            'name' => [
                'first' => 'Barry',
                'last' => 'Larry',
            ],
            'cell' => '+642 987 7654',
            'picture' => [
                'large' => 'https://i.kym-cdn.com/entries/icons/original/000/023/879/dilolhijakestorteddd.jpg'
            ],
            'email' => 'barry.larry@silverstroope.com',
        ];

        $this->badData = [
            'name' => [
                'first' => 'Barry',
                'last' => 'Larry',
            ],
            'cell' => '+642 987 7654',
            'picture' => [
                'large' => 'https://i.kym-cdn.com/entries/icons/original/000/023/879/dilolhijakestorteddd.jpg'
            ],
        ];
    }

    /**
     * Utility function to setup a mock client with responses.
     *
     * @param mixed $statusCode
     * @param mixed $body
     * @param mixed $headers
     */
    protected function setMockResponses($statusCode, $headers = [], $body = null, $fn = null)
    {
        $responses[] = new Response($statusCode, $headers, $body);
        $mock = new MockHandler($responses, $fn);
        $handler = HandlerStack::create($mock);
        $handler->push(Middleware::history($this->history));
        $client = new Client(['handler' => $handler]);
        // replace the default factory with our own
        Injector::inst()->registerService($client, 'UserGenClient');
    }

    public function testUpdateMember()
    {
        $c = UserCreatorPageController::create();
        $c->updateMember($this->data);

        $member = $c->getGeneratedMember();

        $this->assertEquals($member->FirstName, $this->data['name']['first']);
        $this->assertEquals($member->Surname, $this->data['name']['last']);
        $this->assertEquals($member->Email, $this->data['email']);
        $this->assertEquals($member->Cell, $this->data['cell']);
        $this->assertEquals($member->ProfilePic, $this->data['picture']['large']);
        $this->assertNotNull($member->ID);

        $member->delete();
    }

    public function testUpdateMemberMissingData()
    {
        $c = UserCreatorPageController::create();

        $this->setExpectedException(HTTPResponse_Exception::class, 'Missing "email" in API data');
        $c->updateMember($this->badData);

        $member = $c->getGeneratedMember();
        $this->assertNull($member);
    }

    public function testGetUserData()
    {
        $this->setMockResponses('200', [], json_encode($this->data));
        $c = UserCreatorPageController::create();
        $result = $c->getUserData();
        $this->assertEquals($result, $this->data);
    }

    public function testGetUserDataEmptyResponse()
    {
        $this->setMockResponses('200', [], false);
        $c = UserCreatorPageController::create();
        $this->setExpectedException(HTTPResponse_Exception::class, 'Unable to parse response as JSON, or empty response body from https://randomuser.me/api/');
        $c->getUserData();
    }

    public function testGetUserDataError()
    {
        $this->setMockResponses('503');
        $c = UserCreatorPageController::create();
        $this->setExpectedException(HTTPResponse_Exception::class, 'Unable to connect to https://randomuser.me/api/, error: Server error: `GET https://randomuser.me/api/` resulted in a `503 Service Unavailable` response');
        $c->getUserData();
    }

    public function testGetUserDataException()
    {
        $fn = function(){
            throw new \RuntimeException('something');
        };

        $this->setMockResponses('503', [], null, $fn);
        $c = UserCreatorPageController::create();
        $this->setExpectedException(HTTPResponse_Exception::class, 'Unable to connect to https://randomuser.me/api/, error: something');
        $c->getUserData();
    }

    public function testGetUserDataNon200()
    {
        $this->setMockResponses('301');
        $c = UserCreatorPageController::create();
        $this->setExpectedException(HTTPResponse_Exception::class, 'Non-200 response code received from https://randomuser.me/api/: 301');
        $c->getUserData();
    }
}
