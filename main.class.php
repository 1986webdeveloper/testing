<?php
	class main{
		
		
		// global variable for error message 
		public $errMsg = '';
		
		
		//--create connection--//
		public function main(){
			$con = mysql_connect(DB_HOST, DB_USER, DB_PASS) or die('Connection Failed');
			$db = mysql_select_db(DB_NAME, $con);
			if(!$db){
				die('Connection Failed');
			}
		}
		
		
		//-- insert user data --//
		public function insertRec(){ 
			$emailid = mysql_real_escape_string($_POST['emailid']);
			$url = mysql_real_escape_string($_POST['url']);
			$starttime = date('Y-m-d H:i:s',strtotime($_POST['starttime']));
			$endtime = date('Y-m-d H:i:s',strtotime($_POST['endtime']));
			
			if(trim($emailid) == "" || !preg_match("/([\w\-]+\@[\w\-]+\.[\w\-]+)/",$emailid))
				$this->errMsg = 'Please enter valid email address.';
			else if($url == "")
				$this->errMsg = 'Please enter url.';	
			else if($_POST['starttime'] == "")
				$this->errMsg = 'Please enter start time.';	
			else if($_POST['endtime'] == "")
				$this->errMsg = 'Please enter end time.';		
			
			if($this->errMsg == ''){
				mysql_query("INSERT INTO `user` SET `emailid` = '".$emailid."' ");
				$insertuserid = mysql_insert_id();
				mysql_query("INSERT INTO `link` SET `url` = '".$url."' ");
				$insertlinkid = mysql_insert_id();
				mysql_query("INSERT INTO `user_link` SET user_id='".$insertuserid."', link_id='".$insertlinkid."', starttime='".$starttime."', endtime='".$endtime."'   ");
				
				$this->errMsg = 'Data inserted successfully.';
			}
			return $this->errMsg;
		}
		
		
		//-- Check validation and get redirect to link--//
		public function checkRedirection($email){
			
			$getuserid = mysql_query("SELECT l.url,ul.starttime, ul.endtime
				FROM `link` AS l
				LEFT JOIN user_link AS ul ON ul.link_id = l.id
				LEFT JOIN user AS u ON u.id = ul.user_id
				WHERE u.emailid = '".$email."' AND ul.starttime <= NOW() AND ul.endtime > NOW()
			");
			
			if(trim($email) == "" || !preg_match("/([\w\-]+\@[\w\-]+\.[\w\-]+)/",$email))
				$this->errMsg = 'Email address is not valid.';
			else if(mysql_num_rows($getuserid) == 0)	
				$this->errMsg = 'your link is not valid at this time.';
				
			if($this->errMsg == ''){
				$data = mysql_fetch_array($getuserid);
				
				//-- Track record --//
				mysql_query("INSERT INTO `link_clickthrough` SET url = '".$data['url']."', emailid='".$email."', clicktime=NOW() ");
				header("Location:".$data['url']);
			}
			return $this->errMsg;
		}
		
		
		//--- Display all records--//
		public function displayRecords(){
			$getuserid = mysql_query("SELECT ul.id, u.emailid, l.url,ul.starttime, ul.endtime
				FROM `link` AS l
				LEFT JOIN user_link AS ul ON ul.link_id = l.id
				LEFT JOIN user AS u ON u.id = ul.user_id
				ORDER BY u.emailid ASC
			");
			$userdata = array();
			while($resultUser = mysql_fetch_array($getuserid)) {
				$userdata[] = $resultUser; 
			}
			return $userdata;
		}
		
		
		//--- Display all records--//
		public function fetchRecords(){
			
			if($this->is_admin()) {
				$data = $this->displayRecords();
				if(sizeof($data) > 0){
					echo '<table cellpadding="0" cellspacing="0" width="100%">';
						echo '<tr>';
							echo '<th>Email</th>';	
							echo '<th>URL</th>';
							echo '<th>Start Time</th>';
							echo '<th>End Time</th>';
							echo '<th>Action</th>';
						echo '</tr>';
					foreach($data as $val){
							echo '<tr>';
								echo '<td>'.$val['emailid'].'</td>';	
								echo '<td>'.$val['url'].'</td>';
								echo '<td>'.$val['starttime'].'</td>';
								echo '<td>'.$val['endtime'].'</td>';
								echo '<td><a href="index.php?id='.$val['id'].'&action=delete">Delete</a></td>';
							echo '</tr>';
					}
					echo '</table>';	
				}
				else {
					echo '<p>No records found.</p>';
				}
			} else {
				return 'Sorry you don\'t have permission to do this operation';
			}
		}
		
		
		//-- Delete record from db --/
		public function deleteRecord($id){
			if($this->is_admin()) {
				$data = mysql_fetch_array(mysql_query("SELECT user_id, link_id FROM user_link WHERE id = '".(int)$id."' "));
				
				mysql_query("DELETE FROM link where id = '".$data['link_id']."' "); 
				mysql_query("DELETE FROM user where id = '".$data['user_id']."' "); 
				mysql_query("DELETE FROM user_link where id = '".$id."' ");
				
				$this->errMsg = "Record delete successfully."; 
			}else{
				$this->errMsg = 'Sorry you don\'t have permission to delete this record.';
			}
			return $this->errMsg;
		}
		
		
		//-- Check admin is session --//
		public function is_admin(){
			if(isset($_SESSION['userid'])) {
				return true; 
			}else{
				return false; 
			}
		}
		
		
		//-- Check admin is session --//
		public function logout(){
			unset($_SESSION['userid']);
			header('Location:index.php');
		}
		
		
		//-- Authenticate user login --//
		public function admin_auth(){
			$username = mysql_real_escape_string($_POST['username']);
			$password = mysql_real_escape_string($_POST['password']);
			
			if($username == "") 
				$this->errMsg = 'Please enter your username.';
			else if($password == "")
				$this->errMsg = 'Please enter your password.';	
			
			if($this->errMsg == '') {
				$query = mysql_query("SELECT id FROM `admin` WHERE username = '".$username."' AND password = '".md5($password)."'  ");
				if(mysql_num_rows($query) > 0) {
					$userdata = mysql_fetch_array($query);
					$_SESSION['userid'] = $userdata['id'];
					return 'Logged in successfully';
				}else{
					$this->errMsg = 'Invalid email and password';
				}
			}
			return $this->errMsg; 
		}
		
	}
?>