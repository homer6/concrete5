<?php 
/**
 * @access private
 * @package Helpers
 * @category Concrete
 * @author Andrew Embler <andrew@concrete5.org>
 * @copyright  Copyright (c) 2003-2008 Concrete5. (http://www.concrete5.org)
 * @license    http://www.concrete5.org/license/     MIT License
 */

/**
 * @access private
 * @package Helpers
 * @category Concrete
 * @author Andrew Embler <andrew@concrete5.org>
 * @copyright  Copyright (c) 2003-2008 Concrete5. (http://www.concrete5.org)
 * @license    http://www.concrete5.org/license/     MIT License
 */

defined('C5_EXECUTE') or die(_("Access Denied."));
class ConcreteAvatarHelper {

	function getStockAvatars() {
		$f = Loader::helper('file');
		$aDir = $f->getDirectoryContents(DIR_FILES_AVATARS_STOCK);
		return $aDir;			
	}

	function processUploadedAvatar($pointer, $uID) {
		$uHasAvatar = 0;
		$imageSize = getimagesize($pointer);
		$oWidth = $imageSize[0];
		$oHeight = $imageSize[1];
		
		
		$finalWidth = 0;
		$finalHeight = 0;

		// first, if what we're uploading is actually smaller than width and height, we do nothing
		if ($oWidth < AVATAR_WIDTH && $oHeight < AVATAR_HEIGHT) {
			$finalWidth = $oWidth;
			$finalHeight = $oHeight;
		} else {
			// otherwise, we do some complicated stuff
			// first, we subtract width and height from original width and height, and find which difference is g$
			$wDiff = $oWidth - AVATAR_WIDTH;
			$hDiff = $oHeight - AVATAR_HEIGHT;
			if ($wDiff > $hDiff) {
				// there's more of a difference between width than height, so if we constrain to width, we sh$
				$finalWidth = AVATAR_WIDTH;
				$finalHeight = $oHeight / ($oWidth / AVATAR_WIDTH);
			} else {
				// more of a difference in height, so we do the opposite
				$finalWidth = $oWidth / ($oHeight / AVATAR_HEIGHT);
				$finalHeight = AVATAR_HEIGHT;
			}
		}
		
		$image = imageCreateTrueColor($finalWidth, $finalHeight);
		$white = imagecolorallocate($image, 255, 255, 255);
		imagefill($image, 0, 0, $white);

		switch($imageSize[2]) {
			case IMAGETYPE_GIF:
				$im = imageCreateFromGIF($pointer);
				break;
			case IMAGETYPE_JPEG:
				$im = imageCreateFromJPEG($pointer);
				break;
			case IMAGETYPE_PNG:
				$im = imageCreateFromPNG($pointer);
				break;
		}
		
		
		$newPath = DIR_FILES_AVATARS . '/' . $uID . '.gif';
		
		if ($im) {
			$res = imageCopyResampled($image, $im, 0, 0, 0, 0, $finalWidth, $finalHeight, $oWidth, $oHeight);
			if ($res) {
				$res2 = imageGIF($image, $newPath);
				if ($res2) {
					$uHasAvatar = 1;
				}
			}
		}

		return $uHasAvatar;
	}
	
	function removeAvatar($uID) { 
		$db = Loader::db();
		$db->query("update Users set uHasAvatar = 0 where uID = ?", array($uID));
	}

	function updateUserAvatar($pointer, $uID) {
		$uHasAvatar = $this->processUploadedAvatar($pointer, $uID);
		$db = Loader::db();
		$db->query("update Users set uHasAvatar = {$uHasAvatar} where uID = ?", array($uID));
		return $uHasAvatar;
	}
	
	function updateUserAvatarWithStock($pointer, $uID) {
		if ($pointer != "") {
			if (file_exists(DIR_FILES_AVATARS_STOCK . '/' . $pointer)) {
				$uHasAvatar = $this->processUploadedAvatar(DIR_FILES_AVATARS_STOCK . '/' . $pointer, $uID);
				$db = Loader::db();
				$db->query("update Users set uHasAvatar = {$uHasAvatar} where uID = ?", $uID);
			}
		}
	}

}

?>