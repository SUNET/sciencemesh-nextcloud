<?php

namespace OCA\ScienceMesh\Tests\Unit\Controller;

use PHPUnit_Framework_TestCase;

use OCP\AppFramework\Http\TemplateResponse;
use OCP\Files\IRootFolder;
use OCA\ScienceMesh\Controller\RevaController;
use OCA\ScienceMesh\Service\UserService;


class RevaControllerTest extends PHPUnit_Framework_TestCase {

	private $controller;
	private $appName = 'sciencemesh';
	private $rootFolder;
	private $request;
	private $session;
	private $userManager;
	private $urlGenerator;
	private $userId = 'einstein';
	private $config;
	private $userService;
	private $trashManager;

	public function setUp() {

		// arg names: $AppName, IRootFolder $rootFolder, IRequest $request, ISession $session,
		// IUserManager $userManager, IURLGenerator $urlGenerator, $userId, IConfig $config,
		// \OCA\ScienceMesh\Service\UserService $UserService, ITrashManager $trashManager)

		$this->rootFolder = $this->getMockBuilder('OCP\Files\IRootFolder')->getMock();
		$this->request = $this->getMockBuilder('OCP\IRequest')->getMock();
		$this->session =  $this->getMockBuilder('OCP\ISession')->getMock();
		$this->userManager =  $this->getMockBuilder('OCP\IUserManager')->getMock();
		$this->urlGenerator =  $this->getMockBuilder('OCP\IURLGenerator')->getMock();
		$this->config =  $this->getMockBuilder('OCP\IConfig')->getMock();
		$this->userService  =  new UserService($this->session);

		$this->trashManager =  $this->getMockBuilder('OCA\Files_Trashbin\Trash\ITrashManager')->getMock();

	}

	public function testAuthenticateOK() {
		$this->request->method('getParam')
						 ->willReturn('relativity');
		$this->controller = new RevaController(
			$this->appName, $this->rootFolder, $this->request, $this->session,
			$this->userManager, $this->urlGenerator, $this->userId, $this->config,
			$this->userService, $this->trashManager
		);
		$result = $this->controller->Authenticate($this->userId);
		$this->assertEquals($result->getData(), "Logged in");
	}
	public function testAuthenticateWrong() {
		$this->request->method('getParam')
						 ->willReturn('wrong');
		$this->controller = new RevaController(
			$this->appName, $this->rootFolder, $this->request, $this->session,
			$this->userManager, $this->urlGenerator, $this->userId, $this->config,
			$this->userService, $this->trashManager
		);
		$result = $this->controller->Authenticate($this->userId);
		$this->assertEquals($result->getData(), "Username / password not recognized");
	}

}
