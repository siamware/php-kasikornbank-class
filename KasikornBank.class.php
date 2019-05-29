<?php

/**
* KasikornBank Class
 *
 * @category  Payment Gateway
 * @package   php-kasikornbank-class
 * @author    Likecyber <cyber2friends@gmail.com>
 * @copyright Copyright (c) 2018-2019
 * @license   https://creativecommons.org/licenses/by/4.0/ Attribution 4.0 International (CC BY 4.0)
 * @link      https://github.com/likecyber/php-kasikornbank-class
 * @version   1.0.0
**/

class KasikornBank {
	public $credentials = array();
	public $cookie_file = null;

	public $curl_options = null;

	public $_TOKEN = null;
	public $_AccountID = null;

	public $response = null;
	public $http_code = null;

	public $online_gateway = "https://online.kasikornbankgroup.com/K-Online/";
	public $ebank_gateway = "https://ebank.kasikornbankgroup.com/retail/";

	public function __construct ($username = null, $password = null, $cookie_path = null) {
		if (!is_null($username) && !is_null($password)) {
			$this->setCredentials($username, $password);
		}
		$this->setCookieFile($cookie_path);
	}

	public function _reset () {
		$this->_TOKEN = null;
		$this->_AccountID = null;
		$this->response = null;
		$this->http_code = null;
	}

	public function setCredentials ($username, $password) {
		$this->credentials = array();
		$this->credentials["username"] = strval($username);
		$this->credentials["password"] = strval($password);
		$this->_reset();
	}

	public function setCookieFile ($cookie_path = null) {
		if (is_null($cookie_path)) $cookie_path = sys_get_temp_dir();
		if (file_exists($cookie_path) && is_dir($cookie_path)) {
			$cookie_path = tempnam(realpath($cookie_path), "KBA");
			register_shutdown_function(function () {
				@unlink($this->cookie_path);
			});
		} else {
			if (!file_exists($cookie_path)) file_put_contents($cookie_path, "");
			$cookie_path = realpath($cookie_path);
		}
		$this->cookie_file = $cookie_path;
	}

	public function request ($gateway, $path, $data = null) {
		$handle = curl_init($gateway.ltrim($path, "/"));
		if (!is_null($data)) {
			if (is_array($data) && !is_null($this->_TOKEN)) {
				$data = array_merge(array("org.apache.struts.taglib.html.TOKEN" => $this->_TOKEN), $data);
				$this->_TOKEN = null;
			}
			curl_setopt_array($handle, array(
				CURLOPT_POST => true,
				CURLOPT_POSTFIELDS => is_array($data) ? http_build_query($data) : $data
			));
		}
		curl_setopt_array($handle, array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_COOKIEFILE => $this->cookie_file,
			CURLOPT_COOKIEJAR => $this->cookie_file
		));
		if (is_array($this->curl_options)) curl_setopt_array($handle, $this->curl_options);
		$this->response = iconv("windows-874", "utf-8", curl_exec($handle));
		$this->http_code = curl_getinfo($handle, CURLINFO_HTTP_CODE);
		curl_close($handle);
		if (preg_match("<input type=\"hidden\" name=\"org\.apache\.struts\.taglib\.html\.TOKEN\" value=\"([0-9]*(\.[0-9]{1,16})?)\">", $this->response, $matches)) {
			$this->_TOKEN = $matches[1];
		}
		return $this->response;
	}

	public function check_response ($response = null) {
		if (is_null($response)) $response = $this->response;
		if (strpos($response, "Sorry, the current logged-in session") !== false) return false;
		if (strpos($response, "Sorry, your request cannot be processed") !== false) return false;
		if (strpos($response, "An unexpected response has been detected") !== false) return false;
		if (strpos($response, "only ONCE") !== false) return false;
		return true;
	}

	public function Login () {
		if (!isset($this->credentials["username"]) || !isset($this->credentials["password"])) return false;
		$this->request($this->online_gateway, "/login.do", array(
			"tokenId" => "0",
			"userName" => $this->credentials["username"],
			"password" => $this->credentials["password"],
			"cmd" => "authenticate",
			"locale" => "en"
		));
		if ($this->http_code === 302) {
			$this->request($this->online_gateway, "/ib/redirectToIB.jsp");
			if ($this->http_code === 200) {
				if (preg_match("/<input type=\"hidden\" name=\"txtParam\" value=\"(.{192})\" \/>/", $this->response, $matches)) {
					$this->request($this->ebank_gateway, "/security/Welcome.do", array(
						"txtParam" => $matches[1]
					));
					return $this->http_code === 302;
				}
			}
		}
		return false;
	}

	public function Logout () {
		$result = false;
		$this->request($this->online_gateway, "/logout.do?cmd=success");
		if ($this->http_code === 200) {
			if (preg_match("/<iframe id=\"logoutIBFrame\" src=\"https:\/\/ebank\.kasikornbankgroup\.com\/retail\/security\/Logout\.do\?action=retailuser&txtParam=(.*)\" width=\"0\" height=\"0\"><\/iframe>/", $this->response, $matches)) {
				$this->request($this->ebank_gateway, "/security/Logout.do?action=retailuser&txtParam=".$matches[1]);
				$result = $this->http_code === 302;
			}
		}
		$this->_reset();
		return $result;
	}

	public function CheckSession () {
		$this->request($this->online_gateway, "/checkSession.jsp");
		return $this->http_code === 200 && $this->check_response();
	}

	public function GetBalance ($account_number = null, $retry_login = true) {
		$result = array();
		$this->request($this->ebank_gateway, "/cashmanagement/inquiry/AccountSummary.do?action=list_domain2");
		if ($this->http_code === 200 && $this->check_response()) {
			if (preg_match_all("/<tr>(?:[\r\n\t]*)<td class=\"inner_table_center\">([0-9]{3}-[0-9]-[0-9]{5}-[0-9])<\/td>(?:[\r\n\t]*)<td class=\"inner_table_center\" colspan=\"2\">(?:.*)<\/td>(?:[\r\n\t]*)<td class=\"inner_table_right\">(-?[0-9]{1,3}(?:,[0-9]{3})*\.[0-9]{2})<\/td>(?:[\r\n\t]*)<td class=\"inner_table_right\">(?:-?[0-9]{1,3}(?:,[0-9]{3})*\.[0-9]{2})<\/td>(?:[\r\n\t]*)<\/tr>/", $this->response, $matches)) {
				foreach ($matches[1] as $key => $value) {
					$result[$value] = floatval(str_replace(",", "", $matches[2][$key]));
				}
			}
		} elseif ($retry_login) {
			if ($this->Login()) {
				return $this->GetBalance($account_number, false);
			}
		}
		if (!is_null($account_number)) {
			return isset($result[$account_number]) ? $result[$account_number] : false;
		} else {
			return count($result) > 0 ? $result : false;
		}
	}

	public function GetAccountID ($account_number = null, $retry_login = true) {
		$result = array();
		$this->request($this->ebank_gateway, "/accountinfo/AccountStatementInquiry.do");
		if ($this->http_code === 200 && $this->check_response()) {
			if (preg_match_all("/<option value=\"((?:1|2)[0-9]{3}(?:0[1-9]|1[0-2])(?:0[1-9]|1[0-9]|2[0-9]|3[0-1])[0-9]{6})\">([0-9]{3}-[0-9]-[0-9]{5}-[0-9]) (?:.*)<\/option>/", $this->response, $matches)) {
				foreach ($matches[2] as $key => $value) {
					$result[$value] = $matches[1][$key];
				}
			}
		} elseif ($retry_login) {
			if ($this->Login()) {
				return $this->GetAccountID($account_number, false);
			}
		}
		$this->_AccountID = $result;
		if (!is_null($account_number)) {
			return isset($result[$account_number]) ? $result[$account_number] : false;
		} else {
			return count($result) > 0 ? $result : false;
		}
	}

	public function ParseStatement ($response = null) {
		if (is_null($response)) $response = $this->response;
		$result = array();
		$line = strtok($response, PHP_EOL);
		while ($line !== false) {
			$csv_array = str_getcsv($line);
			if (count($csv_array) >= 7) {
				$result[] = $csv_array;
			}
			$line = strtok(PHP_EOL);
		}
		if (count($result) === 0) return false;
		array_walk($result, function(&$transaction) use ($result) {
			$transaction = array_splice($transaction, 0, count($result[0]));
			$transaction = array_combine($result[0], $transaction);
		});
		array_shift($result);
		return $result;
	}

	public function GetStatement ($account_number, $start_date = null, $end_date = null, $retry_login = true, $retry_token = true) {
		$account_id = null;
		if (preg_match("/^[0-9]{3}-[0-9]-[0-9]{5}-[0-9]$/", $account_number)) {
			if (isset($this->_AccountID[$account_number]) || $this->GetAccountID($account_number)) {
				$account_id = $this->_AccountID[$account_number];
			}
		} elseif (preg_match("/^(?:1|2)[0-9]{3}(?:0[1-9]|1[0-2])(?:0[1-9]|1[0-9]|2[0-9]|3[0-1])[0-9]{6}$/", $account_number)) {
			$account_id = $account_number;
			if (isset($this->_AccountID) || $this->GetAccountID()) {
				$account_number = array_search($account_id, $this->_AccountID);
				if (!$account_number) $account_number = null;
			}
		}
		if (is_null($account_number) || is_null($account_id)) return false;
		if (is_null($start_date) && is_null($end_date)) $start_date = date("Y-m-d", strtotime("-30 days") - date("Z") + 25200);
		if (is_null($end_date)) $end_date = date("Y-m-d", strtotime("-1 day") - date("Z") + 25200);
		if (is_null($start_date) || is_null($end_date)) return false;
		if (is_null($this->_TOKEN) && $retry_token) {
			$this->request($this->ebank_gateway, "/accountinfo/AccountStatementInquiry.do");
			return $this->GetStatement($account_number, $start_date, $end_date, $retry_login, false);
		}
		$start_time = strtotime($start_date);
		$end_time = strtotime($end_date);
		$this->request($this->ebank_gateway, "/accountinfo/AccountStatementInquiry.do", array(
			"action" => "select",
			"accountNo" => $account_id,
			"selDayFrom" => date("d", $start_time),
			"selMonthFrom" => date("m", $start_time),
			"selYearFrom" => date("Y", $start_time),
			"selDayTo" => date("d", $end_time),
			"selMonthTo" => date("m", $end_time),
			"selYearTo" => date("Y", $end_time),
			"period" => "3"
		));
		if ($this->http_code === 200 && $this->check_response()) {
			$this->request($this->ebank_gateway, "/accountinfo/AccountStatementInquiry.do", array(
				"action" => "sa_download",
				"selAccountNo" => "|".str_replace("-", "", $account_number)."||||||",
				"period" => "3",
				"selDayFrom" => date("d", $start_time),
				"selMonthFrom" => date("m", $start_time),
				"selYearFrom" => date("Y", $start_time),
				"selDayTo" => date("d", $end_time),
				"selMonthTo" => date("m", $end_time),
				"selYearTo" => date("Y", $end_time)
			));
			if ($this->http_code === 200 && $this->check_response()) {
				return $this->ParseStatement();
			} elseif ($retry_login) {
				if ($this->Login()) {
					return $this->GetStatement($account_number, $start_date, $end_date, false, true);
				}
			}
		} elseif ($retry_login) {
			if ($this->Login()) {
				return $this->GetStatement($account_number, $start_date, $end_date, false, true);
			}
		}
		return false;
	}

	public function GetTodayStatement ($account_number, $retry_login = true, $retry_token = true) {
		if (preg_match("/^[0-9]{3}-[0-9]-[0-9]{5}-[0-9]$/", $account_number)) {
			if (isset($this->_AccountID[$account_number]) || $this->GetAccountID($account_number)) {
				$account_number = $this->_AccountID[$account_number];
			}
		}
		if (is_null($this->_TOKEN) && $retry_token) {
			$this->request($this->ebank_gateway, "/cashmanagement/TodayAccountStatementInquiry.do");
			return $this->GetTodayStatement($account_number, $retry_login, false);
		}
		$this->request($this->ebank_gateway, "/cashmanagement/TodayAccountStatementInquiry.do", array(
			"acctId" => $account_number,
			"action" => "detail"
		));
		if ($this->http_code === 200 && $this->check_response()) {
			$this->request($this->ebank_gateway, "/cashmanagement/TodayAccountStatementInquiry.do", array(
				"acctId" => $account_number,
				"action" => "download"
			));
			if ($this->http_code === 200 && $this->check_response()) {
				return $this->ParseStatement();
			} elseif ($retry_login) {
				if ($this->Login()) {
					return $this->GetTodayStatement($account_number, false, true);
				}
			}
		} elseif ($retry_login) {
			if ($this->Login()) {
				return $this->GetTodayStatement($account_number, false, true);
			}
		}
		return false;
	}
}

?>
