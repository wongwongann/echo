<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once "../config/conn.php";
   if(function_exists($_GET['function'])) {
         $_GET['function']();
      }   
   function get()
   {
      global $conn;      
      try {
          $data = [];
          $query = $conn->query("SELECT item as sku, description from item order by id desc");
          $data = $query->fetchAll(PDO::FETCH_ASSOC);
          
          $response = array(
              'status' => 1,
              'message' => 'Success',
              'data' => $data
          );
      } catch (PDOException $e) {
          $response = array(
              'status' => 0,
              'message' => 'Database Error: ' . $e->getMessage(),
              'data' => []
          );
      }
      
      header('Content-Type: application/json');
      echo json_encode($response, JSON_PRETTY_PRINT);
      exit;
   }
   
   function get_id()
   {
      global $conn;
      if (!empty($_GET["id"])) {
         $id = $_GET["id"];      
            
      $query ="SELECT * FROM item WHERE id= $id";      
	  }     
	   
      $result = $conn->query($query);
      while($row = mysqli_fetch_object($result))
      {
         $data[] = $row;
      }            
      if($data)
      {
      $response = array(
                     'status' => 1,
                     'message' =>'Success',
                     'data' => $data
                  );               
      }else {
         $response=array(
                     'status' => 0,
                     'message' =>'No Data Found'
                  );
      }
      
      header('Content-Type: application/json');
      echo json_encode($response);
       
   }
   function insert()
      {
         global $conn;   
         $check = array( 'id_item' => '', 'item' => '' );
         $check_match = count(array_intersect_key($_POST, $check));
         if($check_match == count($check)){
         
               $result = mysqli_query($conn, "INSERT INTO item SET
                 
				 item = '$_POST[item]' ,
				 name = '$_POST[name]' ,
				 cbft = '$_POST[cbft]' ,
				 waktu = '$_POST[waktu]' ,
				 dim_w = '$_POST[dim_w]' ,
				 dim_d = '$_POST[dim_d]' ,
				 dim_h = '$_POST[dim_h]' ,
				 net_weight = '$_POST[net_weight]' ,	 gross_weight = '$_POST[gross_weight]' 
				 
 
			   ");
               
               if($result)
               {
                  $response=array(
                     'status' => 1,
                     'message' =>'Insert Success'
                  );
               }
               else
               {
                  $response=array(
                     'status' => 0,
                     'message' =>'Insert Failed.'
                  );
               }
         }else{
            $response=array(
                     'status' => 0,
                     'message' =>'Wrong Parameter'
                  );
         }
         header('Content-Type: application/json');
         echo json_encode($response);
      }

   function update()
      {
         global $conn;
         if (!empty($_GET["id"])) {
         $id = $_GET["id"];      
      }   
         $check = array(
			 'item' => '',
			 'name' => '',
			 'cbft' => '',
			 'waktu' => '',
			 'dim_w' => '',
			 'dim_d' => '',
			 'dim_h' => '',
			 'net_weight' => '',
			 'gross_weight' => '' 
						
					   );
         $check_match = count(array_intersect_key($_POST, $check));         
         if($check_match == count($check)){
         
              $result = mysqli_query($conn, "UPDATE item SET               
               item = '$_POST[item]', 
				 name = '$_POST[name]' ,
				 cbft = '$_POST[cbft]' ,
				 waktu = '$_POST[waktu]' ,
				 dim_w = '$_POST[dim_w]' ,
				 dim_d = '$_POST[dim_d]' ,
				 dim_h = '$_POST[dim_h]' ,
				 net_weight = '$_POST[net_weight]' ,
				 gross_weight = '$_POST[gross_weight]'                
			   WHERE id = $id");
         
            if($result)
            {
               $response=array(
                  'status' => 1,
                  'message' =>'Update Success'                  
               );
            }
            else
            {
               $response=array(
                  'status' => 0,
                  'message' =>'Update Failed'                  
               );
            }
         }else{
            $response=array(
                     'status' => 0,
                     'message' =>'Wrong Parameter',
                     'data'=> $id
                  );
         }
         header('Content-Type: application/json');
         echo json_encode($response);
      }
   function delete()
   {
      global $conn;
      $id = $_GET['id'];
      $query = "DELETE FROM item WHERE id=".$id;
      if(mysqli_query($conn, $query))
      {
         $response=array(
            'status' => 1,
            'message' =>'Delete Success'
         );
      }
      else
      {
         $response=array(
            'status' => 0,
            'message' =>'Delete Fail.'
         );
      }
      header('Content-Type: application/json');
      echo json_encode($response);
   }
 ?>