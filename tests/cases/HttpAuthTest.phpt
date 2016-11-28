<?php

namespace PG\HttpAuth\Tests;

use Tester,
    Tester\Assert,
    Mockery,
    Nette,
    PG\HttpAuth\HttpAuth;

require __DIR__ . '/../bootstrap.php';

final class HttpAuthTest extends Tester\TestCase
{
    /**
     * @var Nette\Security\IAuthenticator
     */
    private $authenticator;

    /**
     * @var Nette\Application\IRouter
     */
    private $router;

    /**
     * @var Nette\Http\IRequest
     */
    private $request;

    /**
     * @var Nette\Http\IResponse
     */
    private $response;

    /**
     * @var Nette\Application\Request
     */
    private $appRequest;



    public function setUp()
    {
        $this->router = Mockery::mock('Nette\Application\IRouter');
        $this->request = Mockery::mock('Nette\Http\IRequest');
    }



    private function setupAuthenticator($badCredentials = false)
    {
        unset($this->authenticator);

        $this->authenticator = Mockery::mock('Nette\Security\IAuthenticator');
        if ($badCredentials) {
            $this->authenticator->shouldReceive('authenticate')->andThrow('Nette\Security\AuthenticationException');
        } else {
            $this->authenticator->shouldReceive('authenticate')->andReturnNull();
        }
    }



    private function setupRequest($return_presenter, $user, $password)
    {
        unset($this->appRequest);

        $this->appRequest = Mockery::mock('Nette\Application\Request');

        $this->appRequest->shouldReceive('getPresenterName')
            ->andReturn($return_presenter);

        if ($user || $password) {
            $this->request->url = (object)[
                'user' => $user,
                'password' => $password
            ];
        }
        else {
            $this->request->url = (object)[
                'user' => null,
                'password' => null
            ];
        }

        $this->router->shouldReceive('match')
            ->withArgs([$this->request])
            ->andReturn($this->appRequest);
    }



    public function setupResponse()
    {
        unset($this->response);

        $this->response = Mockery::mock('Nette\Http\IResponse');

        $this->response->header = null;
        $this->response->code = null;

        $this->response->shouldReceive('setHeader')->set('header', 'www-auth');
        $this->response->shouldReceive('setCode')->set('code', 401);
    }



    public function testSecuredAndCredentials()
    {
        $this->setupResponse();
        $this->setupRequest('Front:Secured', 'admin', '1234567890');

        $this->setupAuthenticator();

        ob_start();

        $auth = new HttpAuth(
            ['Front:Secured', 'Front:AnotherSecured'],
            $this->authenticator,
            $this->router,
            $this->request,
            $this->response,
            false
        );

        $response_content = ob_get_clean();

        Assert::null($this->response->header);
        Assert::null($this->response->code);
        Assert::same('', $response_content);
    }



    public function testSecuredBadCredentials()
    {
        $this->setupResponse();
        $this->setupRequest('Front:AnotherSecured', null, null);

        $this->setupAuthenticator(true);

        ob_start();

        $auth = new HttpAuth(
            ['Front:Secured', 'Front:AnotherSecured'],
            $this->authenticator,
            $this->router,
            $this->request,
            $this->response,
            false
        );

        $response_content = ob_get_clean();

        Assert::same('www-auth', $this->response->header);
        Assert::same(401, $this->response->code);
        Assert::same('<h1>Authentication failed.</h1>', $response_content);
    }



    public function testUnSecured()
    {
        $this->setupResponse();
        $this->setupRequest('Front:Unsecured', null, null);

        $this->setupAuthenticator();

        ob_start();

        $auth = new HttpAuth(
            ['Front:Secured', 'Front:AnotherSecured'],
            $this->authenticator,
            $this->router,
            $this->request,
            $this->response,
            false
        );

        $response_content = ob_get_clean();

        Assert::null($this->response->header);
        Assert::null($this->response->code);
        Assert::same('', $response_content);
    }



    public function testEmptyCredentials()
    {
        $this->setupResponse();
        $this->setupRequest('Front:Homepage', null, null);

        $this->setupAuthenticator(true);

        ob_start();

        $auth = new HttpAuth(
            [],
            $this->authenticator,
            $this->router,
            $this->request,
            $this->response,
            false
        );

        $response_content = ob_get_clean();

        Assert::same('www-auth', $this->response->header);
        Assert::same(401, $this->response->code);
        Assert::same('<h1>Authentication failed.</h1>', $response_content);
    }

}

$test_case = new HttpAuthTest;
$test_case->run();
