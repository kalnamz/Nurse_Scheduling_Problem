 <?php

  // function to open a connection to the Database
    function openDatabase()
   {
      $DB_MAIN_HOST = "cse-cmpsc431";
      $DB_MAIN_NAME = "nxk420";
      $DB_MAIN_USER = "nxk420";
      $DB_MAIN_PASS = "923649594";
      if ($connection = mysql_connect($DB_MAIN_HOST, $DB_MAIN_USER, $DB_MAIN_PASS))
     {
          if (!mysql_select_db ($DB_MAIN_NAME))
         {
           $msg = 'MySQL error #' . mysql_errno() . ": " . mysql_error(); 
           echo "\n\n Error is: $msg\n"; 
            rollback();
        return false;
         }
          echo " Successfully connected to the Database.\n\n";
     }
      else 
     {
        $msg = 'MySQL error #' . mysql_errno() . ": " . mysql_error();  
        echo " Error is: $msg.\n"; 
     } 
   }


  //function to close the connection to the Database
   function closeDatabase()
   {
      mysql_close();
      echo "\n Successfully closed the connection to the Database.\n\n";
   }
  
    //function to start the transaction
	function begin(){
		mysql_query("START TRANSACTION");
	}

	//function to commit the transaction
	function commit(){
		mysql_query("COMMIT");
	}

	//function to rollback the transaction
	function rollback(){
		mysql_query("ROLLBACK TO SAVEPOINT A");
	}
 
	 //PHP fun to call the schedule fn
	  function schedule_main($ftpt , $pshift)
	  { 
	   
	    // query to schedule employees by the week
		$get_date_query = "select distinct _date mondate from needs where weekday(_date) =0";
		if(!($date_res = mysql_query($get_date_query)))
		{
			$msg = 'My SQL error #' .mysql_errno().":".mysql_error(); 
			echo "\n\n Error is: $msg\n"; 
			return false;
		}
		else {
			while ($row = mysql_fetch_array($date_res))
		{
			
				$mondate = $row["mondate"];		
				schedule_emp($ftpt , $pshift,$mondate); 
	    }
		}
	  }
	 
	  // PHP function to schedule employees
		function schedule_emp($ftpt , $pshift,$mondate)
	  {

		begin();

		if(!($savepoint_result = mysql_query("SAVEPOINT A")))
		{
			$msg = 'My SQL error #' .mysql_errno().":".mysql_error(); 
			echo "\n\n Error is: $msg\n"; 
			return false;
		}  
	 
		 //To check whether there is a need for the dept for the date/emp_type/shift
			$chk_needs = "select depid , emp_type_ref, need, _date, shiftid from needs where ";
			$chk_needs .= " need >0 and _date between '".$mondate."' and adddate('".$mondate."',interval 6 day) order by depid,_date, shiftid"; 
	 
			if(!($needs_result = mysql_query($chk_needs)))
			 {
			   $msg = 'My SQL error #' .mysql_errno().":".mysql_error(); 
			   echo "\n\n Error is: $msg\n";
			   rollback();
			   return false;
			 }
			 else if(mysql_num_rows($needs_result) == 0) // no rows are selected
			 {  
			   echo " Error : All departments have no requirement now.\n";
			   rollback();          
			   return false;
			 }   
		
		while ($row = mysql_fetch_array($needs_result))
		{
			
			$depid = $row["depid"];
			$emp_type_ref  = $row["emp_type_ref"];
			$schdate = $row["_date"];
			$shiftid  = $row["shiftid"];
			$need = $row["need"];
		  
		 
		 for( $n = 1; $n <= $need; $n++)
		 {
			 
	   // query to check if employee is of the emp_type and FT/PT values returned in the main query.
	   $query = "select empid from employees e where e.emp_type_ref = '" . $emp_type_ref . "'  and e.ftpt = '" . $ftpt . "'";
		  // query to check for preferred shift
	   if( $pshift == 1)
	   {
		  $query .= " and e.pref_shift = '" .$shiftid. "'";
	   }
		  // query to check for employee certifications
	   $query .= " and exists (select 1 from emp_cert ect where ect.empid = e.empid and ect.depid = '" .$depid. "' ) ";
	   // query to check for FT and PT total hours scheduled
	   if( $ftpt == 'FT')
	   {
		  $query .= " and not exists (select 1 from schedule s where _date between '".$mondate."' and adddate('".$mondate."',interval 6 day)";
		  $query .= " and s.empid = e.empid group by empid having count(1) >=5) ";
	   }
	   else{
		  $query .= " and not exists (select 1 from schedule s where _date between '".$mondate."' and adddate('".$mondate."',interval 6 day)";
		  $query .= " and s.empid = e.empid group by empid having count(1) >=3) ";
	   }
	   $query .= " and e.empid not in ( ";
	  //check if employee already scheduled for that shift 
	   $query .= " select empid from schedule s";
	   $query .= " where s._date = '" . $schdate . "'";
	   $query .= " and s.shiftid =  '" . $shiftid . "' ";
	   $query .= " union "; 
	  //check if employee is not scheduled for sat and sun together
	   $query .= " select empid from schedule s";
	   $query .= " where (weekday(s._date)+ weekday('" . $schdate . "')) = 11";
	   $query .= " and abs(DATEDIFF(s._date, '" . $schdate . "')) =1 ";   
	   $query .= " union "; 
	  //check if employee scheduled for two shifts in a row on the same day
	   $query .= " select empid from schedule s ";
	   $query .= " where s._date = '" . $schdate . "'";
	   $query .= " and exists (select 1 from shifts sh2 where sh2.shiftid = ";
	   $query .= "'" .$shiftid ."'";
	   $query .= " and (abs(sh2.shiftid-s.shiftid)) =1)";
	   $query .= " union ";   
	   //check if employee scheduled for prev shift that will continue till next shift. 
	   $query .= " select empid from schedule s1, shifts sh ";
	   $query .= " where s1.shiftid = sh.shiftid"; 
	   $query .= " and adddate(s1._date, interval 1 day) = ";
	   $query .= " '" . $schdate . "'"; 
	   $query .= " and exists (select 1 from shifts sh2 where sh2.shiftid = ";
	   $query .= " '" .$shiftid ."'";
	   $query .= " and HOUR(ADDTIME(STR_TO_DATE(sh.sfrom, '%h:%i %p') , SEC_TO_TIME(sh.slength*3600)))%24 = HOUR( STR_TO_DATE(sh2.sfrom, '%h:%i %p'))) ";
	   $query .= " union ";
	   //check if employee scheduled for prev shift that will continue till next shift.
	   $query .= " select empid from schedule s1, shifts sh ";
	   $query .= " where s1.shiftid = sh.shiftid"; 
	   $query .= " and subdate(s1._date, interval 1 day) = ";
	   $query .= " '" . $schdate . "'"; 
	   $query .= " and exists (select 1 from shifts sh2 where sh2.shiftid = ";
	   $query .= "'" .$shiftid ."'";
	   $query .= " and HOUR(ADDTIME(STR_TO_DATE(sh2.sfrom, '%h:%i %p') , SEC_TO_TIME(sh2.slength*3600)))%24 = HOUR( STR_TO_DATE(sh.sfrom, '%h:%i %p'))) ";
	   //check if employee is going to be scheduled on a day off
	   $query .= " union ";
	   $query .= " select empid from emp_days_off_requests ef ";
	   $query .= " where '" . $schdate . "'"; 
	   $query .= "  = ef.date_of_request  ";
	   $query .= " union ";
	   //check if employee has a day off and new shift carries over into the day off
	   $query .= " select empid from emp_days_off_requests ef "; 
	   $query .= " where adddate('" . $schdate . "', interval 1 day)"; 
	   $query .= "  =ef.date_of_request ";
	   $query .= " and exists (select 1 from shifts sh where sh.shiftid = ";
	   $query .= "'" .$shiftid ."'";
	   $query .= " and HOUR(ADDTIME(STR_TO_DATE(sh.sfrom, '%h:%i %p') , SEC_TO_TIME(sh.slength*3600))) >24 ) ) limit 1";
	 
	    
	   if(!($result = mysql_query($query)))
	   {
		  $msg = 'My SQL error #' .mysql_errno().":".mysql_error(); 
		  echo "\n\n Error is: $msg";
		  rollback();
		  return false;
	   }
	   else if(0 == mysql_num_rows($result))
	   {
		 echo "\n WARNING - NO $ftpt EMPLOYEES AVAILABLE FOR SCHEDULING for Dept ID -$depid for Shift ID - $shiftid on $schdate.\n";
		 // will give a warning for the program, that's all.
	   }
	   else
	   {

		//  there is an employee available for scheduling
		 $row = mysql_fetch_array($result);
		 $empid = $row["empid"]; 
		  
		   $insert_query = "INSERT into schedule ";
		   $insert_query .= "(_date, empid, depid,shiftid) values (";
		   $insert_query .= "'" . $schdate . "',";
		   $insert_query .= "'" . $empid . "',";
		   $insert_query .= "'" . $depid . "',";
		   $insert_query .= "'" . $shiftid . "'";
		   $insert_query .= ")";  
		  
		   if(!($ins_result = mysql_query($insert_query)))
		   {
			 printf("An error occurred while scheduling!\n");
			 $msg = 'My SQL error #' .mysql_errno().":".mysql_error(); 
			 echo "\n\n Error is: $msg\n";
			 rollback();
			 return false;

		   }
		 
		  //update the needs table and decrease the need for thedept/shift/emptype/date scheduled.
		  $update_query = "update needs set need = need -1 where _date =";
		  $update_query .= "'" . $schdate . "'";
		  $update_query .= " and depid =";
		  $update_query .= "'" .$depid ."'";
		  $update_query .= " and shiftid =";
		  $update_query .= "'" .$shiftid ."'";
		  $update_query .= " and emp_type_ref =";
		  $update_query .= "'" .$emp_type_ref ."'";

		 
		if(!($upd_result = mysql_query($update_query)))
		 {
			printf("An error occurred while updating the needs of the department!\n");
			$msg = 'My SQL error #' .mysql_errno().":".mysql_error(); 
			echo "\n\n Error is: $msg\n";
			rollback();
			return false;
			
		 }
	  
		 echo " Successfully scheduled employee  - $empid of employee type - $emp_type_ref on $schdate for the $depid department.\n\n";
		 commit();
		  }
		}}
		
		commit();
		 return true;
	  }
   
	openDatabase();
  
  
  //schedule employees 
   schedule_main('FT' , 1); 
   schedule_main('FT' , 0); 
   schedule_main('PT' , 1);  
   schedule_main('PT' , 0); 
   
 closeDatabase();
 ?> 
