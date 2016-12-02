<?php

/**
 * @copyright   Copyright (c) 2016 Pavel Gajdos <info@pavelgajdos.cz>, Copyright (c) 2015 ublaboo <ublaboo@paveljanda.com>
 * @author      Pavel Gajdos <info@pavelgajdos.cz>, Pavel Janda <me@paveljanda.com>
 * @package     PG
 * Forked from ublaboo/simple-http-auth.
 */

namespace PG\HttpAuth\DI;

use Nette;
use PG\HttpAuth\HttpAuth;

class HttpAuthExtension extends Nette\DI\CompilerExtension
{

	private $defaults = [
		'presenters' => [],
        'setUserAuthenticator' => true
	];


	public function loadConfiguration()
	{
		$config = $this->_getConfig();

		$builder = $this->getContainerBuilder();

		$builder->addDefinition($this->prefix('httpAuth'))
			->setClass(HttpAuth::class)
			->addTag('run')
			->setArguments([
				$config['presenters'],
                $config['setUserAuthenticator']
			]);
	}


	private function _getConfig()
	{
		return $this->validateConfig($this->defaults, $this->config);
	}

}
