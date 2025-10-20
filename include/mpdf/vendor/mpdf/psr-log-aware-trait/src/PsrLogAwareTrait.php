<?php

namespace Mpdf\PsrLogAwareTrait;

use Psr\Log\LoggerInterface;

trait PsrLogAwareTrait 
{

	/**
	 * @var \Psr\Log\LoggerInterface
	 */
	protected $logger;

	public function setLogger(LoggerInterface $logger): void
	{
		$this->logger = $logger;
	}
	
}
