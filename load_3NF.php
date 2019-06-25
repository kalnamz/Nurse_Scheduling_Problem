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

	// function to load data from temporary tables into the 3NF tables
	function load_3NF()
	{

	   begin();

	 if(!($savepoint_result = mysql_query("SAVEPOINT A")))
		{
			$msg = 'My SQL error #' .mysql_errno().":".mysql_error(); 
			echo "\n\n Error is: $msg\n"; 
			return false;
		} 
		
		echo "\n Loading data into the Tables in 3rd Normal form.\n\n";
	
		//adding empid column to the temporary table
		$query = "alter table tmp_emp add empid serial first";
		 if(!($result = mysql_query($query)))
		{ 
		 $msg = 'My SQL error #' .mysql_errno().":".mysql_error(); 
		 echo "\n\n Error is: $msg\n";
		  rollback();
			return false;
		}
		echo "\n Successfully altered tmp_emp, added empid column.\n"; 

		//inserting the shifts from the needs table
		$query = "insert into shifts(sfrom, slength)";
		$query .= " SELECT distinct replace(replace(replace(TRIM(TRAILING SUBSTRING_INDEX(shift, '-', -1) FROM shift),'-',''),'AM',':00 AM'),'PM',':00 PM') ts, 8";
		$query .= " FROM  tmp_needs";

		if(!($result = mysql_query($query)))
		{ 
		 $msg = 'My SQL error #' .mysql_errno().":".mysql_error(); 
		 echo "\n\n Error is: $msg\n";
		  rollback();
			return false;
		}
		
		echo "\n Successfully inserted data into shifts table."; 
		
		//inserting the employee types into the emp_type table
		 $query = " insert into emp_type (emptype) select distinct type from tmp_emp";
		 if(!($result = mysql_query($query)))
		{ 
		 $msg = 'My SQL error #' .mysql_errno().":".mysql_error(); 
		 echo "\n\n Error is: $msg\n";
		  rollback();
			return false;
		}
		
		echo "\n Successfully inserted data into emp_type table.\n";
		
		//inserting the phone types into the phone_types table - homephone
		$query = "  insert into phone_types values (1, 'Home');";
		 if(!($result = mysql_query($query)))
		{ 
		 $msg = 'My SQL error #' .mysql_errno().":".mysql_error(); 
		 echo "\n\n Error is: $msg\n";
		  rollback();
			return false;
		}
		
		//inserting the phone types into the phone_types table - cellphone
		$query = "  insert into phone_types values (2, 'Cell');";
		 if(!($result = mysql_query($query)))
		{ 
		 $msg = 'My SQL error #' .mysql_errno().":".mysql_error(); 
		 echo "\n\n Error is: $msg\n";
		  rollback();
			return false;
		}
		echo "\n Successfully inserted data into phone_types table.\n";

		//inserting the phone numbers into the employee phones table
		$query = "  insert into emp_phones (empid,phoneno,phone_type_id) select empid, cellphone,2 from tmp_emp where cellphone <> '(none)'";
		 if(!($result = mysql_query($query)))
		{ 
		 $msg = 'My SQL error #' .mysql_errno().":".mysql_error(); 
		 echo "\n\n Error is: $msg\n";
		  rollback();
			return false;
		}
		 
		 //inserting the phone numbers into the employee phones table
		$query = "  insert into emp_phones (empid,phoneno,phone_type_id) select empid, hmphone,1 from tmp_emp where hmphone <> '(none)'";
		 if(!($result = mysql_query($query)))
		{ 
		 $msg = 'My SQL error #' .mysql_errno().":".mysql_error(); 
		 echo "\n\n Error is: $msg\n";
		  rollback();
			return false;
		}
		echo "\n Successfully inserted data into emp_phones table.\n";

		//inserting the employees data into the employees table
		$query = "  insert into employees (empid,lastName,firstName,emp_type_ref,ftpt,hourly_rate, pref_shift)";
		$query .= " select empid, ln, fn, (select emp_type_ref from emp_type where emptype = t.type) tp,ftpt,rate, shiftid from tmp_emp t";
		$query .= "  ,shifts s where replace(replace(replace(trim(left(t.pref_shift, 4)), '-',''),'AM',':00 AM'),'PM',':00 PM')  = sfrom";
		
		 
		 if(!($result = mysql_query($query)))
		{ 
		 $msg = 'My SQL error #' .mysql_errno().":".mysql_error(); 
		 echo "\n\n Error is: $msg\n";
		  rollback();
			return false;
		}
		echo "\n Successfully inserted data into employees table.\n";

		//inserting department details into the department table
		$query = "  insert into departments (department_name) ";
		$query .= " select distinct dept from tmp_needs";
		 if(!($result = mysql_query($query)))
		{ 
		 $msg = 'My SQL error #' .mysql_errno().":".mysql_error(); 
		 echo "\n\n Error is: $msg\n";
		  rollback();
			return false;
		}
	   echo "\n Successfully inserted data into departments table.\n";

		
	  // inserting the employee certification details into emp_cert table

		$query = " select empid, depcert,(length(depcert )-length(replace(depcert ,'|',''))) DIV length('|') as delimcnt FROM tmp_emp "; 
		
		if(!($result = mysql_query($query)))
		{ 
		 $msg = 'My SQL error #' .mysql_errno().":".mysql_error(); 
		 echo "\n\n Error is: $msg\n";
		  rollback();
			return false;
		}
		else if (0 == mysql_num_rows($result))
		{
		printf("No data are available!\n");
		 rollback();
			return false;
		}
		else
		{
		  while ($row = mysql_fetch_array($result))
			{
			$empid = $row["empid"];
			$depcert = $row["depcert"];
			$delimcnt = $row["delimcnt"];
		 
			for($i = $delimcnt; $i >0; $i--)
			{
			   $insquery = " insert into emp_cert (empid, depid) ";
			   $insquery .= " select ";
			   $insquery .= "'" .$empid ."' ,";
			   $insquery .= " depid ";
			   $insquery .= " from departments d"; 
			   $insquery .= " where department_name = SUBSTRING_INDEX(SUBSTRING_INDEX( '" .$depcert. "' , '|', '" .$i. "'), '|', -1)";
			   
				
			   if(!($ins_res = mysql_query($insquery)))
				{ 
				 $msg = 'My SQL error #' .mysql_errno().":".mysql_error(); 
				 echo "\n\n Error is: $msg\n";
				  rollback();
				  return false;
				}
			 
			}
			}		
		}		
		echo "\n Successfully inserted data into emp_cert table.\n\n";

		// inserting the days off data into emp_days_off_requests table
		$query = "  insert into emp_days_off_requests(empid,date_of_request)";
		$query .= " select empid,STR_TO_DATE(date, '%M %d %Y') date from tmp_emp t, tmp_days_off d where trim(t.fn) = trim(d.fn) and trim(t.ln) = trim(d.ln)";
		 if(!($result = mysql_query($query)))
		{ 
		 $msg = 'My SQL error #' .mysql_errno().":".mysql_error(); 
		 echo "\n\n Error is: $msg\n";
		  rollback();
			return false;
		}
		 
		echo "\n Successfully inserted data into emp_days_off_requests table.\n"; 

		//inserting the needs data into the needs table
	   $query = "  insert into needs (depid, shiftid, need, _date,emp_type_ref) ";
	   $query .= " select d.depid, s.shiftid, t.needs, STR_TO_DATE(t.date, '%M %d %Y') d, et.emp_type_ref ";
	   $query .= " from departments d, shifts s, tmp_needs t, emp_type et ";
	   $query .= " where et.emptype = t.type ";
	   $query .= "  and d.department_name = t.dept ";
	   $query .= "   and s.sfrom = replace(replace(replace(trim(left(t.shift, 4)), '-',''),'AM',':00 AM'),'PM',':00 PM')  ";
	   
	    
		 if(!($result = mysql_query($query)))
		{ 
		 $msg = 'My SQL error #' .mysql_errno().":".mysql_error(); 
		 echo "\n\n Error is: $msg\n";
		  rollback();
			return false;
		} 
		echo "\n Successfully inserted data into needs table.\n\n";
		
		echo "\n All the data has been entered into the tables in 3NF.\n\n";
		commit();
	 }
   
openDatabase();

 //inserting the data in 3NF into the 3NF tables
 load_3NF();
  
   
 closeDatabase();
 ?> 
