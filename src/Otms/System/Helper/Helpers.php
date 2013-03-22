<?php

/**
 * This file is part of the Workapp project.
 *
 * (c) Dmitry Samotoy <dmitry.samotoy@gmail.com>
 *
 */

namespace Otms\System\Helper;

use Engine\Helper;
use Otms\System\Model\Settings;
use Otms\Modules\Mail\Model\Mail;
use Phpmailer\Phpmailer;

/**
 * System Helpers class
 *
 * Реализует методы "помошники".
 * Обёртки над существующими функциями.
 *
 * @author Dmitry Samotoy <dmitry.samotoy@gmail.com>
 */

class Helpers extends Helper {
	/**
	* Переменная содержит тип сообщения text или html
	* 
	* @var string
	 */
	private $text = NULL;

	/**
	 * Отправка почтового сообщения.
	 * Функйия формирует сообщение из задачи и комментариев и вызывает метод $this->phpmailer
	 * 
	 * @param string $email
	 * @param string $theme
	 * @param string $post
	 * @param string $comments
	 */
	function sendMail($email, $theme, $post, $comments = FALSE) {
		$settings = new Settings();
		 
		if (isset($this->registry["user"])) {
			$who = $this->registry["user"]->getUserInfo($post[0]["who"]);
			$post[0]["who"] = $who["name"] . " " . $who["soname"];
			 
			$post[0]["start"] = $settings->editDate($post[0]["start"]);
			$post[0]["ending"] = $settings->editDate($post[0]["ending"]);
			 
			for($i=0; $i < count($comments); $i++) {
				$comments[$i]["timestamp"] = $settings->editDate($comments[$i]["timestamp"]);
			}
			 
			$text = $this->view->render("mail", array("post" => $post, "comments" => $comments));
			 
			$res_post["subject"] = " <<< Задача " . $post[0]["id"] . ". " . $theme . " >>>";
			$res_post["textfield"] = $text;
			$res_post["to"] = $email;
			 
			$this->phpmailer($res_post);
		}
	}

	/**
	 * Отправляет сообщение методом $this->phpmailer.
	 * Сообщение генерируется по шаблону notify.tpl.
	 * 
	 * @param array $post:
	 *    $post[$i]["id"] - номер задачи
	 *    $post[$i]["text"] - текст задачи
	 *    $post[$i]["email"]
	 * @param int $num - количество задач
	 */
	function sendNotify($post, $num) {
		$text = $this->view->render("notify", array("sitename" => $this->registry["siteName"], "post" => $post));

		$res_post["subject"] = " <<< Day tasks [" . $num . " task]>>>";
		$res_post["textfield"] = $text;
		$res_post["to"] = $post[0]["email"];

		$this->phpmailer($res_post);
	}

	/**
	 * Отправка задачи (с вложениями) по почте в другую систему
	 * @param string $email
	 * @param string $subject
	 * @param array $post
	 */
	function sendTask($email, $subject, $post = null) {
		$this->text = true;
		 
		$res_post["subject"] = json_encode($subject);
		$res_post["textfield"] = json_encode($post);
		$res_post["to"] = $email;
		$res_post["mail"] = $post["mail"];
		if (isset($res_post["mail_id"])) {
			$res_post["mail_id"] = $post["mail_id"];
		}
		if (isset($post["attaches"])) {
			$res_post["attaches"] = $post["attaches"];
		}

		$this->phpmailer($res_post);
	}

	/**
	 * Отправка письма внешней библиотекой Phpmailer
	 * Возвращает текст ошибки или FALSE в случае успешной отправки
	 * 
	 * @param array $post
	 * @param string $smtp
	 * @param string $fromName
	 * @return boolean|multitype:string
	 */
	function phpmailer($post, $smtp = null, $fromName = null) {
		$settings = new Settings();
		
		$mailClass = new Mail();

		if ($smtp == null) {
			$smtp = $settings->getMailbox();
		}
		 
		if ($fromName == null) {
			$fromName = $this->registry["mailSenderName"];
		}
		 
		$mailer = new Phpmailer();
			
		$err = array();
			
		$mailer->SMTPDebug = 0;
			
		$mailer->CharSet = "utf-8";

		$mailer->IsSMTP();
		$mailer->Host = $smtp["server"];
		$mailer->Port = $smtp["port"];
			
		if ($smtp["ssl"] == "ssl") {
			$mailer->SMTPSecure = "ssl";
		}
			
		if ( ($smtp["login"]) and ($smtp["password"]) ) {
			$mailer->SMTPAuth = true;
			$mailer->Username = $smtp["login"];
			$mailer->Password = $smtp["password"];
		} else {
			$mailer->SMTPAuth = false;
		}
			
		$mailer->From = $smtp["email"];
		$mailer->FromName = $fromName;
			
		if ($post["to"] == null) {
			$err[] = "Addressees aren't set";
		} else {
			$to = explode(",", $post["to"]);
			for($i=0; $i<count($to); $i++) {
				$mailer->AddAddress($to[$i]);
			}
		}

		if (isset($post["attaches"])) {
			foreach($post["attaches"] as $part) {
				$filename = mb_substr($part, mb_strrpos($part, DIRECTORY_SEPARATOR) + 1, mb_strlen($part)-mb_strrpos($part, DIRECTORY_SEPARATOR));
					
				if (substr($part, 0, 1) != "/") {
					$dir = $this->registry["path"]["upload"];
					$md5 = $mailClass->getAttachFileMD5($part);
				} else {
					if ( (isset($post["mail"])) and ($post["mail"]) ) {
						$dir = $this->registry["path"]["attaches"];
						$md5 = $mailClass->getFile($post["mail_id"], $filename);
					} else {
						$dir = $this->registry["path"]["upload"];
						$md5 = $mailClass->getFileMD5($part);
					}
				}

				$mailer->AddAttachment($this->registry["rootPublic"] . $dir . $md5, $filename);
			}
		}
			
		if (!$this->text) {
			$mailer->IsHTML(true);

			$mailer->Subject = $post["subject"];
			$mailer->Body = $post["textfield"];
			$mailer->AltBody = strip_tags($post["textfield"]);
		} else {
			$mailer->IsHTML(false);

			$mailer->Subject = base64_encode($post["subject"]);
			$mailer->Body = base64_encode($post["textfield"]);
		}
			
		if ($post["textfield"] == null) {
			$err[] = "Empty mail";
		};

		if (count($err) == 0) {

			if(!$mailer->Send()) {
				return $mailer->ErrorInfo;
			} else {
				return false;
			}
		} else {
			return $err;
		}
	}
}