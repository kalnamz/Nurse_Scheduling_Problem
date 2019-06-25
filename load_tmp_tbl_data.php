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
	  
	// function to create temporary tables to load the data from csv
	function create_tmp_tbls()
	{
		 
		$tbl1 = " create table IF NOT EXISTS  tmp_emp (fn varchar(20), ln varchar(20), rate varchar(10), type varchar(10), hmphone varchar(20), cellphone varchar(20), ftpt varchar(5), pref_shift varchar(20), depcert varchar(100))";  
		$tbl2 = "create table IF NOT EXISTS  tmp_needs (dept varchar(10), date varchar(20), shift varchar(20), type varchar(10), needs int)";  
		$tbl3 = "create table IF NOT EXISTS  tmp_days_off (fn varchar(20), ln varchar(20), date varchar(20))"; 

	  
		if(!($result = mysql_query($tbl1)))
		{ 
			echo " \n An error occurred while creating tmp_emp.\n\n";

			return false;
		}
		 
		else if(!($result = mysql_query($tbl2)))
		{ 
			echo " \n An error occurred while creating tmp_needs.\n\n";

			return false;
		}
		 
		else if(!($result = mysql_query($tbl3)))
		{ 
			echo " \n An error occurred while creating tmp_days_off.\n\n";

			return false;
		}
		else
		{
			 echo " \n Successfully created temporary tables - tmp_emp, tmp_needs and tmp_days_off.\n\n";
		}

	}
	
  //function to load the data from csv from create temporary tables 
	function load_tmp_tables($file1, $tbl  )
	{
		 
		// generic load data command for all table/csv types

		$query = "LOAD DATA LOCAL INFILE '/home/grads/nxk420/Documents/Database/final_proj/"; 
		$query .= "".$file1."' ";
		$query .= "INTO TABLE "; 
		$query .= " " .$tbl . " ";
		$query .= "FIELDS TERMINATED BY ',' ENCLOSED BY '\"' ";
		$query .= "LINES TERMINATED BY '\r\n' ";  
		 
		if(!($result = mysql_query($query)))
		{ 
			echo " \n An error occurred while inserting the data into temporary table- $tbl.\n\n";  
			return false;
		}
		else if(0 == mysql_affected_rows())
		{
			echo " \n An error occurred while inserting the data into temporary table- $tbl. no rows selected \n\n"; 
			return false;
			}
		else
		{ 
		 
			echo " \n Successfully loaded data from $file1 into temporary table-  $tbl.\n\n";  
			return true;
		}
	}


	// function to format temporary tables
	function format_tmp_table_data()
	{
	 begin();

	 if(!($savepoint_result = mysql_query("SAVEPOINT A")))
		{
			$msg = 'My SQL error #' .mysql_errno().":".mysql_error(); 
			echo "\n\n Error is: $msg\n"; 
			return false;
		} 
		
		echo "\n Formatting tmp_days_off.\n\n";
		
		$query = "update tmp_days_off set fn = replace(fn, ' ',''), ln =replace(ln, ' ','') ";
		
		 if(!($result = mysql_query($query)))
		{ 
		 $msg = 'My SQL error #' .mysql_errno().":".mysql_error(); 
		 echo "\n\n Error is: $msg\n";
		  rollback();
			return false;
		}

		echo "\n Successfully formatted tmp_days_off.\n\n"; 
		
		echo "\n Formatting tmp_emp.\n\n";
		
		$query = "update tmp_emp set depcert = replace(depcert, '\"',''), rate = replace(rate, '$','') ";
		
		 if(!($result = mysql_query($query)))
		{ 
		 $msg = 'My SQL error #' .mysql_errno().":".mysql_error(); 
		 echo "\n\n Error is: $msg\n";
		  rollback();
			return false;
		}

		$query = "update tmp_emp set depcert = replace(depcert, ' ','') ";
		
		 if(!($result = mysql_query($query)))
		{ 
		 $msg = 'My SQL error #' .mysql_errno().":".mysql_error(); 
		 echo "\n\n Error is: $msg\n";
		  rollback();
			return false;
		}

		echo "\n Successfully formatted tmp_emp.\n\n";
		commit();
		return true;
	}
 
 
 //function to open a database connection 
 openDatabase();
  
 //function to create temporary tables to insert the data from csv
 create_tmp_tbls(); 
 
 //loading the csv data into the tables
 load_tmp_tables("employee_final.csv","tmp_emp");
 load_tmp_tables("daysoffrequests_final.csv","tmp_days_off");
 load_tmp_tables("needs_final.csv","tmp_needs"); 
 
 //format the temporary table data like removing extra spaces, quotes etc.
 format_tmp_table_data();
 
 //function to close the database connection 
 closeDatabase();
 ?> 
