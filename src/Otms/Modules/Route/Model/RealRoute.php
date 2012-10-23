<?php

/**
 * This file is part of the Workapp project.
 *
 * Business Process Module
 *
 * (c) Dmitry Samotoy <dmitry.samotoy@gmail.com>
 *
 */

namespace Otms\Modules\Route\Model;

use Engine\Modules\Model;
use PDO;

/**
 * Model\RealRoute class
 *
 * Класс-модель для работы с шаблонами бизнес-процессами
 *
 * @author Dmitry Samotoy <dmitry.samotoy@gmail.com>
 */
class RealRoute extends Model {
	/**
	 * ID БП
	 * 
	 * @var int
	 */
	private $_rid =  0;
	
	/**
	 * Получить БП по ID
	 * 
	 * @param int $id
	 * @return array
	 */
	function getRoute($id) {
		$this->_rid = $id;
		
		$sql = "SELECT id, name FROM route WHERE id = :id LIMIT 1";
		
		$res = $this->registry['db']->prepare($sql);
		$param = array(":id" => $id);
		$res->execute($param);
		$rows = $res->fetchAll(PDO::FETCH_ASSOC);

		return $rows;
	}
	
	/**
	 * Получить все этапы БП
	 * 
	 * @param
	 *    $this->_rid
	 * @return array
	 */
	function getSteps() {
		$sql = "SELECT rrt.step_id, rs.name
					FROM route_route_tasks AS rrt
					LEFT JOIN route_step AS rs ON (rs.id = rrt.step_id)
					WHERE rrt.rid = :rid
					GROUP BY rrt.step_id
					ORDER BY rs.order, rrt.step_id";
	
		$res = $this->registry['db']->prepare($sql);
		$param = array(":rid" => $this->_rid);
		$res->execute($param);
		return $step = $res->fetchAll(PDO::FETCH_ASSOC);
	}
	
	/**
	 * Получить действие (алгоритм) перехода при завершении этапа
	 * 
	 * @param int $step_id
	 * @return array
	 */
	function getRouteAction($step_id) {
		$sql = "SELECT ra.ifdata, ra.ifcon, ra.ifval, ra.goto, rs.name AS gotoval, rtr.name AS ifdataval
			FROM route_action AS ra
			LEFT JOIN route_step AS rs ON (rs.id = ra.goto)
			LEFT JOIN route_tasks_results AS rtr ON (rtr.id = ra.ifdata)
			WHERE ra.step_id = :step_id";
	
		$res = $this->registry['db']->prepare($sql);
		$param = array(":step_id" => $step_id);
		$res->execute($param);
		$data = $res->fetchAll(PDO::FETCH_ASSOC);
	
		return $data;
	}
	
	/**
	 * Получить задачи для БП
	 * 
	 * @param int $rid
	 * @return array
	 *    $result[$i]["rid"]
	 *    $result[$i]["step_id"]
	 *    $result[$i]["task"] - JSON array
	 */
	function getTasks($rid) {
		$sql = "SELECT tid, step_id FROM route_route_tasks WHERE rid = :rid ORDER BY step_id";
	
		$res = $this->registry['db']->prepare($sql);
		$param = array(":rid" => $rid);
		$res->execute($param);
		$step = $res->fetchAll(PDO::FETCH_ASSOC);
	
		$result = array(); $i=0;
	
		foreach($step as $part) {
			$sql = "SELECT id AS tid, json FROM route_tasks WHERE id = :id LIMIT 1";
				
			$res = $this->registry['db']->prepare($sql);
			$param = array(":id" => $part["tid"]);
			$res->execute($param);
			$data = $res->fetchAll(PDO::FETCH_ASSOC);
				
			$result[$i] = $data[0];
			$result[$i]["rid"] = $rid;
			$result[$i]["step_id"] = $part["step_id"];
			$result[$i]["task"] = json_decode($data[0]["json"], true);
				
			$i++;
		}
	
		return $result;
	}
	
	/**
	 * Удалить БП
	 * 
	 * @param int $id
	 */
	function delRoute($id) {
		$sql = "SELECT tid, step_id FROM route_route_tasks WHERE rid = :rid";
			
		$res = $this->registry['db']->prepare($sql);
		$param = array(":rid" => $id);
		$res->execute($param);
		$data = $res->fetchAll(PDO::FETCH_ASSOC);
	
		foreach($data as $part) {
			$sql = "DELETE FROM route_tasks WHERE id = :id LIMIT 1";
				
			$res = $this->registry['db']->prepare($sql);
			$param = array(":id" => $part["tid"]);
			$res->execute($param);
				
			$sql = "DELETE FROM route_step WHERE id = :id LIMIT 1";
				
			$res = $this->registry['db']->prepare($sql);
			$param = array(":id" => $part["step_id"]);
			$res->execute($param);
		}
	
		$sql = "DELETE FROM route_route_tasks WHERE rid = :rid";
			
		$res = $this->registry['db']->prepare($sql);
		$param = array(":rid" => $id);
		$res->execute($param);
	
		$sql = "DELETE FROM route WHERE id = :id LIMIT 1";
	
		$res = $this->registry['db']->prepare($sql);
		$param = array(":id" => $id);
		$res->execute($param);
	}
	
	/**
	 * Требование результата завершения этапа (задачи) в ходе БП
	 * 
	 * @param int $tid - ID задачи
	 * @return array
	 */
	function getTaskData($tid) {
		$sql = "SELECT rrt.step_id, rrt.rid, rs.order
		FROM route_route_tasks AS rrt
		LEFT JOIN route_step AS rs ON (rs.id = rrt.step_id)
		WHERE rrt.tid = :tid LIMIT 1";
		
		$res = $this->registry['db']->prepare($sql);
		$param = array(":tid" => $tid);
		$res->execute($param);
		$data = $res->fetchAll(PDO::FETCH_ASSOC);

		$sql = "SELECT rrt.tid, rs.name, rt.json
		FROM route_route_tasks AS rrt
		LEFT JOIN route_step AS rs ON (rs.id = rrt.step_id)
		LEFT JOIN route_tasks AS rt ON (rt.id = rrt.tid)
		WHERE rrt.rid = :rid AND rs.order = :order";
		
		$res = $this->registry['db']->prepare($sql);
		$param = array(":rid" => $data[0]["rid"], "order" => $data[0]["order"]);
		$res->execute($param);
		$data = $res->fetchAll(PDO::FETCH_ASSOC);
		
		$result = array(); $i=0;
		
		foreach($data as $part) {
			$result[$i] = $this->getResult($part["tid"]);
			
			$result[$i]["step_name"] = $part["name"];
			$task = json_decode($part["json"], true);
			$result[$i]["task_name"] = $task["taskname"];
			
			$i++;
		}
		
		return $result;
	}
	
	/**
	 * Получить вопрос (требуемый результат) для задачи
	 * 
	 * @param int $tid - ID задачи
	 * @return array
	 */
	function getResult($tid) {
		$sql = "SELECT id, name, type, datatype FROM route_tasks_results WHERE tid = :tid";

		$res = $this->registry['db']->prepare($sql);
		$param = array(":tid" => $tid);
		$res->execute($param);
		$data = $res->fetchAll(PDO::FETCH_ASSOC);
	
		return $data;
	}
}
?>