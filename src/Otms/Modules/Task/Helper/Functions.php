<?php

/**
 * This file is part of the Workapp project.
 *
 * Task Module
 *
 * (c) Dmitry Samotoy <dmitry.samotoy@gmail.com>
 *
 */

namespace Otms\Modules\Task\Helper;

use Engine\Modules;

/**
 * Task Helper class
 *
 * @author Dmitry Samotoy <dmitry.samotoy@gmail.com>
 */
class Functions extends Modules\Functions {
	/**
	 * Получить количество задач
	 * 
	 * @return int
	 */
	function getNumTasks() {
		return $this->registry["task"]->getNumTasks();
	}

	/**
	 * Задача
	 * 
	 * @param array:
	 *    array $param[0] - данные
	 *    array $param[1] - автор
	 *    $param[2] - NULL
	 *    $param[3] - NULL
	 *    $param[4] - NULL
	 *    array $param[5] - объект
	 *    int $param[6] - количество комментариев
	 *    int $param[7] - количество новых комментариев
	 * @return string (twig render) - .../tt/task.tpl
	 */
	function renderTask($param) {
		return $this->view->render("tt_task", array("data" => $param[0], "author" => $param[1], "ruser" => $param[2], "cuser" => $param[3], "notObj" => $param[4], "obj" => $param[5], "numComments" => $param[6], "newComments" =>$param[7]));
	}

	/**
	 * Короткое отображение задачи
	 * 
	 * @param array
	 *    $param[0] - ID задачи
	 * @return string (twig render) - .../tt/taskshort.tpl
	 */
	function taskshort($param) {
		$taskshort = null;
		
		$tid = $param[0];

		if ($data = $this->registry["task"]->getTask($tid)) {
			$numComments = $this->registry["task"]->getNumComments($tid);

			$author = $this->registry["user"]->getUserInfo($data[0]["who"]);

			$ruser = array();
			foreach($data as $val) {
				if (isset($val["uid"])) {
					if ($val["uid"] != 0) {
						$uname = $this->registry["user"]->getUserInfo($val["uid"]);
						$ruser[] = "<a style='cursor: pointer' onclick='getUserInfo(" . $val["uid"] . ")'>" . $uname["name"] . " " . $uname["soname"] . "</a>";
					}
				}

				if (isset($val["rgid"])) {
					if ($val["rgid"] != 0) {
						$ruser[] = "<span style='color: #5D7FA6'><b>" . $this->registry["user"]->getGroupName($val["rgid"]) . "</b></span>";
					}
				}

				if ($val["all"] == 1) {
					$ruser[] = "<span style='color: #D9A444'><b>Все</b></span>";
				}
			}

			$object = $this->registry["object"];

			$notObj = true;
			if (!$obj = $object->getShortObject($data[0]["oid"])) {
				$notObj = false;
			}

			$taskshort = $this->view->render("tt_taskshort", (array("ui" => $this->registry["ui"], "data" => $data, "author" => $author, "ruser" => $ruser, "notObj" => $notObj, "obj" => $obj, "numComments" => $numComments, "uid" => $this->registry["ui"]["id"])));
		}

		return $taskshort;
	}

	/**
	 * Форма создания задачи
	 * 
	 * @param array
	 *    array $param[0] - параметры новой задачи
	 * @return string (twig render) - .../tt/tabs.tpl
	 */
	function formtask($param) {
		if (isset($param[0])) {
			return $this->view->render("tt_tabs", array("data" => $param[0]));
		} else {
			return $this->view->render("tt_tabs", array());
		}
	}
	
	/**
	 * Форма создания задачи (используется в модуле Route(БП))
	 * 
	 * @param array
	 *    array $param[0] - параметры новой задачи
	 * @return string (twig render) - .../tt/form/fulltask.tpl
	 */
	function formfulltask($param) {
		
		if (isset($param[0])) {
			$data = $param[0];
			$issRusers = array(); $k = 0;
			foreach($data as $part) {
				
				if (isset($part["ruser"])) {
					foreach($part["ruser"] as $uid) {
						if (($uid) != "0") {
							$row = $this->registry["user"]->getUserInfo($uid);
				
							$k++;
				
							$issRusers[$k]["desc"] = '<p><span style="font-size: 11px; margin-right: 10px;" id="udesc[' . $uid . ']">' . $row["name"] . ' ' . $row["soname"] . '</span>';
							$issRusers[$k]["desc"] .= '<input id="uhid[' . $uid . ']" type="hidden" name="ruser[]" value="' . $uid . '" /></p>';
						}
					}
				}
			
				if (isset($part["gruser"])) {
					foreach($part["gruser"] as $gid) {
						if (($gid) != "0") {
							$gname = $this->registry["user"]->getSubgroupName($gid);
				
							$k++;
				
							$issRusers[$k]["desc"] = '<p style="font-size: 11px; margin-right: 10px">' . $gname . '<input type="hidden" name="gruser[]" value="' . $gid . '" /></p>';
						}
					}
				}
			
				if (isset($part["rall"])) {
					if (($part["rall"]) == "1") {
						$k++;
				
						$issRusers[$k]["desc"] = '<p style="font-size: 11px; margin-right: 10px">Все<input type="hidden" name="rall" value="1" /></p>';
					}
				}
			}
			
			$obj = array();
			if ($data[0]["selObjHid"] != "") {
				$object = new Model_Object();
				$obj = $object->getShortObject($data[0]["selObjHid"]);
			}
			
			return $this->view->render("tt_form_fulltask", array("data" => $data, "issRusers" => $issRusers, "obj" => $obj));
		}
	}

	/**
	 * Закрыть задачу
	 * 
	 * @param array
	 *    int $param[0] - ID задачи
	 */
	function closeTask($param) {
		$this->registry["task"]->closeTask($param[0]);
	}

	/**
	 * Возвращает задачу
	 * 
	 * @param unknown_type $param
	 *    int $param[0] - ID задачи
	 */
	function getTask($param) {
		return $this->registry["task"]->getTask($param[0]);
	}
}
?>