<?php
/**
 * @copyright Copyright (c) 2021, PonderSource
 *
 * @author Yvo Brevoort <yvo@pondersource.nl>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\ScienceMesh\ShareProvider;

use OCP\IConfig;
use OCP\IUserManager;
use OCA\ScienceMesh\RevaHttpClient;

/**
 * Class ScienceMeshShareHelper
 *
 * @package OCA\ScienceMesh\ShareProvider\ShareAPIHelper
 */
class ShareAPIHelper {
	/** @var IConfig */
	private $config;

	/** @var IUserManager */
	private $userManager;

	/** @var RevaHttpClient */
	private $revaHttpClient;

	/**
	 * ShareAPIHelper constructor.
	 *
	 * @param IConfig $config
	 * @param IUserManager $userManager
	 */
	public function __construct(
		IConfig $config,
		IUserManager $userManager
	) {
		$this->config = $config;
		$this->userManager = $userManager;
		$this->revaHttpClient = new RevaHttpClient($this->config);
	}

	public function formatShare($share) {
		$result = [];
		$result['share_with'] = $share->getSharedWith();
		$result['share_with_displayname'] = $result['share_with'];
		$result['token'] = $share->getToken();
		return $result;
	}
	
	public function createShare($share, $shareWith, $permissions, $expireDate) {
		$node = $share->getNode();
		$share->setSharedWith($shareWith);
		$share->setPermissions($permissions);
		$pathParts = explode("/", $node->getPath());
		$sender = $pathParts[1];
		$sourceOffset = 3;
		$targetOffset = 3;
		$prefix = "/";
		$suffix = ($node->getType() == "dir" ? "/" : "");

		// "home" is reva's default work space name, prepending that in the source path:
		$sourcePath = $prefix . "home/" . implode("/", array_slice($pathParts, $sourceOffset)) . $suffix;
		$targetPath = $prefix . implode("/", array_slice($pathParts, $targetOffset)) . $suffix;
		$shareWithParts = explode("@", $shareWith);
		$this->revaHttpClient->createShare($sender, [
			'sourcePath' => $sourcePath,
			'targetPath' => $targetPath,
			'type' => $node->getType(),
			'recipientUsername' => $shareWithParts[0],
			'recipientHost' => $shareWithParts[1]
		]);
	}
}