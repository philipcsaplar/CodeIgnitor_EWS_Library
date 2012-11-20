<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class test_ews extends CI_Controller {

	public function index()
	{
		
	}
	
###################################################################Calendar Functions###############################################################

	public function exchange_event_detail()
	{
		//Check Session Data
		if(!($this->session->userdata('logged_in'))) {redirect('user/login');} //User needs to be logged in
		$this->load->library('ews');
		$exchange_details = $this->ews->get_exchange_user_details('1');
		$ews = $this->ews->create_ews_instants($exchange_details['exchange_host'],$exchange_details['exchange_user'],$exchange_details['exchange_password']);
		$event_details = $this->ews->calendar_get_item($ews,$event_id);
		echo '<pre>'.print_r($event_details, true).'</pre>';	
	}
	
	public function update_exchange_event()
	{
		//Check Session Data
		if(!($this->session->userdata('logged_in'))) {redirect('user/login');} //User needs to be logged in
		$this->load->library('ews');
		//Get Variables from post.
		$event_id = $this->input->post('event_id');
		$event_change_key = $this->input->post('event_change_key');
		$subject = $this->input->post('subject');
		$body = $this->input->post('body');
		$bodytype = $this->input->post('bodytype');
		$start_date = $this->input->post('start_date');
		$end_date = $this->input->post('end_date');
		$start_time = $this->input->post('start_time');
		$end_time = $this->input->post('end_time');
		$location = $this->input->post('location');
		$attendees = $this->input->post('attendees');
		$allday = $this->input->post('allday');
		$importance = $this->input->post('importance');
		$sensitivity = $this->input->post('sensitivity');
		$cancelled = $this->input->post('cancelled');
		
		//format date and time
		$start_date = $start_date."T".$start_time.":00+00:00";
		$end_date = $end_date."T".$end_time.":00+00:00";

		$exchange_details = $this->ews->get_exchange_user_details('1');
		$ews = $this->ews->create_ews_instants($exchange_details['exchange_host'],$exchange_details['exchange_user'],$exchange_details['exchange_password']);
		$event_details = $this->ews->calendar_edit_item($ews, $event_id, $event_change_key, $subject, $body, $bodytype, $start_date, $end_date, $location, $attendees, $allday, $importance, $sensitivity, $cancelled);
		echo '<pre>'.print_r($event_details, true).'</pre>';	
	}
	
	public function delete_exchange_event()
	{
		//Check Session Data
		if(!($this->session->userdata('logged_in'))) {redirect('user/login');} //User needs to be logged in
		$this->load->library('ews');
		$exchange_details = $this->ews->get_exchange_user_details('1');
		$ews = $this->ews->create_ews_instants($exchange_details['exchange_host'],$exchange_details['exchange_user'],$exchange_details['exchange_password']);
		$event_details = $this->ews->calendar_delete_item($ews,$event_id,$event_change_key);
		echo '<pre>'.print_r($event_details, true).'</pre>';	
	}

	public function add_exchange_event()
	{
		//Check Session Data
		if(!($this->session->userdata('logged_in'))) {redirect('user/login');} //User needs to be logged in
		$this->load->library('ews');
		//Get Variables from post.
		$subject = $this->input->post('subject');
		$body = $this->input->post('body');
		$start_date = $this->input->post('start_date');
		$end_date = $this->input->post('end_date');
		$start_time = $this->input->post('start_time');
		$end_time = $this->input->post('end_time');
		$allday = $this->input->post('allday');
		$location = $this->input->post('location');
		$attendees = $this->input->post('attendees');
		$importance = $this->input->post('importance');
		$sensitivity = $this->input->post('sensitivity');
		
		//format date and time
		$start_date = $start_date."T".$start_time.":00+00:00";
		$end_date = $end_date."T".$end_time.":00+00:00";
		
		//format attendees for EWS function
		if(!empty($attendees)){
		$attendees = join(";",$attendees);
		}
		//Format Sensitivity and Importance
		$exchange_details = $this->ews->get_exchange_user_details('1');
		$ews = $this->ews->create_ews_instants($exchange_details['exchange_host'],$exchange_details['exchange_user'],$exchange_details['exchange_password']);
		$event_details = $this->ews->calendar_add_item($ews, $subject, $body, $start_date, $end_date, $allday, $location, $attendees, $importance, $sensitivity);
		echo '<pre>'.print_r($event_details, true).'</pre>';	
	}

####################################################################End of calendar functions$###############################################################3
	
####################################################################Contact Functions###########################################################	
	
	public function contact_get_list(){
		//Check Session Data
		if(!($this->session->userdata('logged_in'))) {redirect('user/login');} //User needs to be logged in
		$this->load->library('ews');
		$exchange_details = $this->ews->get_exchange_user_details('1');
		$ews = $this->ews->create_ews_instants($exchange_details['exchange_host'],$exchange_details['exchange_user'],$exchange_details['exchange_password']);
		$contacts = $this->ews->contact_get_list($ews,"a","z");	
		echo '<pre>'.print_r($contacts, true).'</pre>';	
	}
	
	public function contact_get_item(){
		//Check Session Data
		if(!($this->session->userdata('logged_in'))) {redirect('user/login');} //User needs to be logged in
		$this->load->library('ews');
		$exchange_details = $this->ews->get_exchange_user_details('1');
		$ews = $this->ews->create_ews_instants($exchange_details['exchange_host'],$exchange_details['exchange_user'],$exchange_details['exchange_password']);
		$contact_details = $this->ews->contact_get_item($ews,"AAAeAGRvbm92YW52QGZyYXNlcmFsZXhhbmRlci5jby56YQBGAAAAAABZwHMFfLCSRptZNNxhlFzZBwBa1Hxa9x+WR4DbxUZCcgNKADktICXNAABa1Hxa9x+WR4DbxUZCcgNKAJ4BK6HgAAA=","EQAAABYAAABa1Hxa9x+WR4DbxUZCcgNKAJ4BLSck");	
		echo '<pre>'.print_r($contact_details, true).'</pre>';	
	}
	
	public function delete_exchange_contact(){
		//Check Session Data
		if(!($this->session->userdata('logged_in'))) {redirect('user/login');} //User needs to be logged in
		$this->load->library('ews');
		$exchange_details = $this->ews->get_exchange_user_details('1');
		$ews = $this->ews->create_ews_instants($exchange_details['exchange_host'],$exchange_details['exchange_user'],$exchange_details['exchange_password']);
		$contact_details = $this->ews->contact_delete_item($ews,"AAAeAGRvbm92YW52QGZyYXNlcmFsZXhhbmRlci5jby56YQBGAAAAAABZwHMFfLCSRptZNNxhlFzZBwBa1Hxa9x+WR4DbxUZCcgNKADktICXNAABa1Hxa9x+WR4DbxUZCcgNKAJ4BK6HhAAA=","EQAAABYAAABa1Hxa9x+WR4DbxUZCcgNKAJ4BLSgw");
		echo '<pre>'.print_r($contact_details, true).'</pre>';	
	}
	
	public function add_exchange_contact(){
		//Check Session Data
		if(!($this->session->userdata('logged_in'))) {redirect('user/login');} //User needs to be logged in
		$this->load->library('ews');
		$exchange_details = $this->ews->get_exchange_user_details('1');
		$ews = $this->ews->create_ews_instants($exchange_details['exchange_host'],$exchange_details['exchange_user'],$exchange_details['exchange_password']);
		$contact_details = $this->ews->contact_add_item($ews, "Fred", "Flintstone", "Slate Rock and Gravel Company", "Bronto Crane Operator", "fred@flintstone.com", "301 Cobblestone Way", "Bedrock", "Arkanstone", "1111", "USA", "555-5555");
		echo '<pre>'.print_r($contact_details, true).'</pre>';	
	}

	public function update_exchange_contact(){
		//Check Session Data
		if(!($this->session->userdata('logged_in'))) {redirect('user/login');} //User needs to be logged in
		$this->load->model('calender_model');
		$this->load->library('ews');
		$exchange_details = $this->ews->get_exchange_user_details('1');
		$ews = $this->ews->create_ews_instants($exchange_details['exchange_host'],$exchange_details['exchange_user'],$exchange_details['exchange_password']);
		$contact_id = "AAAeAGRvbm92YW52QGZyYXNlcmFsZXhhbmRlci5jby56YQBGAAAAAABZwHMFfLCSRptZNNxhlFzZBwBa1Hxa9x+WR4DbxUZCcgNKADktICXNAABa1Hxa9x+WR4DbxUZCcgNKAJ4BK6HgAAA=";
		$contact_change_key = "EQAAABYAAABa1Hxa9x+WR4DbxUZCcgNKAJ4BLSck";
		
		$contact_details = $this->ews->contact_edit_item($ews, $contact_id, $contact_change_key, "Fred1", "Flintstone1", "Slate Rock and Gravel Company1", "Bronto Crane Operator1", "fred@flintstone.com1", "301 Cobblestone Way1", "Bedrock1", "Arkanstone1", "1112", "USA1", "555-55551");
		echo '<pre>'.print_r($contact_details, true).'</pre>';	
	}
	
###############################################################End of Contact Functions############################################################	

##############################################################Folder Functions#################################################################
	
	public function folder_get_list(){
		//Check Session Data
		if(!($this->session->userdata('logged_in'))) {redirect('user/login');} //User needs to be logged in
		$this->load->library('ews');
		$exchange_details = $this->ews->get_exchange_user_details('1');
		$ews = $this->ews->create_ews_instants($exchange_details['exchange_host'],$exchange_details['exchange_user'],$exchange_details['exchange_password']);
		$folder_details = $this->ews->folder_get_list($ews);
		echo '<pre>'.print_r($folder_details, true).'</pre>';	
	}
	
	public function folder_get_details(){
		//Check Session Data
		if(!($this->session->userdata('logged_in'))) {redirect('user/login');} //User needs to be logged in
		$this->load->library('ews');
		$exchange_details = $this->ews->get_exchange_user_details('1');
		$ews = $this->ews->create_ews_instants($exchange_details['exchange_host'],$exchange_details['exchange_user'],$exchange_details['exchange_password']);
		$folder_details = $this->ews->folder_get_details($ews);
		echo '<pre>'.print_r($folder_details, true).'</pre>';	
	}
	
###############################################################End of Folder Functions################################################################	


	
}