<?php
 
require_once '../include/DbHandler.php';
require '.././libs/Slim/Slim.php';
 
\Slim\Slim::registerAutoloader();
 
$app = new \Slim\Slim();
// User id from db - Global Variable
$phone_num = NULL;

function verifyRequiredParams($required_fields) {
    $error = false;
    $error_fields = "";
    $request_params = array();
    $request_params = $_REQUEST;
    // Handling PUT request params
    if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
        $app = \Slim\Slim::getInstance();
        parse_str($app->request()->getBody(), $request_params);
    }
    foreach ($required_fields as $field) {
        if (!isset($request_params[$field]) || strlen(trim($request_params[$field])) <= 0) {
            $error = true;
            $error_fields .= $field . ', ';
        }
    }
 
    if ($error) {
        // Required field(s) are missing or empty
        // echo error json and stop the app
        $response = array();
        $app = \Slim\Slim::getInstance();
        $response["error"] = true;
        $response["message"] = 'Required field(s) ' . substr($error_fields, 0, -2) . ' is missing or empty';
        echoRespnse(400, $response);
        $app->stop();
    }
}
/**
 * Echoing json response to client
 * @param String $status_code Http response code
 * @param Int $response Json response
 */
function echoRespnse($status_code, $response) {
    $app = \Slim\Slim::getInstance();
    // Http response code
    $app->status($status_code);
 
    // setting response content type to json
    $app->contentType('application/json');
 
    echo json_encode($response);
}

$app->post('/register', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('phone_num', 'name', 'user_type', 'operator', 'sim_type'));
			
			$response = array();
			
			$phone_num = $app->request->post('phone_num');
			$name = $app->request->post('name');
			$user_type = $app->request->post('user_type');
			$operator = $app->request->post('operator');
			$sim_type = $app->request->post('sim_type');
			
			$db = new DbHandler();
            $res = $db->createUser($phone_num, $name, $user_type, $operator, $sim_type);
			
			if ($res == USER_CREATED_SUCCESSFULLY) {
                $response["error"] = false;
                $response["message"] = "You are successfully registered";
                echoRespnse(201, $response);
            } else if ($res == USER_CREATE_FAILED) {
                $response["error"] = true;
                $response["message"] = "Oops! An error occurred while registereing";
                echoRespnse(200, $response);
            } else if ($res == USER_ALREADY_EXISTED) {
                $response["error"] = true;
                $response["message"] = "Sorry, this email already existed";
                echoRespnse(200, $response);
            }
});


$app->post('/login', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('phone_num'));
 
            // reading post params
            $phone_num = $app->request()->post('phone_num');
            $response = array();
 
            $db = new DbHandler();
            // check for correct email and password
            if ($user = $db->getUserByEmail($phone_num)) {
                // get the user by phone num
                //$user = $db->getUserByEmail($phone_num);
 
                if ($user != NULL) {
                    $response["error"] = false;
					$response['phone_num'] = $user['phone_num'];
                    $response['name'] = $user['name'];
                    $response['user_type'] = $user['user_type'];
                    $response['operator'] = $user['operator'];
                    $response['sim_type'] = $user['sim_type'];
                } else {
                    // unknown error occurred
                    $response['error'] = true;
                    $response['message'] = "An error occurred. Please try again";
                }
            } else {
                // user credentials are wrong
                $response['error'] = true;
                $response['message'] = 'Phone num does not exist';
            }
 
            echoRespnse(200, $response);
});
$app->post('/priority', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('phone_num', 'pr_tt', 'pr_data', 'pr_roam', 'pr_sms'));
			
			$response = array();
			
			$phone_num = $app->request->post('phone_num');
			$pr_tt = $app->request->post('pr_tt');
			$pr_data = $app->request->post('pr_data');
			$pr_roam = $app->request->post('pr_roam');
			$pr_sms = $app->request->post('pr_sms');
			
			$db = new DbHandler();
            $res = $db->setPriority($phone_num, $pr_tt, $pr_data, $pr_roam, $pr_sms);
			
			if($res) {
				$response['error'] = false;
                $response['message'] = "updated";
			}
			else
			{
				$response["error"] = true;
				$response["message"] = "Failed";
			}
			echoRespnse(201, $response);
});

$app->post('/scorer', function() use ($app) {
			verifyRequiredParams(array('phone_num', 'user_tt', 'user_data', 'user_roam', 'user_sms', 'operator', 'sim_type'));
			
			$price_tt = 1;
			$price_data = 0.25;
			$price_roam = 1.5;
			$price_sms = 1;
			
			$phone_num = $app->request->post('phone_num');
			$user_tt = $app->request->post('user_tt');
			$user_data = $app->request->post('user_data');
			$user_roam = $app->request->post('user_roam');
			$user_sms = $app->request->post('user_sms');
			$operator = $app->request->post('operator');
			$sim_type = $app->request->post('sim_type');
			
			$db = new DbHandler();
            $priority_array = $db->getPriority($phone_num);
			//echo "priority of tt is" . $priority_array['pr_tt'];
			//priority array is ready now
			$response = array();
			$response["packs"] = array();
			$pack_array = $db->getPack($operator, $sim_type);
			
			while ($pack = $pack_array->fetch_assoc()) {
                $tmp = array();
                $tmp["pack_id"] = $pack["pack_id"];
                $tmp["pack_operator"] = $pack["pack_operator"];
                $tmp["description"] = $pack["description"];
				$tmp["pack_type"] = $pack["pack_type"];
				$tmp["price"] = $pack["price"];
				$tmp["pack_for_sim"] = $pack["pack_for_sim"];
				$tmp["talktime"] = $pack["talktime"];
				$tmp["data"] = $pack["data"];
				$tmp["roaming"] = $pack["roaming"];
				$tmp["sms"] = $pack["sms"];
				// 5 - ((p-u)/u)
				//tt
				if($user_tt <= $tmp["talktime"]) {
					$score_tt = $priority_array['pr_tt'] - (($tmp["talktime"] - $user_tt) / $user_tt);
					if($score_tt < 0) {
						$score_tt = 0;
					}
				}
				else {
					$score_tt = -$priority_array['pr_tt'] * 10;
				}
				//data
				if($user_data <= $tmp["data"]) {
					$score_data = $priority_array['pr_data'] - (($tmp["data"] - $user_data) / $user_data);
					if($score_data < 0) {
						$score_data = 0;
					}
				}
				else {
					$score_data = -$priority_array['pr_data'] * 10;
				}
				//roaming
				if($user_roam <= $tmp["roaming"]) {
					$score_roam = $priority_array['pr_roam'] - (($tmp["roaming"] - $user_roam) / $user_roam);
					if($score_roam < 0) {
						$score_roam = 0;
					}
				}
				else {
					$score_roam = -$priority_array['pr_roam'] * 10;
				}
				//sms
				if($user_sms <= $tmp["sms"]) {
					$score_sms = $priority_array['pr_sms'] - (($tmp["sms"] - $user_sms) / $user_sms);
					if($score_sms < 0) {
						$score_sms = 0;
					}
				}
				else {
					$score_sms = -$priority_array['pr_sms'] * 10;
				}
				//price
				$score_price = 0;
				$comp_score = $score_tt + $score_data + $score_roam + $score_sms + $score_price;//score out of 20
				$price = ($price_tt * $tmp["talktime"]) + ($price_data * $tmp["data"]) + ($price_roam * $tmp["roaming"]) + ($price_sms * $tmp["sms"]);
				$discount = ($price - $tmp["price"]) / $price;
				$money_score = 80 * $discount;
				$tot_score = $comp_score + $money_score;
				$tmp["score"] = $tot_score;
				array_push($response["packs"], $tmp);
            }
			//echo($response)
			echoRespnse(200, $response);
});
$app->post('/compare', function() use ($app) {
			verifyRequiredParams(array('operator1', 'description1', 'operator2', 'description2'));
			
			$response1 = array();
			//$response2 = array();
			
			//$phone_num = $app->request->post('phone_num');
			$operator1 = $app->request->post('operator1');
			$description1 = $app->request->post('description1');
			$operator2 = $app->request->post('operator2');
			$description2 = $app->request->post('description2');
			
			$db = new DbHandler();
            $res1 = $db->getPlanCompare($operator1, $description1);
			$res2 = $db->getPlanCompare($operator2, $description2);
			
			if ($res1 != NULL) {
                    $response1["error"] = false;
					$response1['pack_id'] = $res1['pack_id'];
                    $response1['pack_operator'] = $res1['pack_operator'];
					$response1['description'] = $res1['description'];
                    $response1['pack_type'] = $res1['pack_type'];
					$response1['price'] = $res1['price'];
                    $response1['pack_for_sim'] = $res1['pack_for_sim'];
                    $response1['talktime'] = $res1['talktime'];
					$response1['data'] = $res1['data'];
					$response1['roaming'] = $res1['roaming'];
					$response1['sms'] = $res1['sms'];
            } else {
                    // unknown error occurred
                    $response1['error'] = true;
                    $response1['message'] = "An error occurred in response1. Please try again";
             }
			 if ($res2 != NULL) {
                    $response1["error2"] = false;
					$response1['pack_id2'] = $res2['pack_id'];
                    $response1['pack_operator2'] = $res2['pack_operator'];
					$response1['description2'] = $res2['description'];
                    $response1['pack_type2'] = $res2['pack_type'];
					$response1['price2'] = $res2['price'];
                    $response1['pack_for_sim2'] = $res2['pack_for_sim'];
                    $response1['talktime2'] = $res2['talktime'];
					$response1['data2'] = $res2['data'];
					$response1['roaming2'] = $res2['roaming'];
					$response1['sms2'] = $res2['sms'];
            } else {
                    // unknown error occurred
                    $response['error2'] = true;
                    $response['message2'] = "An error occurred in response2. Please try again";
             }
			 echoRespnse(201, $response1);
});
$app->post('/planautofill', function() use ($app) {
			verifyRequiredParams(array('operator', 'sim_type'));
			
			//$response1 = array();
			//$phone_num = $app->request->post('phone_num');
			$operator = $app->request->post('operator');
			$sim_type = $app->request->post('sim_type');
			$db = new DbHandler();
            
			$response = array();
			$response["packs"] = array();
			$pack_array = $db->getPack($operator, $sim_type);
			
			while ($pack = $pack_array->fetch_assoc()) {
                $tmp = array();
                $tmp["pack_id"] = $pack["pack_id"];
                $tmp["pack_operator"] = $pack["pack_operator"];
                $tmp["description"] = $pack["description"];
				$tmp["pack_for_sim"] = $pack["pack_for_sim"];
				$tmp["price"] = $pack["price"];
				array_push($response["packs"], $tmp);
            }
			echoRespnse(200, $response);
});
$app->post('/historysave', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('phone_num', 'month', 'year', 'tt', 'data', 'roam', 'sms'));
			
			$response = array();
			
			$phone_num = $app->request->post('phone_num');
			$month = $app->request->post('month');
			$year = $app->request->post('year');
			//$plan_name = $app->request->post('plan_name');
			$tt = $app->request->post('tt');
			$data = $app->request->post('data');
			$roam = $app->request->post('roam');
			$sms = $app->request->post('sms');
			
			$db = new DbHandler();
			//$pack_id = $db->getPackId($plan_name);
            $res = $db->postHistory($phone_num, $month, $year, $tt, $data, $roam, $sms);
			//error handling to be done echoRespnse(200, $response);
			
});
$app->post('/showpriority', function() use ($app) {
			verifyRequiredParams(array('phone_num'));
			$response = array();
			
			$phone_num = $app->request->post('phone_num');
			$db = new DbHandler();
            $priority_array = $db->getPriority($phone_num);
			if ($priority_array != NULL) {
                    $response["error"] = false;
					$response['phone_num'] = $priority_array['phone_num'];
                    $response['pr_tt'] = $priority_array['pr_tt'];
                    $response['pr_data'] = $priority_array['pr_data'];
                    $response['pr_roam'] = $priority_array['pr_roam'];
                    $response['pr_sms'] = $priority_array['pr_sms'];
            } else {
                    // unknown error occurred
                    $response['error'] = true;
                    $response['message'] = "An error occurred. Please try again";
            }
			echoRespnse(200, $response);
});
$app->run();
?>