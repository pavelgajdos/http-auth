<?php

/**
 * @copyright   Copyright (c) 2016 Pavel Gajdos <info@pavelgajdos.cz>, Copyright (c) 2015 ublaboo <ublaboo@paveljanda.com>
 * @author      Pavel Gajdos <info@pavelgajdos.cz>, Pavel Janda <me@paveljanda.com>
 * @package     PG
 * Forked from ublaboo/simple-http-auth.
 */

namespace PG\HttpAuth;

use Nette;

class HttpAuth extends Nette\DI\CompilerExtension
{

	/**
	 * @var Nette\Http\Request
	 */
	protected $httpRequest;

	/**
	 * @var Nette\Http\Response
	 */
	protected $httpResponse;

	/**
	 * @var bool
	 */
	protected $exitOnBadCredentials;


    /** @var Nette\Security\IAuthenticator */
    private $authenticator;



    /**
     * @param array $presenters If array of presenters is empty, accept all
     * @param Nette\Application\IRouter $router
     * @param Nette\Security\IAuthenticator $authenticator
     * @param Nette\Http\IRequest $httpRequest
     * @param Nette\Http\IResponse $httpResponse
     * @param bool $exit_on_bad_credentials
     */
	public function __construct(
		$presenters,
        Nette\Security\IAuthenticator $authenticator,
		Nette\Application\IRouter $router,
		Nette\Http\IRequest $httpRequest,
		Nette\Http\IResponse $httpResponse,
		$exit_on_bad_credentials = TRUE
	) {
        $this->authenticator = $authenticator;
		$this->httpRequest  = $httpRequest;
		$this->httpResponse = $httpResponse;
		$this->exitOnBadCredentials = $exit_on_bad_credentials;

		try {
			$request = $router->match($httpRequest);

		} catch (\Exception $e) {
			return;
		}

		if (!$request) {
			return;
		}

		/**
		 * Accept either all presenters or just the specified ones
		 */
		if (empty($presenters) || in_array($request->getPresenterName(), $presenters)) {
			$this->authenticate();
		}
    }


	/**
	 * Authenticate user
	 * @return void
	 */
	public function authenticate()
	{
		$url = $this->httpRequest->url;

        $askForCredentials = false;

        if (!$url->user && !$url->password) {
            $askForCredentials = true;
        }
        else {
            try {
                $this->authenticator->authenticate([$url->user, $url->password]);
            } catch (Nette\Security\AuthenticationException $e) {
                $askForCredentials = true;
            }
        }

        if ($askForCredentials) {
            $this->httpResponse->setHeader('WWW-Authenticate', 'Basic realm="HTTP Authentication"');
            $this->httpResponse->setCode(Nette\Http\IResponse::S401_UNAUTHORIZED);

            echo '<h1>Authentication failed.</h1>';

            if ($this->exitOnBadCredentials) {
                die;
            }
        }
	}
}
