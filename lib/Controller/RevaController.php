<?php
namespace OCA\ScienceMesh\Controller;

use OCA\ScienceMesh\ServerConfig;
use OCA\ScienceMesh\PlainResponse;
use OCA\ScienceMesh\ResourceServer;
use OCA\ScienceMesh\NextcloudAdapter;

use OCA\Files_Trashbin\Trash\ITrashManager;

use OCP\IRequest;
use OCP\IUserManager;
use OCP\IURLGenerator;
use OCP\ISession;
use OCP\IConfig;

use OCP\Files\IRootFolder;
use OCP\Files\IHomeStorage;
use OCP\Files\SimpleFS\ISimpleRoot;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Controller;

class RevaController extends Controller {
	/* @var IURLGenerator */
	private $urlGenerator;

	/* @var ISession */
	private $session;

	public function __construct($AppName, IRootFolder $rootFolder, IRequest $request, ISession $session, IUserManager $userManager, IURLGenerator $urlGenerator, $userId, IConfig $config, \OCA\ScienceMesh\Service\UserService $UserService, ITrashManager $trashManager)
	{
		parent::__construct($AppName, $request);
		require_once(__DIR__.'/../../vendor/autoload.php');
		$this->config = new \OCA\ScienceMesh\ServerConfig($config, $urlGenerator, $userManager);
		$this->rootFolder = $rootFolder;
		$this->request     = $request;
		$this->urlGenerator = $urlGenerator;
		$this->session = $session;
		$this->userManager = $userManager;
		$this->trashManager = $trashManager;
	}

	/**
	 * @param array $nodeInfo
	 *
	 * Returns the data of a CS3 provider.ResourceInfo object https://github.com/cs3org/cs3apis/blob/a86e5cb/cs3/storage/provider/v1beta1/resources.proto#L35-L93
	 * @return array
	 *
	 * @throws \OCP\Files\InvalidPathException
	 * @throws \OCP\Files\NotFoundException
	 */

	private function nodeInfoToCS3ResourceInfo(array $nodeInfo) : array
	{
			return [
					"opaque" => [
							"map" => NULL,
					],
					"type" => 1,
					"id" => [
							"opaque_id" => "fileid-/" . $nodeInfo["path"],
					],
					"checksum" => [
							"type" => 0,
							"sum" => "",
					],
					"etag" => "deadbeef",
					"mime_type" => "text/plain",
					"mtime" => [
							"seconds" => 1234567890
					],
					"path" => "/" . $nodeInfo["path"],
					"permission_set" => [
							"add_grant" => false,
							"create_container" => false,
							"delete" => false,
							"get_path" => false,
							"get_quota" => false,
							"initiate_file_download" => false,
							"initiate_file_upload" => false,
							// "listGrants => false,
							// "listContainer => false,
							// "listFileVersions => false,
							// "listRecycle => false,
							// "move => false,
							// "removeGrant => false,
							// "purgeRecycle => false,
							// "restoreFileVersion => false,
							// "restoreRecycleItem => false,
							// "stat => false,
							// "updateGrant => false,
							// "denyGrant => false,
					],
					"size" => 12345,
					"canonical_metadata" => [
							"target" => NULL,
					],
					"arbitrary_metadata" => [
							"metadata" => [
									"some" => "arbi",
									"trary" => "meta",
									"da" => "ta",
							],
					],
			];
	}

	private function getFileSystem() {
		// Create the Nextcloud Adapter
		$adapter = new NextcloudAdapter($this->sciencemeshFolder);
		$filesystem = new \League\Flysystem\Filesystem($adapter);
		return $filesystem;
	}

	private function getStorageUrl($userId) {
		$storageUrl = $this->urlGenerator->getAbsoluteURL($this->urlGenerator->linkToRoute("sciencemesh.storage.handleHead", array("userId" => $userId, "path" => "foo")));
		$storageUrl = preg_replace('/foo$/', '', $storageUrl);
		return $storageUrl;
	}

	private function initializeStorage($userId, $createHome = false) {
		$this->userFolder = $this->rootFolder->getUserFolder($userId);
		$homeExists = $this->userFolder->nodeExists("sciencemesh");
		if ($createHome && !$homeExists) {
			$this->userFolder->newFolder("sciencemesh"); // Create the Sciencemesh directory for storage if it doesn't exist.
		  $homeExists = true;
		}
		if ($homeExists) {
			$this->sciencemeshFolder = $this->userFolder->get("sciencemesh"); // used by getFileSystem
			$this->filesystem = $this->getFileSystem();
		}
		$this->baseUrl = $this->getStorageUrl($userId); // Where is that used?
	}

	private function respond($responseBody, $statusCode, $headers=array()) {
		$result = new PlainResponse($body);
		foreach ($headers as $header => $values) {
			foreach ($values as $value) {
				$result->addHeader($header, $value);
			}
		}
		$result->setStatus($statusCode);
		return $result;
	}

	/* Reva handlers */

	/**
	 * @PublicPage
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function AddGrant($userId) {
		$this->initializeStorage($userId);
		$path = $this->request->getParam("path") ?: "/";
		// FIXME: Expected a param with a grant to add here;
		return new JSONResponse("Not implemented", 200);
	}

	/**
	 * @PublicPage
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function Authenticate($userId) {
		$password = $this->request->getParam("password");
		// Try e.g.:
		// curl -v -H 'Content-Type:application/json' -d'{"password":"relativity"}' http://localhost/apps/sciencemesh/~einstein/api/Authenticate
		// FIXME: https://github.com/pondersource/nc-sciencemesh/issues/3
		$auth = $this->userManager->checkPassword($userId,$password);
		if ($auth) {
			return new JSONResponse("Logged in", 200);
		} else {
			return new JSONResponse("Username / password not recognized", 401);
		}
	}

	/**
	 * @PublicPage
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function CreateDir($userId) {
		$this->initializeStorage($userId);
		$path = $this->request->getParam("path") ?: "/";
		$success = $this->filesystem->createDir($path);
		if ($success) {
			return new JSONResponse("OK", 200);
		} else {
			return new JSONResponse(["error" => "Could not create directory."], 500);
		}
	}

	/**
	 * @PublicPage
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function CreateHome($userId) {
		$this->initializeStorage($userId, true);
		return new JSONResponse("OK", 200);
	}

	/**
	 * @PublicPage
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function CreateReference($userId) {
		$this->initializeStorage($userId);
		$path = $this->request->getParam("path") ?: "/";
		return new JSONResponse("Not implemented", 200);
	}

	/**
	 * @PublicPage
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function Delete($userId) {
		$this->initializeStorage($userId);
		$path = $this->request->getParam("path") ?: "/";
		$success = $this->filesystem->delete($path);
		if ($success) {
			return new JSONResponse("OK", 200);
		} else {
			return new JSONResponse(["error" => "Failed to delete."], 500);
		}
	}

	/**
	 * @PublicPage
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function EmptyRecycle($userId) {
		$this->initializeStorage($userId);
		$user = $this->userManager->get($userId);
		$trashItems = $this->trashManager->listTrashRoot($user);

		$result = []; // Where is this used?
		foreach ($trashItems as $node) {
			#getOriginalLocation : returns string
			if (preg_match("/^sciencemesh/", $node->getOriginalLocation())) {
				$this->trashManager->removeItem($node);
			}
		}
		return new JSONResponse("OK", 200);
	}

	/**
	 * @PublicPage
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function GetMD($userId) {
		$ref = $this->request->getParam("ref") ?: "/";
		$path = $ref["path"];
		if ($path == "/") {
			$this->userFolder = $this->rootFolder->getUserFolder($userId);
			$success = $this->userFolder->nodeExists("sciencemesh");
			if ($success) {
				$this->initializeStorage($userId);
				$path = ".";
			}
		} else {
			$this->initializeStorage($userId);
			$success = $this->filesystem->has($path);
		}

		if ($success) {
  		$nodeInfo = $this->filesystem->getMetaData($ref["path"]);
			$resourceInfo = $this->nodeInfoToCS3ResourceInfo($nodeInfo);
				return new JSONResponse($resourceInfo, 200);
		} else {
			return new JSONResponse(["error" => "File not found"], 404);
		}
	}

	/**
	 * @PublicPage
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function GetPathByID($userId) {
		$this->initializeStorage($userId);
		$storageId = $this->request->getParam("storage_id");
		$opaqueId = $this->request->getParam("opaque_id");
		return new JSONResponse("/foo", 200);
	}

	/**
	 * @PublicPage
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function InitiateUpload($userId) {
		$response = [
			"simple" => "yes",
			"tus" => "yes" // FIXME: Not really supporting this;
		];
		return new JSONResponse($response, 200);
	}

	/**
	 * @PublicPage
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */

	 // `POST /apps/sciencemesh/~tester/api/storage/ListFolder
	 // {"ref":{
	 //	"resource_id":{
	 			//"storage_id":"storage-id","opaque_id":"opaque-id"
	//		},
	// 	"path":"/some/path"
//		},
//		"mdKeys":["val1","val2","val3"]}`: {
//			200,
//		`[
//				{"opaque":{},"type":1,"id":{
//					"opaque_id":"fileid-/some/path"
//					}
//					,"checksum":{},"etag":"deadbeef","mime_type":"text/plain","mtime":{
//						"seconds":1234567890
//						},
//						"path":"/some/path","permission_set":{},"size":12345,"canonical_metadata":{},"arbitrary_metadata":{
//							"metadata":
//								{"da":"ta","some":"arbi","trary":"meta"
//							}
//						}
//				}
//		]`,
//	 serverStateEmpty},

	public function ListFolder($userId) {
		$this->initializeStorage($userId);
		$ref = $this->request->getParam("ref") ?: "/";
		$path = $ref["path"];
		if ($path == "/") {
			$nodeInfos = $this->filesystem->listContents(".");
		} else {
			$nodeInfos = $this->filesystem->listContents($path);
		}
		// FIXME: https://github.com/pondersource/nc-sciencemesh/issues/26
		// It seems that if the folder is not found, then NextcloudAdapter
		// returns [] and not false?
		if ($nodeInfos !== false) {
			$resourceInfos = array_map(function($nodeInfo) {
				return $this->nodeInfoToCS3ResourceInfo($nodeInfo);
			}, $nodeInfos);
			return new JSONResponse($resourceInfos, 200);
		} else {
			return new JSONResponse(["error" => "Folder not found"], 400);
		}
	}

	/**
	 * @PublicPage
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function ListGrants($userId) {
		$this->initializeStorage($userId);
		$path = $this->request->getParam("path") ?: "/";
		return new JSONResponse("Not implemented", 200);
	}

	/**
	 * @PublicPage
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function ListRecycle($userId) {
		$this->initializeStorage($userId);
		$user = $this->userManager->get($userId);
		$trashItems = $this->trashManager->listTrashRoot($user);

		$result = [];
		foreach ($trashItems as $node) {
			if (preg_match("/^sciencemesh/", $node->getOriginalLocation())) {
				$result[] = [
				    'mimetype' => $node->getMimetype(),
				    'path' => preg_replace("/^sciencemesh/", "", $node->getOriginalLocation()),
				    'size' => $node->getSize(),
				    'basename' => basename($node->getPath()),
				    'timestamp' => $node->getMTime(),
				    'deleted' => $node->getDeletedTime(),
				    'type' => $node->getType(),
				    // @FIXME: Use $node->getPermissions() to set private or public
				    //         as soon as we figure out what Nextcloud permissions mean in this context
				    'visibility' => 'public',
				    /*/
				    'CreationTime' => $node->getCreationTime(),
				    'Etag' => $node->getEtag(),
				    'Owner' => $node->getOwner(),
				    /*/
				];
			}
		}
		return new JSONResponse($result, 200);
	}

	/**
	 * @PublicPage
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function ListRevisions($userId) {
		$this->initializeStorage($userId);
		$path = $this->request->getParam("path") ?: "/";
		return new JSONResponse("Not implemented", 200);
	}

	/**
	 * @PublicPage
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function Move($userId) {
		$this->initializeStorage($userId);
		$from = $this->request->getParam("from");
		$to = $this->request->getParam("to");
		$success = $this->filesystem->move($from, $to);
		if ($success) {
			return new JSONResponse("OK", 200);
		} else {
			return new JSONResponse(["error" => "Failed to move."], 500);
		}
	}

	/**
	 * @PublicPage
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function RemoveGrant($userId) {
		$this->initializeStorage($userId);
		$path = $this->request->getParam("path") ?: "/";
		// FIXME: Expected a grant to remove here;
		return new JSONResponse("Not implemented", 200);
	}

	/**
	 * @PublicPage
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function RestoreRecycleItem($userId) {
		$this->initializeStorage($userId);
		$path = $this->request->getParam("path") ?: "/";
		$user = $this->userManager->get($userId);
		$trashItems = $this->trashManager->listTrashRoot($user);

		foreach ($trashItems as $node) {
			if (preg_match("/^sciencemesh/", $node->getOriginalLocation())) {
				$nodePath = preg_replace("/^sciencemesh/", "", $node->getOriginalLocation());
				if ($path == $nodePath) {
					$this->trashManager->restoreItem($node);
					return new JSONResponse("OK", 200);
				}
			}
		}
		return new JSONResponse('["error" => "Not found."]', 404);
	}

	/**
	 * @PublicPage
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function RestoreRevision($userId) {
		$this->initializeStorage($userId);
		$path = $this->request->getParam("path") ?: "/";
		// FIXME: Expected a revision param here;
		return new JSONResponse("Not implemented", 200);
	}

	/**
	 * @PublicPage
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function SetArbitraryMetadata($userId) {
		$this->initializeStorage($userId);
		$path = $this->request->getParam("path") ?: "/";
		$metadata = $this->request->getParam("metadata");
		// FIXME: What do we do with the existing metadata? Just toss it and overwrite with the new value? Or do we merge?
		return new JSONResponse("Not implemented", 200);
	}

	/**
	 * @PublicPage
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function UnsetArbitraryMetadata($userId) {
		$this->initializeStorage($userId);
		$path = $this->request->getParam("path") ?: "/";
		return new JSONResponse("Not implemented", 200);
	}

	/**
	 * @PublicPage
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function UpdateGrant($userId) {
		$this->initializeStorage($userId);
		$path = $this->request->getParam("path") ?: "/";
		// FIXME: Expected a paramater with the grant(s)
		return new JSONResponse("Not implemented", 200);
	}

	/**
	 * @PublicPage
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function Upload($userId, $path) {
		$this->initializeStorage($userId);
		$contents = $this->request->put;
		if ($this->filesystem->has($path)) {
			$success = $this->filesystem->update($path, $contents);
			if ($success) {
				return new JSONResponse("OK", 200);
			} else {
				return new JSONResponse(["error" => "Update failed"], 500);
			}
		} else {
			$success = $this->filesystem->write($path, $contents);
			if ($success) {
				return new JSONResponse("OK", 201);
			} else {
				return new JSONResponse(["error" => "Create failed"], 500);
			}
		}
	}
}
