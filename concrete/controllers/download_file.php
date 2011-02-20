<?php 

defined('C5_EXECUTE') or die(_("Access Denied."));
class DownloadFileController extends Controller {

	/** 
	 * Automatically loads the file block class
	 */
	public function on_start() {
		Loader::block('file');
	}

	public function view($bID = 0) {
		// get the block
		$block = $this->getBlock($bID);
		$file = $block->getFileObject();
		
		// if block password is blank download
		if (!$block->getPassword())
			return $this->download($file);
		
		// otherwise show the form
		$this->set('bID', $bID);
		$this->set('filename', $file->getFilename());
		$this->set('filesize', filesize(DIR_FILES_UPLOADED."/".$file->getFilename()));
	}
	
	public function submit_password($bID = 0) {
		$block = $this->getBlock($bID);
		$file = $block->getFileObject();
	
		if ($block->getPassword() == $_POST['password'])
			return $this->download($file);
		
		$this->set('error', "The entered password was incorrect. Please try again.");
		$this->view($bID);
	}
	
	private function download($file) {
		//$mime_type = finfo_file(DIR_FILES_UPLOADED."/".$filename);
		//header('Content-type: $mime_type');
		// everything else lets just download
		$filename = $file->getFilename();
	
		header('Content-type: application/octet-stream');
		header("Content-Disposition: attachment; filename=\"$filename\"");
		header('Content-Length: ' . filesize(DIR_FILES_UPLOADED."/".$filename));
		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Cache-Control: private",false);
		header("Content-Transfer-Encoding: binary");
	
		// this should be from FILES -- but lets not break it just yet
		$handle = fopen(DIR_FILES_UPLOADED."/".$filename, "r");
		echo fread($handle, filesize(DIR_FILES_UPLOADED."/".$filename));
		fclose($handle);
		exit;
	}
	
	private function getBlock($bID) {
		$b = Block::getByID($bID);
		return $b->getInstance();
	}
}

?>
