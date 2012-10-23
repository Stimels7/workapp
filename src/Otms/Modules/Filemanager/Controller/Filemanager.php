<?php

/**
 * This file is part of the Workapp project.
 *
 * Filemanager Module
 *
 * (c) Dmitry Samotoy <dmitry.samotoy@gmail.com>
 *
 */

namespace Otms\Modules\Filemanager\Controller;

use Engine\Modules\Controller;
use Otms\Modules\Filemanager\Model;

/**
 * Controller\Filemanager class
 *
 * @author Dmitry Samotoy <dmitry.samotoy@gmail.com>
 */
class Filemanager extends Controller {

	public function index() {
		$this->view->setTitle("Файловый менеджер");
		
		$file = new Model\File($this->config);
		$file->showTree(0);
		
		if (isset($_GET["id"])) {
			$fm = & $_SESSION["fm"];

			if ($_GET["id"] == "0") {
				$fm["dir"] = 0;
				$fm["dirname"] = "/";
			} else if (is_numeric($_GET["id"])) {
				$fm["dir"] = $_GET["id"];
				$dirname = $file->getNameFromDir($_GET["id"]);
				$fm["dirname"] = $dirname[0]["name"];
			} else {
				$fm["dir"] = 0;
				$fm["dirname"] = "/";
			}
		}

		$this->view->setLeftContent($this->view->render("block_left", array("tree" => $file->getTree())));
		
		$this->view->setAdvBottomPanel($this->view->render("block_bottom", array()));

		$this->view->index(array("session_name" => session_name(), "session_id" => session_id(), "maxUploadSize" => $this->config["fm"]["maxUploadSize"]));
		
		$this->view->showPage();
	}
}