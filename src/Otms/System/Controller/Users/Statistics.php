<?php

/**
 * This file is part of the Workapp project.
 *
 * (c) Dmitry Samotoy <dmitry.samotoy@gmail.com>
 *
 */

namespace Otms\System\Controller\Users;

use Otms\System\Controller\Users;
use Otms\System\Model;

class Statistics extends Users {

	public function index() {			
		if ($this->registry["ui"]["admin"]) {

			$this->view->setTitle("Statistic");
			
			$this->view->setLeftContent($this->view->render("left_users", array()));

			$users = new Model\User();
				
			$data = $users->getTotal();
				
			if (($data["all"] / 1024 / 1024) > 1) {
				$data["all_val"] = round($data["all"] / 1024 / 1024, 2);
				$data["all_unit"] = "mb";
			};
			if (($data["all"] / 1024 / 1024 / 1024) > 1) {
				$data["all_val"] = round($data["all"] / 1024 / 1024 / 1024, 2);
				$data["all_unit"] = "gb";
			};
				
			foreach($data["users"] as $part) {
				if ($part["quota"] == 0) {
					$user[$part["login"]]["quota_val"] = "<span style='font-size: 18px; position: relative; top: 3px'>&infin;</span> (quota isn't set)";
				}
				
				if (($part["quota"] / 1024 / 1024) > 1) {
					$user[$part["login"]]["quota_val"] = round($part["quota"] / 1024 / 1024, 2);
					$user[$part["login"]]["quota_unit"] = "mb";
				};
				if (($part["quota"] / 1024 / 1024 / 1024) > 1) {
					$user[$part["login"]]["quota_val"] = round($part["quota"] / 1024 / 1024 / 1024, 2);
					$user[$part["login"]]["quota_unit"] = "gb";
				};
					
				if (($part["sum"] / 1024 / 1024) > 1) {
					$user[$part["login"]]["val"] = round($part["sum"] / 1024 / 1024, 2);
					$user[$part["login"]]["unit"] = "mb";
				};
				if (($part["sum"] / 1024 / 1024 / 1024) > 1) {
					$user[$part["login"]]["val"] = round($part["sum"] / 1024 / 1024 / 1024, 2);
					$user[$part["login"]]["unit"] = "gb";
				};
					
				if ($part["quota"] != 0) {
					$user[$part["login"]]["percent"] = round($part["sum"] / $part["quota"] * 100, 0);
				} else {
					$user[$part["login"]]["percent"] = "0";
				}
			}
				
			$this->view->users_statistic(array("total" => $data, "users" => $user));
		}
	}
}