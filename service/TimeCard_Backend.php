<?php
/**
 * Documentation, License etc.
 *
 * @package TimeCard_Backend
 */
 include_once 'Base/PostgresBase.php';
 
class TimecardBackend {    
    
    function __construct(&$config) {
        $params = @file_get_contents($config);
        $params = json_decode($params);
        $this->pgConn = new postgres\PostgresBase($params->db->database, $params->db->host, $params->db->user, $params->db->password);
    }
    
    private function echoFailedAuthenticationResponse() {
            $response = '{"status":"error", "message":"Authentication Failed"}';
            $this->echoResponse($response);
    }
    
    private function fixArguments (&$args) {
        $return = array();        
        foreach($args as $key=>$value) {
            if (is_string($value))
                 $value = str_replace("'", "", $value);
            $return[$key] = $value;
        }
        return (object)$return;    
    }
    
    private function echoResponse(&$response) {
        /*
        header('Content-Type: text/html; charset=utf-8');
        header('Content-Type: application/json');
        header("Access-Control-Allow-Origin: *");
        */
        echo $response;        
    }
    
    private function verifyAdmin(&$id, &$sessionId, &$type) {
        $query = "SELECT CASE WHEN count = 1 THEN 1 ELSE 0 END result
        FROM (
            SELECT count(*) as count
            FROM customers_auth a
            JOIN customers_session b
            ON a.uid = b.user_id AND a.uid = $id AND a.type = '$type' AND a.type='admin' AND b.session_id = '$sessionId'
        )a";
        return boolval($this->pgConn->fetchQueryResult($query)[0][0]);
    }
    
    private function verifyEmployee(&$id, &$sessionId, &$type) {
        $query = "SELECT EXISTS(
            SELECT count(*) as count
            FROM customers_auth a
            JOIN customers_session b
            ON a.uid = b.user_id AND a.uid = $id AND a.type = '$type' and a.type in ('l1', 'admin')  AND b.session_id = '$sessionId'
        )";
        return boolval($this->pgConn->fetchQueryResult($query)[0][0]);
    }
    
    function addCompany(&$args) {
        $fixedArgs = $this->fixArguments($args);
        if ($this->verifyAdmin($fixedArgs->user_id, $fixedArgs->session_id, $fixedArgs->type) === true) {
            $query = "INSERT INTO admin.company(name, vat,  address, user_id ) VALUES ('{$fixedArgs->company_name}', '{$fixedArgs->vat}',  '{$fixedArgs->address}',  '{$fixedArgs->user_id}') ON CONFLICT(vat) DO UPDATE SET name=EXCLUDED.name, address=EXCLUDED.address, user_id=EXCLUDED.user_id;";
            $result = $this->pgConn->fetchRawQueryResult($query);
            $response = "";
            if (pg_affected_rows($result) == 1)
                $response =  '{"status":"success", "message":"company added/modified"}';
            else
                $response =  '{"status":"error", "message":"Failed to add company. Company with same VAT already exists!"}';
            
            $this->echoResponse($response);
        }
        else 
            $this->echoFailedAuthenticationResponse();
    }
    
    function addEmployeeExpenseToProject(&$args) {
        $fixedArgs = $this->fixArguments($args);
        if ($this->verifyAdmin($fixedArgs->user_id, $fixedArgs->session_id, $fixedArgs->type) === true) {
           $values = "";
           foreach ($fixedArgs->expenses as $expense) {
                $tmpValues = "( '{$fixedArgs->case_number}',  {$expense->user_id}, '{$expense->description}', {$expense->amount}, {$expense->vat}, '{$expense->start_date}', '{$expense->end_date}', '{$expense->receipt_date}'),";
                $values .= $tmpValues;
            }
            $values = substr($values, 0, strlen($values)-1);
            
            $query = "INSERT INTO admin.project_personnel_expense (case_number, user_id, description, amount, vat, start_date, end_date, date_created) VALUES $values";
            $response = "";
            #print("\n\n".$query."\n\n");
            $result = $this->pgConn->fetchRawQueryResult($query);
            
            if (pg_affected_rows($result) > 0)
                $response =  '{"status":"success", "message":"expenses added!"}';
            else
                $response =  '{"status":"error", "message":"Failed to add expenses. Contact System Administrator"}';
            
            $this->echoResponse($response);
            
        }
        else 
            $this->echoFailedAuthenticationResponse();
    }
    
    function addExpenseToProject(&$args) {
        $fixedArgs = $this->fixArguments($args);
        if ($this->verifyAdmin($fixedArgs->user_id, $fixedArgs->session_id, $fixedArgs->type) === true) {
            $values = "";
            $caseNumber = $fixedArgs->case_number;
            
            
            foreach ($fixedArgs->expenses as $expense) {
                $tmpValues = "( '{$fixedArgs->case_number}', '{$expense["description"]}', {$expense["amount"]}, {$expense["vat"]}, '{$expense["date"]}'),";
                $values .= $tmpValues;
            }
            $values = substr($values, 0, strlen($values)-1);
            
            $query = "INSERT INTO admin.project_expense (case_number, description, amount, vat, date) VALUES $values";
            $result = $this->pgConn->fetchRawQueryResult($query);

            $response = "";
            if (pg_affected_rows($result) > 0)
                $response =  '{"status":"success", "message":"expenses added!"}';
            else
                $response =  '{"status":"error", "message":"Failed to add expenses. Contact System Administrator"}';
            
            $this->echoResponse($response);
            
        }
        else 
            $this->echoFailedAuthenticationResponse();
    }
    
    function addGeneralExpenseToCompany(&$args) {
        $fixedArgs = $this->fixArguments($args);
        if ($this->verifyAdmin($fixedArgs->user_id, $fixedArgs->session_id, $fixedArgs->type) === true) {
            $values = "";
            foreach($fixedArgs->expenses as $expense) {
                #var_dump($expense["expense_id"]);
                $values .= "( {$expense->company_id}, {$expense->expense_id}, {$expense->amount}, '{$expense->date}', '{$expense->comment}'),";
            }
            $values = substr($values, 0, strlen($values) -1);
            $query  = " INSERT INTO admin.general_expense (company_id, expense_id, amount, date, comment) VALUES $values ON CONFLICT (company_id, expense_id, date ) DO UPDATE SET amount = EXCLUDED.amount, comment = EXCLUDED.comment";
            $result = $this->pgConn->fetchRawQueryResult($query);
            $response = "";
            if (pg_affected_rows($result) > 0)
                $response =  '{"status":"success", "message":"expenses added/updated!"}';
            else
                $response =  '{"status":"error", "message":"Failed to add expenses. "}';
            
            $this->echoResponse($response);
        }
        else 
            $this->echoFailedAuthenticationResponse();
    }
    
    function addPaymentToProject(&$args) {
    $fixedArgs = $this->fixArguments($args);
        if ($this->verifyAdmin($fixedArgs->user_id, $fixedArgs->session_id, $fixedArgs->type) === true) {
            $values = "";           
            
            foreach ($fixedArgs->payments as $payment) {
                $tmpValues = "( '{$fixedArgs->case_number}', '{$payment["description"]}', {$payment["amount"]}, {$payment["vat"]}, '{$payment["date"]}'),";
                $values .= $tmpValues;
            }
            $values = substr($values, 0, strlen($values)-1);
            
            $query = "INSERT INTO admin.project_income (case_number, description, amount, vat, date) VALUES $values";
            $result = $this->pgConn->fetchRawQueryResult($query);

            $response = "";
            if (pg_affected_rows($result) > 0)
                $response =  '{"status":"success", "message":"payments added!"}';
            else
                $response =  '{"status":"error", "message":"Failed to add payments. Contact System Administrator"}';
            
            $this->echoResponse($response);
            
        }
        else 
            $this->echoFailedAuthenticationResponse();
    }
    
    function addProject(&$args) {
        $fixedArgs = $this->fixArguments($args);
        if ($this->verifyAdmin($fixedArgs->user_id, $fixedArgs->session_id, $fixedArgs->type) === true) {
            $query = "INSERT INTO admin.project(case_number, acronym, full_project_name, company_id, budget, authority, contract_number, start_date, end_date, guarantee, bank, vat ) VALUES ('{$fixedArgs->case_number}', '{$fixedArgs->acronym}', '{$fixedArgs->full_project_name}', {$fixedArgs->company_id}, {$fixedArgs->budget}, '{$fixedArgs->authority}', '{$fixedArgs->contract_number}', '{$fixedArgs->start_date}', '{$fixedArgs->end_date}', '{$fixedArgs->guarantee}',  '{$fixedArgs->bank}', {$fixedArgs->vat}) ON CONFLICT (case_number,company_id) DO UPDATE SET  acronym=EXCLUDED.acronym, full_project_name=EXCLUDED.full_project_name, budget=EXCLUDED.budget, authority=EXCLUDED.authority, contract_number=EXCLUDED.contract_number, start_date=EXCLUDED.start_date, end_date=EXCLUDED.end_date, guarantee=EXCLUDED.guarantee, bank=EXCLUDED.bank, vat = EXCLUDED.vat";
            #print($query);
            $result = $this->pgConn->fetchRawQueryResult($query);
            $response = "";
            if (pg_affected_rows($result) == 1) 
                $response =  '{"status":"success", "message":"project added/modified"}';
            else 
                $response =  '{"status":"error", "message":"Failed to add project. Case number for this company already exists!"}';
            
            $this->echoResponse($response);
        }
        else 
            $this->echoFailedAuthenticationResponse();
    
    }
    
    function checkStartEmployeeWork(&$args) {
          $fixedArgs = $this->fixArguments($args);
          if ($this->verifyEmployee($fixedArgs->user_id, $fixedArgs->session_id, $fixedArgs->type) === true) {
            $query = "SELECT id, start_date  FROM employees.work_day WHERE user_id = {$fixedArgs->user_id} AND EXTRACT (DAY FROM start_date) = EXTRACT (DAY FROM current_timestamp)
                                AND EXTRACT (MONTH FROM start_date) = EXTRACT (MONTH FROM current_timestamp)
                                AND EXTRACT (YEAR FROM start_date) = EXTRACT (YEAR FROM current_timestamp) ORDER BY id DESC LIMIT 1; ";
           
            $result = $this->pgConn->fetchRawQueryResult($query);
            $response = "";
            if (pg_affected_rows($result) > 0) {
                $row = pg_fetch_assoc($result); 
                if ($row["id"] == null)
                    $row["response"] = "null";
                   
                $response =  '{"status":"success", "data":' .    '{"id": ' . $row["id"]. ', "start_date": "' . $row["start_date"] .  '"  }' .'}';
                #print($response);
            }
            else 
                $response =  '{"status":"error", "message":"Employee has not started working!"}';
            $this->echoResponse($response);
          }
          else
            $this->echoFailedAuthenticationResponse();
    }
    
    function assignEmployeeToProject(&$args) {
        $fixedArgs = $this->fixArguments($args);
         if ($this->verifyAdmin($fixedArgs->user_id, $fixedArgs->session_id, $fixedArgs->type) === true) {
            $query = "INSERT INTO admin.project_user VALUES ('{$fixedArgs->project_id}', {$fixedArgs->employee_id}) ON CONFLICT(project_id, user_id) DO NOTHING;";
            $result = $this->pgConn->fetchRawQueryResult($query);
            $response = "";
            if (pg_affected_rows($result) == 1) 
                $response =  '{"status":"success", "message":"User assigned to project!"}';
            else 
                $response =  '{"status":"error", "message":"Could not assign user to this project. Check if the project exists in the database or if the user is already assigned to the project"}';
            $this->echoResponse($response);
         }
         else 
            $this->echoFailedAuthenticationResponse();
    }
    
    function deleteProject(&$args) {
        $fixedArgs = $this->fixArguments($args);
        if ($this->verifyAdmin($fixedArgs->user_id, $fixedArgs->session_id, $fixedArgs->type) === true) {
            $query = "DELETE FROM admin.project WHERE case_number = '{$fixedArgs->case_number}' AND company_id = {$fixedArgs->company_id}";
            $result = $this->pgConn->fetchRawQueryResult($query);
            $response = "";
            if (pg_affected_rows($result) == 1)
                $response =  '{"status":"success", "message":"project deleted!"}';
            else
                $response =  '{"status":"error", "message":"Failed to delete project."}';
            
            $this->echoResponse($response);        
        }
        else 
            $this->echoFailedAuthenticationResponse();
    }
    
    function deleteCompany(&$args) {
        $fixedArgs = $this->fixArguments($args);
        if ($this->verifyAdmin($fixedArgs->user_id, $fixedArgs->session_id, $fixedArgs->type) === true) {
            $query = "DELETE FROM admin.company WHERE vat = '{$fixedArgs->vat}'";
            $result = $this->pgConn->fetchRawQueryResult($query);
            $response = "";
            if (pg_affected_rows($result) == 1)
                $response =  '{"status":"success", "message":"company deleted!"}';
            else 
                $response =  '{"status":"error", "message":"Failed to delete company"}';
                
            $this->echoResponse($response);
        }
        else 
            $this->echoFailedAuthenticationResponse();
    }
    
    function endEmployeeWork(&$args) {
        $fixedArgs = $this->fixArguments($args);
        if ($this->verifyEmployee($fixedArgs->user_id, $fixedArgs->session_id, $fixedArgs->type) === true) {
            $query = "UPDATE employees.work_day SET end_date = '{$fixedArgs->end_date}' WHERE id = {$fixedArgs->work_day_id}";
            
            $result = $this->pgConn->fetchRawQueryResult($query);
            $response = "";
            if (pg_affected_rows($result) == 1) {
                $values = "";
                foreach($fixedArgs->work_projects as $workProject) {
                    $workProject = (object)$workProject;
                    $workProject->description = str_replace( "'", " ", $workProject->description);
                    $values .= " ({$fixedArgs->work_day_id}, '{$workProject->project_id}', {$workProject->time}, '{$workProject->description}'),";
                }
                $values = substr($values, 0, strlen($values)-1);
                $query = "INSERT INTO employees.work_day_project (work_day_id, project_id, time, description) VALUES $values ON CONFLICT (work_day_id, project_id) DO UPDATE SET time = EXCLUDED.time, description = EXCLUDED.description";
                $result2 = $this->pgConn->fetchRawQueryResult($query);
                
                if (pg_affected_rows($result2) > 0) 
                    $response =  '{"status":"success", "message": "goodbye!"}';                
                else 
                    $response =  '{"status":"error", "message": "Failed to save project info. Please verify that project info is provided only once or contact system administrator!"}';           
            }
            else 
                $response =  '{"status":"error", "message":"Unable to stop working hours! Please contact system administrator"}';
            $this->echoResponse($response);
        
          }
          else
            $this->echoFailedAuthenticationResponse();
    }
        
    function getAvailableCompanies(&$args) {
        $fixedArgs = $this->fixArguments($args);
        if ($this->verifyAdmin($fixedArgs->user_id, $fixedArgs->session_id, $fixedArgs->type) === true) {
            $query = "SELECT ARRAY_TO_JSON(ARRAY_AGG(ROW_TO_JSON(a.*))) response FROM admin.company a WHERE id > 0";
            $result = $this->pgConn->fetchRawQueryResult($query);            
            $response = "";
            if (pg_affected_rows($result) == 1) {
                $row = pg_fetch_assoc($result); 
                $response =  '{"status":"success", "data":' .$row["response"] .'}';
            }
            else 
                $response =  '{"status":"error", "message":"No companies retrieved"}';
                
            $this->echoResponse($response);
        }
        else 
            $this->echoFailedAuthenticationResponse();
    }
    
    function getAvailableEmployees(&$args) {
        $fixedArgs = $this->fixArguments($args);
        if ($this->verifyAdmin($fixedArgs->user_id, $fixedArgs->session_id, $fixedArgs->type) === true) {
            $query = "SELECT array_to_json((array_agg(row_to_json(a.*) order by name))) response FROM customers_auth a  where type = 'l1'";
            $result = $this->pgConn->fetchRawQueryResult($query);
            $response = "";
            if (pg_affected_rows($result) == 1) {
                $row = pg_fetch_assoc($result); 
                $response =  '{"status":"success", "data":' .$row["response"] .'}';
            }
            else 
                $response =  '{"status":"error", "message":"Unable to fetch available employees."}';
           $this->echoResponse($response);
        }
        else
            $this->echoFailedAuthenticationResponse();
    }
    
    function getAvailableOverheadExpenses(&$args) {
        $fixedArgs = $this->fixArguments($args);
        if ($this->verifyAdmin($fixedArgs->user_id, $fixedArgs->session_id, $fixedArgs->type) === true) {
            $query = "SELECT ARRAY_TO_JSON(ARRAY_AGG(ROW_TO_JSON(a.*) ORDER BY name )) response FROM admin.expense a WHERE is_overhead = true";
            $result = $this->pgConn->fetchRawQueryResult($query);
            $response = "";
            if (pg_affected_rows($result) == 1) {
                $row = pg_fetch_assoc($result); 
                if ($row["response"] ==null)
                    $row["response"] = "null";
                $response =  '{"status":"success", "data":' .$row["response"] .'}';
            }
            else 
                $response =  '{"status":"error", "message":"Unable to fetch expenses."}';
           $this->echoResponse($response);
        }
        else
            $this->echoFailedAuthenticationResponse();
    }
    
    function getAvailableProjectExpenses(&$args) {
        $fixedArgs = $this->fixArguments($args);
        if ($this->verifyAdmin($fixedArgs->user_id, $fixedArgs->session_id, $fixedArgs->type) === true) {
            $query = "SELECT ARRAY_TO_JSON(ARRAY_AGG(ROW_TO_JSON(a.*) ORDER BY name)) response FROM admin.expense a WHERE is_overhead = false";
            $result = $this->pgConn->fetchRawQueryResult($query);
            $response = "";
            if (pg_affected_rows($result) == 1) {
                $row = pg_fetch_assoc($result); 
                $response =  '{"status":"success", "data":' .$row["response"] .'}';
            }
            else 
                $response =  '{"status":"error", "message":"Unable to fetch expenses."}';
           $this->echoResponse($response);
        }
        else
            $this->echoFailedAuthenticationResponse();
    }
    
    function getAvailableProjects(&$args) {
        $fixedArgs = $this->fixArguments($args);
        if ($this->verifyAdmin($fixedArgs->user_id, $fixedArgs->session_id, $fixedArgs->type) === true) {
        
            $query =    "SELECT ARRAY_TO_JSON(ARRAY_AGG(ROW_TO_JSON(a.*) ORDER BY case_number)) response FROM (
                    SELECT b.name company_name, c.* 
                    FROM admin.company b 
                    JOIN admin.project c ON b.id = c.company_id 
           )a";

           $result = $this->pgConn->fetchRawQueryResult($query);
            
           $response = "";
           if (pg_affected_rows($result) == 1) {
                $row = pg_fetch_assoc($result); 
                if ($row["response"] ==null)
                    $row["response"] = "null";
                $response =  '{"status":"success", "data":' .$row["response"] .'}';
            }
            else 
                $response =  '{"status":"error", "message":"No projects retrieved"}';
                
            $this->echoResponse($response);
        }
        else 
            $this->echoFailedAuthenticationResponse();
    }
    
    function getAvailableCompanyProjects(&$args) {
        $fixedArgs = $this->fixArguments($args);
        if ($this->verifyAdmin($fixedArgs->user_id, $fixedArgs->session_id, $fixedArgs->type) === true) {
        
            $query = "SELECT ARRAY_TO_JSON(ARRAY_AGG(ROW_TO_JSON(a.*))) response FROM(
                SELECT company_name, ARRAY_TO_JSON(ARRAY_AGG(ROW_TO_JSON(a.*) ORDER BY case_number)) projects FROM (
                    SELECT b.name company_name, c.* FROM
                    customers_auth a 
                    JOIN admin.company b ON a.uid = b.user_id AND a.uid = {$fixedArgs->user_id} AND a.company_id = {$fixedArgs->company_id}
                    JOIN admin.project c ON b.id = c.company_id
                ) a
                GROUP BY company_name 
           )a";

           $result = $this->pgConn->fetchRawQueryResult($query);
            
           $response = "";
           if (pg_affected_rows($result) == 1) {
                $row = pg_fetch_assoc($result); 
                if ($row["response"] ==null)
                    $row["response"] = "null";
                $response =  '{"status":"success", "data":' .$row["response"] .'}';
            }
            else 
                $response =  '{"status":"error", "message":"No projects retrieved"}';
                
            $this->echoResponse($response);
        }
        else 
            $this->echoFailedAuthenticationResponse();
    }
    
    function getAvailableUsers(&$args) {
        $fixedArgs = $this->fixArguments($args);
        if ($this->verifyAdmin($fixedArgs->user_id, $fixedArgs->session_id, $fixedArgs->type) === true) {
            $query = "SELECT ARRAY_TO_JSON(ARRAY_AGG(ROW_TO_JSON(a.*) ORDER BY type, name)) response FROM(SELECT uid, name, type, email, phone, address, city, created FROM customers_auth) a;";
            $result = $this->pgConn->fetchRawQueryResult($query);
            
            $response = "";
            if (pg_affected_rows($result) == 1) {
                $row = pg_fetch_assoc($result); 
                if ($row["response"] ==null)
                    $row["response"] = "null";
                $response =  '{"status":"success", "data":' .$row["response"] .'}';
            }
            else 
                $response =  '{"status":"error", "message":"No users retrieved"}';
                
            $this->echoResponse($response);
        }
        else 
            $this->echoFailedAuthenticationResponse();
    }
        
    function getEmployeeAvailableProjects(&$args) {
          $fixedArgs = $this->fixArguments($args);
          if ($this->verifyEmployee($fixedArgs->user_id, $fixedArgs->session_id, $fixedArgs->type) === true) {
            $query = "WITH tmp1 AS( SELECT b.acronym, b.full_project_name, b.case_number 
                                FROM admin.project_user a
                                JOIN admin.project b ON a.project_id = b.case_number AND a.user_id = {$fixedArgs->user_id}
                            )SELECT ARRAY_TO_JSON(ARRAY_AGG(ROW_TO_JSON(tmp1.*)ORDER BY acronym)) response FROM tmp1; ";
           
            $result = $this->pgConn->fetchRawQueryResult($query);
            $response = "";
            if (pg_affected_rows($result) == 1) {
                $row = pg_fetch_assoc($result); 
                if ($row["response"] ==null)
                    $row["response"] = "null";
                $response =  '{"status":"success", "data":' .$row["response"] .'}';
            }
            else 
                $response =  '{"status":"error", "message":"Employee is not associated with any projects"}';
            $this->echoResponse($response);
          }
          else
            $this->echoFailedAuthenticationResponse();
    }
    
    function getEmployeeAvailableProjectsByAdmin(&$args) {
          $fixedArgs = $this->fixArguments($args);
          if ($this->verifyAdmin($fixedArgs->user_id, $fixedArgs->session_id, $fixedArgs->type) === true) {
            $query = "WITH tmp1 AS( SELECT b.acronym, b.full_project_name, b.case_number 
                                FROM admin.project_user a
                                JOIN admin.project b ON a.project_id = b.case_number AND a.user_id = {$fixedArgs->request_user_id}
                            )SELECT ARRAY_TO_JSON(ARRAY_AGG(ROW_TO_JSON(tmp1.*)ORDER BY acronym)) response FROM tmp1; ";
           
            $result = $this->pgConn->fetchRawQueryResult($query);
            $response = "";
            if (pg_affected_rows($result) == 1) {
                $row = pg_fetch_assoc($result); 
                if ($row["response"] ==null)
                    $row["response"] = "null";
                $response =  '{"status":"success", "data":' .$row["response"] .'}';
            }
            else 
                $response =  '{"status":"error", "message":"Employee is not associated with any projects"}';
            $this->echoResponse($response);
          }
          else
            $this->echoFailedAuthenticationResponse();
    }
    
    function getAllEmployeesProjects(&$args) {
        $fixedArgs = $this->fixArguments($args);
        if ($this->verifyAdmin($fixedArgs->user_id, $fixedArgs->session_id, $fixedArgs->type) === true) {
            $query = "SELECT ARRAY_TO_JSON(ARRAY_AGG(proj)) response FROM (
            SELECT json_build_object('user_id', user_id, 'projects', ARRAY_TO_JSON(ARRAY_AGG(project_id)) ) proj FROM admin.project_user
            GROUP BY user_id
            )a";
           
            $result = $this->pgConn->fetchRawQueryResult($query);
            $response = "";
            if (pg_affected_rows($result) == 1) {
                $row = pg_fetch_assoc($result); 
                if ($row["response"] ==null)
                    $row["response"] = "null";
                $response =  '{"status":"success", "data":' .$row["response"] .'}';
            }
            else 
                $response =  '{"status":"error", "message":"Employee is not associated with any projects"}';
            $this->echoResponse($response);
          }
          else
            $this->echoFailedAuthenticationResponse();
    }
    
    function getCompanyOverheads(&$args) {
        $fixedArgs = $this->fixArguments($args);
        if ($this->verifyAdmin($fixedArgs->user_id, $fixedArgs->session_id, $fixedArgs->type) === true) {
            $query = "with tmp1 AS(
                                    SELECT a.expense_id, b.name, a.date, a.amount, a.comment 
                                    FROM admin.general_expense a
                                    JOIN admin.expense b ON a.expense_id = b.id AND a.company_id = {$fixedArgs->company_id}
                                    JOIN admin.company c ON a.company_id = c.id AND c.user_id = {$fixedArgs->user_id}
                            )SELECT ARRAY_TO_JSON(ARRAY_AGG(ROW_TO_JSON(tmp1.*) ORDER BY name)) response FROM tmp1";
            $result = $this->pgConn->fetchRawQueryResult($query);
            
            $response = "";
            if (pg_affected_rows($result) == 1) {
                $row = pg_fetch_assoc($result); 
                if ($row["response"] ==null)
                    $row["response"] = "null";
                $response =  '{"status":"success", "data":' .$row["response"] .'}';
            }
            else 
                $response =  '{"status":"error", "message":"No company expenses retrieved"}';
                
            $this->echoResponse($response);
        }
        else 
            $this->echoFailedAuthenticationResponse();
    }
    
    function getProjectExpenses(&$args) {
        $fixedArgs = $this->fixArguments($args);
        if ($this->verifyAdmin($fixedArgs->user_id, $fixedArgs->session_id, $fixedArgs->type) === true) {
            $query = "SELECT expense FROM admin.project a JOIN admin.company b ON a.company_id = b.id AND b.user_id = {$fixedArgs->user_id} AND a.case_number = '{$fixedArgs->case_number}'";
             $result = $this->pgConn->fetchRawQueryResult($query);
            
            $response = "";
            if (pg_affected_rows($result) == 1) {
                $row = pg_fetch_assoc($result); 
                if ($row["expense"] ==null)
                    $row["expense"] = "null";
                $response =  '{"status":"success", "data":' .$row["expense"] .'}';
            }
            $this->echoResponse($response);
        }
        else 
            $this->echoFailedAuthenticationResponse();
    }
    
    function getProjectInfo(&$args) {
        $fixedArgs = $this->fixArguments($args);
        if ($this->verifyAdmin($fixedArgs->user_id, $fixedArgs->session_id, $fixedArgs->type) === true) {
            $query = "WITH user_info AS(
	SELECT a.case_number, c.uid, c.name--, ROUND(SUM(EXTRACT(EPOCH FROM e.end_date - e.start_date)/3600)::numeric,1) work_hours
    FROM admin.project a
    JOIN admin.project_user b ON a.case_number = b.project_id AND a.case_number = '{$fixedArgs->case_number}'
    JOIN customers_auth c ON b.user_id = c.uid
    LEFT JOIN employees.work_day_project d ON a.case_number = d.project_id
    --LEFT JOIN employees.work_day e ON c.uid = e.user_id AND e.start_date BETWEEN '{$fixedArgs->start_date}' AND '{$fixedArgs->end_date}'				
    --WHERE e.id IS NOT NULL AND e.end_date IS NOT NULL
    GROUP BY a.case_number, c.uid, c.name
),personnel_expense_detail AS(
	SELECT user_id, sum(amount) salary, sum(vat) vat
	FROM admin.project_personnel_expense WHERE case_number = '{$fixedArgs->case_number}' AND date_created BETWEEN '{$fixedArgs->start_date}' AND '{$fixedArgs->end_date}' 
	GROUP BY user_id			
),personel_expense AS(
	SELECT sum(salary) amount, sum(vat) vat  FROM personnel_expense_detail
),total_user_info AS(
	SELECT ARRAY_TO_JSON(ARRAY_AGG(
		json_build_object ('name', b.name, /*'total_work_hours', c.work_hours,*/ 'salary', d.salary, 'vat', d.vat ) ORDER BY b.name)) user_info
	FROM admin.project_user a
	JOIN customers_auth b ON a.user_id = b.uid AND a.project_id = '{$fixedArgs->case_number}'
	LEFT JOIN user_info c ON a.user_id = c.uid
	LEFT JOIN personnel_expense_detail d ON a.user_id = d.user_id
),total_current_project_budget AS(
	SELECT sum(budget) total_available_budget FROM admin.project WHERE GREATEST(start_date, '{$fixedArgs->start_date}') < LEAST(end_date, '{$fixedArgs->end_date}')
),overhead_ratio AS(
	SELECT budget/total_available_budget overhead_ratio
	FROM admin.project a
	JOIN total_current_project_budget b ON  a.case_number = '{$fixedArgs->case_number}'
),project_overhead AS(
	SELECT round(sum(amount*overhead_ratio)::numeric,2) amount, round(sum(vat*overhead_ratio)::numeric, 2) vat
	FROM admin.project_expense a 
	JOIN overhead_ratio ON \"date\" BETWEEN '{$fixedArgs->start_date}' AND '{$fixedArgs->end_date}' AND case_number IN (SELECT case_number FROM admin.overhead)
),expense_detail AS(
	SELECT * FROM admin.project_expense WHERE case_number = '{$fixedArgs->case_number}' AND \"date\" BETWEEN '{$fixedArgs->start_date}' AND '{$fixedArgs->end_date}'		
),expense_detail_json AS(
	SELECT ARRAY_TO_JSON(ARRAY_AGG( ROW_TO_JSON(a.*) ORDER BY date)) expense_detail FROM expense_detail a
),expense AS(
	SELECT sum (amount) amount, sum(vat) vat
	FROM expense_detail
),income_detail AS(
	SELECT * FROM admin.project_income WHERE case_number = '{$fixedArgs->case_number}' AND date < '{$fixedArgs->end_date}'
),income_detail_json AS(
	SELECT ARRAY_TO_JSON(ARRAY_AGG( ROW_TO_JSON(a.*) ORDER BY date)) income_detail FROM income_detail a
),income AS(
	SELECT sum(amount) amount, sum(vat) vat FROM income_detail
)
SELECT json_build_object('personel', json_build_object('amount', g.amount, 'vat', g.vat),
						 'expense', json_build_object('amount', b.amount, 'vat', b.vat ), 
						 'overhead', json_build_object('amount', c.amount, 'vat', c.vat), 
						 'income', json_build_object('amount', d.amount, 'income', d.vat),
						 'personnel_detail', user_info, 
						 'expense_detail', e.expense_detail,
						 'income_detail', f.income_detail) response
FROM total_user_info a
FULL JOIN expense b ON true
FULL JOIN project_overhead c ON true
FULL JOIN income d ON true
FULL JOIN expense_detail_json e ON true
FULL JOIN income_detail_json f ON true
FULL JOIN personel_expense g ON true;";
#print $query;
             $result = $this->pgConn->fetchRawQueryResult($query);
            $response = "";
            if (pg_affected_rows($result) == 1) {
                $row = pg_fetch_assoc($result); 
                if ($row["response"] ==null)
                    $row["response"] = "null";
                $response =  '{"status":"success", "data":' .$row["response"] .'}';
            }
            $this->echoResponse($response);
        }
        else 
            $this->echoFailedAuthenticationResponse();
    }
    
    function removeEmployeeFromProject(&$args) {
        $fixedArgs = $this->fixArguments($args);
        if ($this->verifyAdmin($fixedArgs->user_id, $fixedArgs->session_id, $fixedArgs->type) === true) {
            $query = "DELETE FROM admin.project_user WHERE project_id = '{$fixedArgs->project_id}' AND user_id = {$fixedArgs->employee_id};";
            $result = $this->pgConn->fetchRawQueryResult($query);
            $response = "";
            if (pg_affected_rows($result) == 1) 
                $response =  '{"status":"success", "message":"User removed from project!"}';
            else 
                $response =  '{"status":"error", "message":"Could not remove user from the specified project."}';
            $this->echoResponse($response);
         }
         else 
            $this->echoFailedAuthenticationResponse();
    }
    
    function setUserAccessLevel(&$args) {
        $fixedArgs = $this->fixArguments($args);
        if ($this->verifyAdmin($fixedArgs->user_id, $fixedArgs->session_id, $fixedArgs->type) === true) {
            $query = "UPDATE customers_auth SET type = '{$fixedArgs->new_access_type}' WHERE uid = {$fixedArgs->employee_id}";
            $result = $this->pgConn->fetchRawQueryResult($query);
            $response = "";
            if (pg_affected_rows($result) == 1) 
                $response =  '{"status":"success", "message":"User access level updated!"}';
            else 
                $response =  '{"status":"error", "message":"User access level unaffected."}';
            $this->echoResponse($response);
        }
        else 
            $this->echoFailedAuthenticationResponse();
    }
    
    function startEmployeeWork(&$args) {
        $fixedArgs = $this->fixArguments($args);
         if ($this->verifyEmployee($fixedArgs->user_id, $fixedArgs->session_id, $fixedArgs->type) === true) {
            $query = "INSERT INTO employees.work_day (user_id, start_date) VALUES ( {$fixedArgs->user_id}, current_timestamp) RETURNING id;";
            $result = $this->pgConn->fetchRawQueryResult($query);
            $response = "";
            if (pg_affected_rows($result) == 1) {
                $row = pg_fetch_assoc($result); 
                $response =  "{\"status\":\"success\", \"message\":  \"have a good day!\", \"work_day_id\": {$row["id"]}}";
            }
            else 
                $response =  '{"status":"error", "message":"Employee is not associated with any projects"}';
            $this->echoResponse($response);
          }
          else
            $this->echoFailedAuthenticationResponse();
    }
    
    
    function service(&$args) {
        if ($args->action == "addcompany" )
            $this->addCompany($args);
        else if ($args->action == "deletecompany" )
            $this->deleteCompany($args);
        else if ($args->action == "getcompanies" ) 
            $this->getAvailableCompanies($args);            
         else if ($args->action == "addproject" )
            $this->addProject($args);
         else if ($args->action == "deleteproject" )
            $this->deleteProject($args);
        else if ($args->action == "getprojects" )
            $this->getAvailableProjects($args);
        else if ($args->action == "assignemployeetoproject" )
            $this->assignEmployeeToProject($args);
        else if ($args->action == "setuseraccesslevel" )
            $this->setUserAccessLevel($args);
        else if ($args->action == "removeemployeefromproject" )
            $this->removeEmployeeFromProject($args);
        else if ($args->action == "getavailableemployees" )
            $this->getAvailableEmployees($args);
        else if ($args->action =="geteoverheads")
             $this->getAvailableOverheadExpenses($args);
        else if ($args->action == "addgeneralcompanyexpenses")
             $this->addGeneralExpenseToCompany($args);
        else if ($args->action == "getcompanyoverheads")
             $this->getCompanyOverheads($args);    
        else if ($args->action == "getperprojectexpenses")
             $this->getProjectExpenses($args);
        else if ($args->action == "getemployeeprojects")
             $this->getEmployeeAvailableProjects($args);  
        else if ($args->action == "getavailableusers")
             $this->getAvailableUsers($args);
        else if ($args->action == "startwork")
             $this->startEmployeeWork($args);  
        else if ($args->action == "checkstartemployeework")
             $this->checkStartEmployeeWork($args);  
        else if ($args->action == "endwork")
             $this->endEmployeeWork($args);  
        else if ($args->action == "getprojectinfo") 
             $this->getProjectInfo($args); 
        else if ($args->action == "addpersonnelexpensetoproject")
            $this->addEmployeeExpenseToProject($args);
        else if ($args->action == "addexpensetoproject")
            $this->addExpenseToProject($args);
        else if ($args->action == "getemployeeprojectsbyadmin")
            $this->getEmployeeAvailableProjectsByAdmin($args);
        else if ($args->action == "getallemployeesprojects")
            $this->getAllEmployeesProjects($args);
        
    }
}


#$config = "config.json";
#$obj = new TimecardBackend($config);


#$addCompanyArgs = (object) [ "action" =>"addcompany",  "user_id" => 3, "session_id" => 'vrtmsup2ea3e1ui36f5dftt4sg', "type" => "admin", "company_name"=> "EPSILON Malta Limited", "vat" => "11111111", "address" => "paparies"];
#$obj->addCompany($addCompanyArgs);

#$deleteCompanyArgs = (object) ["action" =>"deletecompany", "user_id" => 3, "session_id" => 'vrtmsup2ea3e1ui36f5dftt4sg', "type" => "admin", "vat" => "11111111"];
#$obj->deleteCompany($deleteCompanyArgs);

#$getCompanyArgs = (object) ["action" =>"getcompanies", "user_id" => 3, "session_id" => 'vrtmsup2ea3e1ui36f5dftt4sg', "type" => "admin"];
#$obj->getAvailableCompanies($getCompanyArgs);


#$addProjectArgs = (object)["action" => "addproject", "user_id" => 3, "session_id" => 'vrtmsup2ea3e1ui36f5dftt4sg', "type" => "admin", "case_number"=>"1604", "acronym"=> "AURORA", "full_project_name" => "AURORA kai ta muala sta mplenter",  "company_id" => 1, "budget" => 390000, "authority" => "European Comission", "contract_number" => "den xero", "start_date" => "2018-01-01 00:00:00", "end_date" => "2020-01-03 00:00:00", "guarantee" => 30000, "bank" => "kapoia", "balance" => 0, "vat" => 0 ];
#$obj->addProject($addProjectArgs);

#$addProjectArgs = (object)["action" => "addproject", "user_id" => 3, "session_id" => 'vrtmsup2ea3e1ui36f5dftt4sg', "type" => "admin", "case_number"=>"1704", "acronym"=> "SIM4NEXUS", "full_project_name" => "SIM SIM ",  "company_id" => 2, "budget" => 390000, "authority" => "European Comission", "contract_number" => "den xero", "start_date" => "2018-01-01 00:00:00", "end_date" => "2020-01-03 00:00:00", "guarantee" => 30000, "bank" => "kapoia", "vat" => 0 ];
#$obj->addProject($addProjectArgs);
#$addProjectArgs = '{"type":"admin","user_id":4,"session_id":"8a20cdddd997d2f47456cf448b79eff4","action":"addproject","payments_and_comments":{},"company_id":86,"case_number":1,"acronym":"RR","full_project_name":"Testing Project","budget":1111,"vat":15,"authority":"GR","contract_number":"123123","start_date":"2019-12-31","end_date":"2020-12-31"}';
#$addProjectArgs = json_decode($addProjectArgs);
#$obj->addProject($addProjectArgs);



#$deleteProjectArgs = (object) ["action" => "deleteproject", "user_id" => 3, "session_id" => 'vrtmsup2ea3e1ui36f5dftt4sg', "type" => "admin", "case_number"=>"1604", "company_id" => 1];
#$obj->deleteProject($deleteProjectArgs);

#$getProjectArgs = (object) ["action" => "getprojects", "user_id" => 3, "session_id" => 'vrtmsup2ea3e1ui36f5dftt4sg', "type" => "admin"];
#$obj->getAvailableProjects($getProjectArgs);

#$assignEmployeeToProjectArgs = (object) ["action" => "assignemployeetoproject", "user_id" => 3, "session_id" => 'vrtmsup2ea3e1ui36f5dftt4sg', "type" => "admin", "employee_id"=>5, "project_id"=>"1604"];
#$obj->assignEmployeeToProject($assignEmployeeToProjectArgs);

#$setUserAccessLevelArgs =  (object) ["action" => "setuseraccesslevel", "user_id" => 3, "session_id" => 'vrtmsup2ea3e1ui36f5dftt4sg', "type" => "admin", "employee_id"=>5, "new_access_type"=>"l1"];
#$obj->setUserAccessLevel($setUserAccessLevelArgs);

#$removeEmployeeFromProjectArgs = (object) ["action" => "removeemployeefromproject", "user_id" => 3, "session_id" => 'vrtmsup2ea3e1ui36f5dftt4sg', "type" => "admin", "employee_id"=>5, "project_id"=>"1604"];
#$obj->removeEmployeeFromProject($removeEmployeeFromProjectArgs);

#$getAvailableEmployeesArgs = (object) ["action" => "getavailableemployees", "user_id" => 3, "session_id" => 'vrtmsup2ea3e1ui36f5dftt4sg', "type" => "admin"];
#$obj->getAvailableEmployees($getAvailableEmployeesArgs);

#$getAvailableOverheadExpensesArgs = (object) ["action" => "geteoverheads", "user_id" => 3, "session_id" => 'vrtmsup2ea3e1ui36f5dftt4sg', "type" => "admin"];
#$obj->getAvailableOverheadExpenses($getAvailableOverheadExpensesArgs);

#$getAvailableProjectExpensesArgs = (object) ["action" => "getprojectexpenses", "user_id" => 3, "session_id" => 'vrtmsup2ea3e1ui36f5dftt4sg', "type" => "admin"];
#$obj->getAvailableProjectExpenses($getAvailableProjectExpensesArgs);

#$addExpenseToProjectArgs = (object) ["action" => "addexpensetoproject", "user_id" => 3, "session_id" => 'vrtmsup2ea3e1ui36f5dftt4sg', "type" => "admin", "case_number" => "1337", "expenses" => [["amount" => 1000, "vat"=>240, "date" =>  "2018-01-03 00:00:00", "description" => "Ταξίδι ΑΑ σε Ταϋλάνδη" ], [ "amount" => 200, "vat" => 100, "date" =>  "2018-01-05 00:00:00", "description" => "server gtpk" ]]];
#$obj->addExpenseToProject($addExpenseToProjectArgs);
/*
$addPersonnelExpenseToProjectArgs = (object) ["action" => "addpersonnelexpensetoproject", "user_id" => 3, "session_id" => 'vrtmsup2ea3e1ui36f5dftt4sg', "type" => "admin", "case_number" => "1604", "expenses" => [
["amount" => 2500, "vat"=>360, "start_date" =>  "2018-01-03 00:00:00","end_date" =>  "2018-01-05 00:00:00", "receipt_date" => "2018-01-07 00:00:00", "description" => "Πολύ σοβαρή δουλειά", "user_id" => 5 ], ["amount" => 3500, "vat"=>1360, "start_date" =>  "2018-01-03 00:00:00","end_date" =>  "2018-01-03 00:00:00", "receipt_date" => "2018-01-08 00:00:00", "description" => "Πολύ σοβαρή δουλειά", "user_id" => 5 ],  ["amount" => 25200, "vat"=>3620, "start_date" =>  "2018-01-03 00:00:00","end_date" =>  "2018-01-03 00:00:00", "receipt_date" => "2018-01-07 00:00:00", "description" => "Πολύ σοβαρή δουλειά", "user_id" => 6 ]]];
$addPersonnelExpenseToProjectArgs = '{"type":"admin","user_id":4,"session_id":"b84758e238fea4ec5e564ee70250968b","action":"addpersonnelexpensetoproject","company_name":"EPSILON Malta Limited","case_number":"1337","acronym":"LT","full_project_name":"Final Test Product","company_id":2,"budget":12,"authority":"GR","contract_number":"1123df","start_date":"2019-11-28T00:00:00","end_date":"2021-03-18T00:00:00","guarantee":1,"bank":"NBG","vat":1,"balance":"30000","expenses":[{"user_id":"4","start_date":"2010-10-26","end_date":"2018-11-29","receipt_date":"2019-03-21","amount":300,"vat":25,"description":"asd"}]}';

$addPersonnelExpenseToProjectArgs = json_decode($addPersonnelExpenseToProjectArgs);
$obj->addEmployeeExpenseToProject($addPersonnelExpenseToProjectArgs);
*/
#$addPaymentToProjectArgs = (object) ["action" => "addexpensetoproject", "user_id" => 3, "session_id" => 'vrtmsup2ea3e1ui36f5dftt4sg', "type" => "admin", "case_number" => "1604", "payments" => [["amount" => 100000, "vat"=>0, "date" =>  "2018-01-03 00:00:00", "description" => "Πληρωμή AURORA γιατί έτσι" ], ["amount" => 50000, "vat"=>0, "date" =>  "2018-01-07 00:00:00", "description" => "Πληρωμή AURORA 2 γιατί έτσι" ] ] ];
#$obj->addPaymentToProject($addPaymentToProjectArgs);


#$getCompanyOverheadsArgs = (object) ["action" => "getcompanyoverheads", "user_id" => 3, "session_id" => 'vrtmsup2ea3e1ui36f5dftt4sg', "type" => "admin", "company_id" => 79];
#$obj->getCompanyOverheads($getCompanyOverheadsArgs);

#$getProjectExpensesArgs = (object) ["action" => "getperprojectexpenses", "user_id" => 3, "session_id" => 'vrtmsup2ea3e1ui36f5dftt4sg', "type" => "admin", "case_number" => 1704];
#$obj->getProjectExpenses($getProjectExpensesArgs);

#$getEmployeeAvailableProjectsArgs = (object) ["action" => "getemployeeprojects", "user_id" => 5, "session_id" => 'mvco7djooe3jki08rsf4f0pisf', "type" => "l1"];
#$obj->getEmployeeAvailableProjects($getEmployeeAvailableProjectsArgs);

#$getAvailableUsersArgs = (object) ["action" => "getavailableusers", "user_id" => 3, "session_id" => 'vrtmsup2ea3e1ui36f5dftt4sg', "type" => "admin"];
#$obj->getAvailableUsers($getAvailableUsersArgs);

#$startEmployeeWorkArgs =  (object) ["action" => "startwork", "user_id" => 6, "session_id" => 'ipeh6341jqajmud65omfjdt9ql', "type" => "l1"];
#$obj->startEmployeeWork($startEmployeeWorkArgs);

#$checkStartEmployeeWorkArgs = (object) ["action" => "checkstartemployeework", "user_id" => 6, "session_id" => 'ipeh6341jqajmud65omfjdt9ql', "type" => "l1"];
#$obj->checkStartEmployeeWork($checkStartEmployeeWorkArgs);

#$endEmployeeWorkArgs =  (object) ["action" => "endwork", "user_id" => 5, "session_id" => 'mvco7djooe3jki08rsf4f0pisf', "type" => "l1", "end_date" =>  "2019-05-01 15:00:00", "work_day_id" => 9, "work_projects" => [  ["project_id" => "1604", "time" => "10800", "description" => "malakies"], ["project_id" => 1704, "time" => "7200", "description" => "alles malakies"] ]];
#$obj->endEmployeeWork($endEmployeeWorkArgs);

#$endEmployeeWorkArgs =  (object) ["action" => "endwork", "user_id" => 5, "session_id" => 'mvco7djooe3jki08rsf4f0pisf', "type" => "l1", "end_date" =>  "2019-05-01 15:00:00", "work_day_id" => 8, "work_projects" => [  ["project_id" => "1604", "time" => "10800", "description" => "malakies"], ["project_id" => 1704, "time" => "7200", "description" => "alles malakies"] ]];
#$obj->endEmployeeWork($endEmployeeWorkArgs);

#$getProjectInfoArgs = (object) ["action" => "getprojectinfo", "user_id" => 3, "session_id" => 'vrtmsup2ea3e1ui36f5dftt4sg', "type" => "admin",  "case_number" => "1337", "start_date" => '2018-01-01 00:00:00', "end_date" => '2020-01-01 00:00:00'];
#$obj->getProjectInfo($getProjectInfoArgs);
#print("\nhere\n");

/*
$getEmployeeAvailableProjectsByAdminArgs = (object) ["action" => "getemployeeprojectsbyadmin","user_id" => 3, "session_id" => 'vrtmsup2ea3e1ui36f5dftt4sg', "type" => "admin", "request_user_id" => 5];
$obj->getEmployeeAvailableProjectsByAdmin($getEmployeeAvailableProjectsByAdminArgs);
*/
/*
$getAllEmployeeAvailableProjectsArgs = (object) ["action" => "getallemployeesprojects","user_id" => 3, "session_id" => 'vrtmsup2ea3e1ui36f5dftt4sg', "type" => "admin"];
$obj->getAllEmployeesProjects($getAllEmployeeAvailableProjectsArgs);
*/
/*
echo json_encode($addCompanyArgs) ."\n";
echo json_encode($deleteCompanyArgs) ."\n";
echo json_encode($getCompanyArgs) ."\n";
echo json_encode($addProjectArgs) ."\n";
echo json_encode($deleteProjectArgs) ."\n";
echo json_encode($getProjectArgs) ."\n";
*/
?>
