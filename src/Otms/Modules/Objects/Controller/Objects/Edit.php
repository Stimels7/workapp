<?php

/**
 * This file is part of the Workapp project.
 *
 * Object Module
 *
 * (c) Dmitry Samotoy <dmitry.samotoy@gmail.com>
 *
 */

namespace Otms\Modules\Objects\Controller\Objects;

use Otms\Modules\Objects\Controller\Objects;
use Otms\Modules\Objects\Model;

/**
 * Controller\Objects\Edit class
 *
 * @author Dmitry Samotoy <dmitry.samotoy@gmail.com>
 */
class Edit extends Objects {

	public function index() {
		$this->view->setTitle("Edit object");

		$this->view->setLeftContent($this->view->render("left_objects", array()));
		
		$object = new Model\Object();

		if (isset($_POST["submit"])) {
			$object->editObject($_POST);

			$this->view->refresh(array("timer" => "1", "url" => "objects/sub/" . $this->args[1] . "/"));

		} else {

			if (isset($this->args[1])) {
				$data = $object->getObject($this->args[1]);
				
				$contact = & $_SESSION["contact"];
				if ((isset($contact["email"])) and ($contact["email"] != null)) {
					$email = $contact["email"];
				} else {
					$email = null;
				}
				
				$first[] = '<ul id="firstSort" class="tviewshow">';
				$second[] = '<ul id="secondSort" class="tviewshow">';
				$third[] = '<ul id="thirdSort" class="tviewshow">';
				foreach($data as $part) {
					if ($part["view"]["x"] == 1) {
						$first[] = $this->view->render("objects_objectfieldedit", array("part" => $part));
					} elseif ($part["view"]["x"] == 2) {
						$second[] = $this->view->render("objects_objectfieldedit", array("part" => $part));
					} elseif ($part["view"]["x"] == 3) {
						$third[] = $this->view->render("objects_objectfieldedit", array("part" => $part));
					} else {
						$first[] = $this->view->render("objects_objectfieldedit", array("part" => $part));
					}
				}
				$first[] = "</ul>"; $second[] = "</ul>"; $third[] = "</ul>";
				
				$template = array_merge($first, $second, $third);
				$template = '<div style="overflow: hidden" id="tview">' . implode(" ", $template) . '</div>';
				
				$this->view->objects_edit(array("vals" => $data, "template" => $template, "email" => $email));
			}

		}

		$this->view->showPage();
	}
}
?>