<?php
 
/**
 * Class to handle all db operations
 * This class will have CRUD methods for database tables
 *
 * @author Ravi Tamada
 */
class DbHandler {
 
    private $conn;
 
    function __construct() {
        require_once dirname(__FILE__) . './DbConnect.php';
        // opening db connection
        $db = new DbConnect();
        $this->conn = $db->connect();
    }
  
	 public function createUser($phone_num, $name, $user_type, $operator, $sim_type) {
		 $response = array();
		 if (!$this->isUserExists($phone_num)) {
			 $stmt = $this->conn->prepare("INSERT into user(phone_num, name, user_type, operator, sim_type) values(?, ?, ?, ?, ?)");
			 $stmt->bind_param("sssss",$phone_num, $name, $user_type, $operator, $sim_type);
			 $result = $stmt->execute(); 
			 $stmt->close();
			 
			if($user_type === "student") {
					$pr_tt = 5;
					$pr_data = 8;
					$pr_roam = 4;
					$pr_sms = 3;
					//$pr_price = 3;
			}
			else if($user_type === "working") {
					$pr_tt = 7;
					$pr_data = 4;
					$pr_roam = 6;
					$pr_sms = 3;
					//$pr_price = 2;
			}
			else if($user_type === "traveller") {
					$pr_tt = 3;
					$pr_data = 7;
					$pr_roam = 7;
					$pr_sms = 3;
					//$pr_price = 2;
			}
			else {
					$pr_tt = 5;
					$pr_data = 5;
					$pr_roam = 5;
					$pr_sms = 5;
					//$pr_price = 2;
			}
			$pr_price = 0;
			$stmt2 = $this->conn->prepare("INSERT into priority(phone_num, pr_tt, pr_data, pr_roam, pr_sms, pr_price) values(?, ?, ?, ?, ?, ?)");
			$stmt2->bind_param("sddddd",$phone_num, $pr_tt, $pr_data, $pr_roam, $pr_sms, $pr_price);
			$result = $stmt2->execute(); 
			$stmt2->close();
			 // Check for successful insertion
            if ($result) {
                // User successfully inserted
                return USER_CREATED_SUCCESSFULLY;
            } else {
                // Failed to create user
                return USER_CREATE_FAILED;
            }
        } else {
            // User with same Phone_num already existed in the db
            return USER_ALREADY_EXISTED;
        }
		return $response;
	 }
	 private function isUserExists($phone_num) {
        $stmt = $this->conn->prepare("SELECT phone_num from user WHERE phone_num = ?");
        $stmt->bind_param("s", $phone_num);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }
	public function getUserByEmail($phone_num) {
        $stmt = $this->conn->prepare("SELECT phone_num, name, user_type, operator, sim_type FROM user WHERE phone_num = ?");
        $stmt->bind_param("s", $phone_num);
        if ($stmt->execute()) {
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $user;
        } else {
            return NULL;
        }
    }
	public function setPriority($phone_num, $pr_tt, $pr_data, $pr_roam, $pr_sms)
	{
		//$pr_price = 10 - ($pr_tt + $pr_data + $pr_roam + $pr_sms);
		$pr_price = 0;
		$stmt = $this->conn->prepare("UPDATE priority set pr_tt = ?, pr_data = ?, pr_roam = ?, pr_sms = ?, pr_price = ? WHERE phone_num = ?");
		$stmt->bind_param("ddddds", $pr_tt, $pr_data, $pr_roam, $pr_sms, $pr_price, $phone_num);
		$res = $stmt->execute();
		$stmt->close();
		return $res;
	}
	public function getPriority($phone_num) {
        $stmt = $this->conn->prepare("SELECT phone_num, pr_tt, pr_data, pr_roam, pr_sms FROM priority WHERE phone_num = ?");
        $stmt->bind_param("s", $phone_num);
        if ($stmt->execute()) {
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $user;
        } else {
            return NULL;
        }
    }
	public function getPack($operator, $sim_type) {
		if($operator === "all") {
			if($sim_type === "both") {
				$stmt = $this->conn->prepare("SELECT * FROM plans WHERE 1");
				//$stmt->bind_param("s", $phone_num);
				$stmt->execute();
				$packs = $stmt->get_result();
				$stmt->close();
				return $packs;
			}
			else {
				$stmt = $this->conn->prepare("SELECT * FROM plans WHERE pack_for_sim = ?");
				$stmt->bind_param("s", $sim_type);
				$stmt->execute();
				$packs = $stmt->get_result();
				$stmt->close();
				return $packs;
			}
		}
		else {
			if($sim_type === "both") {
				$stmt = $this->conn->prepare("SELECT * FROM plans WHERE pack_operator = ?");
				$stmt->bind_param("s", $operator);
				$stmt->execute();
				$packs = $stmt->get_result();
				$stmt->close();
				return $packs;
			}
			else {
				$stmt = $this->conn->prepare("SELECT * FROM plans WHERE pack_operator = ? and pack_for_sim = ?");
				$stmt->bind_param("ss", $operator, $sim_type);
				$stmt->execute();
				$packs = $stmt->get_result();
				$stmt->close();
				return $packs;
			}
		}
	}
	public function getPlanCompare($operator, $description) {
        $stmt = $this->conn->prepare("SELECT * FROM plans WHERE pack_operator = ? and description = ?");
        $stmt->bind_param("ss", $operator, $description);
        if ($stmt->execute()) {
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();
            return $user;
        } else {
            return NULL;
        }
    }
	//write fnc to get pack id
	/*public function getPackId($plan_name) {
        $stmt = $this->conn->prepare("SELECT pack_id FROM plans WHERE description = ?");//query to insert 
        $stmt->bind_param("s", $plan_name);
        if ($user = $stmt->execute()) {
            $stmt->close();
            return $user;
        } else {
            return NULL;
        }
    }*/
	public function postHistory($phone_num, $month, $year, $tt, $data, $roam, $sms) {
        $stmt = $this->conn->prepare("INSERT into plan_history(phone_num, month, year, tt, data, roam, sms) values(?, ?, ?, ?, ?, ?, ?)");//query to insert 
        $stmt->bind_param("ssiiiii", $phone_num, $month, $year, $tt, $data, $roam, $sms);
        if ($user = $stmt->execute()) {
            $stmt->close();
            return $user;
        } else {
            return NULL;
        }
    }
	
	
}


?>