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

  // function to close the connection to the Database
   function closeDatabase()
   {
      mysql_close();
      echo "\n Successfully closed the connection to the Database.\n\n";
   }
  
   
	  // function to calculate the happiness
	  function emp_happiness()
	  {   
		  
		  $query = "  select  round(cnta/(cnta+cntb),2) PH , happy.firstName, happy.lastName  from  ";
		  $query .= " (select count(*) cnta, e.empid , e.firstName, e.lastName from schedule s, employees e where e.empid = s.empid  and e.pref_shift = s.shiftid group by e.empid,e.firstName, e.lastName ) happy,";
		  $query .= " (select count(*) cntb, e.empid  from schedule s, employees e where e.empid = s.empid  and e.pref_shift <> s.shiftid group by e.empid,e.firstName, e.lastName ) unhappy ";
		  $query .= " where happy.empid = unhappy.empid";
		  $query .= "  union ";
		  $query .= "  select 0.5 c,  e.firstName, e.lastName  from employees e  where not exists (select 1 from schedule s where e.empid = s.empid) ";
			  
			
		   if(!($result = mysql_query($query)))
		 {
			$msg = 'My SQL error #' .mysql_errno().":".mysql_error(); 
			echo "\n\n Error is: $msg\n";
			return false;
			
		 }
		 
		  echo "\n           Report of Employee happiness rating [0-1] : \n\n";
		  echo "|    FIRST NAME        | LAST NAME   |   HAPPINESS RATING |\n";
		
		  while($row = mysql_fetch_array($result)){
		  $happy = $row["PH"];
		  $fn = $row["firstName"];
		  $ln = $row["lastName"];
		  
			echo "|    $fn        | $ln  |  $happy  | \n";	
 
		  
		}		
		  }
		 
	  
	  // Function to get total scheduling cost  
	  function get_sched_Costs()
	  {
		$report_query =  "   select  ";
		$report_query .=  "  round(sum(e.hourly_rate * ss.slength *(1+ 0.2 * ss.hour_after_midnight)),2)  cost ";
		$report_query .=  "  from schedule s, "; 
		$report_query .=  "  employees e, "; 
		$report_query .=  "  (select ss.slength, ss.shiftid, "; 
		$report_query .=  "  case when  HOUR(ADDTIME(STR_TO_DATE(ss.sfrom, '%h:%i %p') , SEC_TO_TIME(ss.slength*3600))) >24  ";
		$report_query .=  "  then  1 ";
		$report_query .=  "  else  0 end hour_after_midnight	 ";			 
		$report_query .=  "  from shifts ss) ss ";
		$report_query .=  "  where  e.empid = s.empid  "; 
		$report_query .=  "  and  ss.shiftid = s.shiftid "; 
	  
	  
		if (!($result = mysql_query($report_query)))
	   {
		  $msg = 'MySQL error #' . mysql_errno() . ": " . mysql_error();
		  echo $msg; 
	   }
	   else if (0 == mysql_num_rows($result))
	   {
		  printf("No data are available!\n");
		  return false;
	   }
	   else
	   {

		  echo "\nTotal Scheduling Cost : ";
		  while ($row = mysql_fetch_array($result))
		 {
			$cost = $row["cost"];  
			echo "|    $ $cost    |\n";
		 }
		}
	} 
	  
	   // Function to get get unused shifts for full-time and part-time employees
	  function get_unused_shifts()
	  {
		  
		//full time
		$report_query =  "    select sum(tot_shifts) Total_unused_shifts from  ";
		$report_query .=  "    ( select ( 10-count(*)) tot_shifts from schedule s1 ";
		$report_query .=  "  where s1.empid in";
		$report_query .=  "  (Select empid from employees where ftpt = 'FT')";
		$report_query .=  "   group by empid having count(*) <10) b"; 
		 
	 
	   if (!($result = mysql_query($report_query)))
	   {
		  $msg = 'MySQL error #' . mysql_errno() . ": " . mysql_error();
		  echo $msg; 
	   }
	   else if (0 == mysql_num_rows($result))
	   {
		  printf("No data are available!\n");
		  return false;
	   }
	   else
	   {

		  echo "\n \nTotal Unused shifts for Full - time employees : ";
		   while ($row = mysql_fetch_array($result))
		 { 
			$tus = $row["Total_unused_shifts"];  
			echo " $tus  \n";
		 }
		}
		 
		
		//part time
		$report_query =  "    select sum(tot_shifts) Total_unused_shifts from  ";
		$report_query .=  "    ( select ( 6-count(*)) tot_shifts from schedule s1 ";
		$report_query .=  "  where s1.empid in";
		$report_query .=  "  (Select empid from employees where ftpt = 'PT')";
		$report_query .=  "   group by empid having count(*) <6) b";  
	 
		   if (!($result = mysql_query($report_query)))
	   {
		  $msg = 'MySQL error #' . mysql_errno() . ": " . mysql_error();
		  echo $msg; 
	   }
	   else if (0 == mysql_num_rows($result))
	   {
		  printf("No data are available!\n");
		  return false;
	   }
	   else
	   {

		  echo "\n \nTotal Unused shifts for Part - time employees : ";
		   while ($row = mysql_fetch_array($result))
		 { 
			$tus = $row["Total_unused_shifts"];  
			echo " $tus  \n";
		 }
		}
	}


	
     // Function to get a report of unfilled vacancies
     function report_unfulfilled_vacancies()
	  {
		$report_query =   "   select d.department_name  , et.emptype , s.sfrom, need , _date  ";
		$report_query .=  "  from needs n,";
		$report_query .=  "  departments d,";
		$report_query .=  "  emp_type et, ";
		$report_query .=  "   shifts s "; 
		$report_query .=  "  where n.depid = d.depid ";
		$report_query .=  "  and n.shiftid = s.shiftid";
		$report_query .=  "  and et.emp_type_ref = n.emp_type_ref  ";
		$report_query .=  "   and need > 0";   
	 
	   if (!($result = mysql_query($report_query)))
	   {
		  $msg = 'MySQL error #' . mysql_errno() . ": " . mysql_error();
		  echo $msg; 
	   }
	   else if (0 == mysql_num_rows($result))
	   {
		  printf("No data are available!\n");
		  return false;
	   }
	   else
	   {

		  echo "\n           Report of Unfulfilled Needs : \n\n";
		  echo "|    DEPARTMENT        | DATE   |   SHIFT   |   EMPLOYEE TYPE  |  NEEDS TO BE FULFILLED   |\n";
		  while ($row = mysql_fetch_array($result))
		 {
			$dept = $row["department_name"];
			$emptype = $row["emptype"];
			$shift = $row["sfrom"];
			$need = $row["need"];  
			$date = $row["_date"];  
			echo "|    $dept        | $date  |  $shift  |   $emptype  |  $need    |\n";
		 }
		}
	}
		
	   // Function to get the total utilization percent by full time and part time employees
	  function get_total_utilization()
	  {
		   
		//full time
		$report_query =  "    select 100* round(a/b,2) utp from  ";
		$report_query .=  "    (select count(shiftid) a from schedule where empid in (Select empid from employees where ftpt = 'FT'))sched, ";
		$report_query .=  "   (select count(empid) * 10 b from employees where ftpt = 'FT')tot"; 
		 
	 
	   if (!($result = mysql_query($report_query)))
	   {
		  $msg = 'MySQL error #' . mysql_errno() . ": " . mysql_error();
		  echo $msg; 
	   }
	   else if (0 == mysql_num_rows($result))
	   {
		  printf("No data are available!\n");
		  return false;
	   }
	   else
	   {

		  echo "\n \nTotal Utilization for Full - time employees : ";
		   while ($row = mysql_fetch_array($result))
		 { 
			$tus = $row["utp"];  
			echo " $tus % \n";
		 }
		}
		 
		
		//part time
		$report_query =  "    select 100* round(a/b,2) utp from  ";
		$report_query .=  "    (select count(shiftid) a from schedule where empid in (Select empid from employees where ftpt = 'PT'))sched, ";
		$report_query .=  "   (select count(empid) * 10 b from employees where ftpt = 'PT')tot"; 
	 
		   if (!($result = mysql_query($report_query)))
	   {
		  $msg = 'MySQL error #' . mysql_errno() . ": " . mysql_error();
		  echo $msg; 
	   }
	   else if (0 == mysql_num_rows($result))
	   {
		  printf("No data are available!\n");
		  return false;
	   }
	   else
	   {

		  echo "\n \nTotal Utilization for  Part - time employees : ";
		   while ($row = mysql_fetch_array($result))
		 { 
			$tus = $row["utp"];  
			echo " $tus % \n";
		 }
		}
	}

		
  //open database connection	
  openDatabase();
	 
  //Calculate employee happiness
  emp_happiness();
 
  //total schedule cost 
  get_sched_Costs();
  
  //number of unused shifts 
  get_unused_shifts();
  
  //report of unfilled vacancies
  report_unfulfilled_vacancies();
 
  // get the total utilization values
  get_total_utilization();
  
  //close database connection
  closeDatabase();
 ?> 
