<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * EWS Class
 *
 * @package		EWS
 * @subpackage	Libraries
 * @category	Exchange Web Services 2007 / 2010
 * @author		Philip Csaplar
 */

//Load includes
include(APPPATH.'libraries/EWS/lock_and_load.php');

class Ews
{
    // public vars

    // private vars
    private $ci;

    /**
     * Class constructor - loads CodeIgnighter and Configs
     */
    public function __construct()
    {
        $this->ci =& get_instance();
    }

	 /**
     * Method for Checking authentication against said Exchange Server before continuing to create EWS Instance
     * needed variables - Server hostname , exchange username without domain prefix and password
     * @returns array[]
     */
	 
	#######################################################################################Authentication Function############################################################################################### 
	
	public function check_authentication($host,$username,$password){
		$ch = curl_init('https://'.$host.'/ews/services.wsdl');
		curl_setopt($ch, CURLOPT_VERBOSE, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_NTLM);
		curl_setopt($ch, CURLOPT_USERPWD, $username.':'.$password);
		$response = curl_exec($ch);
		$info = curl_getinfo( $ch );
		$error =  curl_error ($ch);
		$http_code = array($info['http_code']);
		return $http_code;//200 if successfull authentication and 401 if failed.
	}
	
	 /**
     * Method for Defining an EWS instance
     * needed variables - Server hostname , exchange username without domain prefix and password
     * @returns array[]
     */
	 
	 public function create_ews_instants($host,$username,$password){
		$ews = new ExchangeWebServices($host, $username, $password);
		return $ews;
	 }
	 
	 /**
     * Method for getting Exchange user details from database
     * @returns array[]
     */

	public function get_exchange_user_details($user_id){
		//get user information
		$this->ci->db->where('user_id', $user_id);
		$this->ci->db->from('exchange_users');
		$query = $this->ci->db->get();
		return $query->row_array();
	}
	 
	 ##############################################################################################End of Authentication Functions################################################################################
	 
	 ##############################################################################################Calendar Functions ############################################################################################
	
	 /**
     * Method for Calendar: Get List (Retrieving ID and ChangeKey)
     * $startdate an ISO8601 date e.g. 2012-06-12T15:18:34+03:00
	 * $endate an ISO8601 date later than the above
     * @returns array[] 
     */
	 
	 public function calendar_get_list($ews,$startdate, $enddate){
		// Set init class
		$request = new EWSType_FindItemType();
		// Use this to search only the items in the parent directory in question or use ::SOFT_DELETED
		// to identify "soft deleted" items, i.e. not visible and not in the trash can.
		$request->Traversal = EWSType_ItemQueryTraversalType::SHALLOW;
		// This identifies the set of properties to return in an item or folder response
		$request->ItemShape = new EWSType_ItemResponseShapeType();
		$request->ItemShape->BaseShape = EWSType_DefaultShapeNamesType::DEFAULT_PROPERTIES;//Returns ID_ONLY - DEFAULT_PROPERTIES - ALL_PROPERTIES
		
		// Define the timeframe to load calendar items
		$request->CalendarView = new EWSType_CalendarViewType();
		$request->CalendarView->StartDate = $startdate; // an ISO8601 date e.g. 2012-06-12T15:18:34+03:00
		$request->CalendarView->EndDate = $enddate; // an ISO8601 date later than the above
		
		// Only look in the "calendars folder"
		$request->ParentFolderIds = new EWSType_NonEmptyArrayOfBaseFolderIdsType();
		$request->ParentFolderIds->DistinguishedFolderId = new EWSType_DistinguishedFolderIdType();
		$request->ParentFolderIds->DistinguishedFolderId->Id = EWSType_DistinguishedFolderIdNameType::CALENDAR;
		
		// Send request
		$response = $ews->FindItem($request);
		
		// Add events to array if event(s) were found in the timeframe specified
		if ($response->ResponseMessages->FindItemResponseMessage->RootFolder->TotalItemsInView > 0){
		   $events = $response->ResponseMessages->FindItemResponseMessage->RootFolder->Items->CalendarItem;	
		}
		else{
			if(empty($events)){
				$events = "No Events Found";  	
			}
		}
		 
		return $events; //remember to use the php function urlencode on the id and changekey else it will not work when sent to other EWS functions
	 }

	 /**
     * Method for Calendar: Get Item (Retrieves Calendar Event information by ID)
     * @returns array[] 
     */
	 
	 public function calendar_get_item($ews,$event_id){
		// Form the GetItem request
		$request = new EWSType_GetItemType();
		
		// Define which item properties are returned in the response
		$itemProperties = new EWSType_ItemResponseShapeType();
		$itemProperties->BaseShape = EWSType_DefaultShapeNamesType::ALL_PROPERTIES;
		
		// Add properties shape to request
		$request->ItemShape = $itemProperties;
		
		// Set the itemID of the desired item to retrieve
		$id = new EWSType_ItemIdType();
		$id->Id = $event_id;
		
		$request->ItemIds->ItemId = $id;
		
		//  Send the listing (find) request and get the response
		$response = $ews->GetItem($request);
		//create an array to hold response data
		$event_data = array();
		array_push($event_data,$response->ResponseMessages->GetItemResponseMessage->Items->CalendarItem);
		return $event_data;
	 }
	 
	 /**
     * Method for Calendar: Delete Item (Deletes Calendar Event information by ID)
     * @returns array[] 
     */
	 
	 public function calendar_delete_item($ews,$event_id,$event_change_key){
		// Define the delete item class
		$request = new EWSType_DeleteItemType();
		// Send to trash can, or use EWSType_DisposalType::HARD_DELETE instead to bypass the bin directly
		$request->DeleteType = EWSType_DisposalType::MOVE_TO_DELETED_ITEMS;
		// Inform all who shares the item that it has been deleted and save in sent items
		$request->SendMeetingCancellations = EWSType_CalendarItemCreateOrDeleteOperationType::SEND_TO_ALL_AND_SAVE_COPY;
		
		// Set the item to be deleted
		$item = new EWSType_ItemIdType();
		$item->Id = $event_id;
		$item->ChangeKey = $event_change_key;
		
		// We can use this to mass delete but in this case it's just one item
		$items = new EWSType_NonEmptyArrayOfBaseItemIdsType();
		$items->ItemId = $item;
		$request->ItemIds = $items;
		
		// Send the request
		$response = $ews->DeleteItem($request);
		//create an array to hold response data
		$event_data = array();
		array_push($event_data,$response->ResponseMessages->DeleteItemResponseMessage);
		return $event_data;
	 }

	/**
	* Method for Calendar: Add Item (Add Calendar Event)
	* @param string $subject  	- event subject
	* @param string $body     	- event body
	* @param int $start 		- event start time e.g. 2012-06-12T12:30:00+00:00
	* @param int $end			- event end time e.g. 2012-06-12T13:30:00+00:00
	* @param string $location 	- event location
	* @param string $anttendees	- String of Name|EmailAddresse|RespondsType eg: Unknown,Tentative,Accept,Decline. format string as so "Name|EmailAddress|RespondsType;Name|EmailAddress|RespondsType" function below will explode into array and loop through to input into update. if attendees id empty will act as Appointment not Meeting
	* @param string $importance	- event important
	* @param string $sensitivity	- event sensitivity
	* @returns array[]
	*/
	 
	 public function calendar_add_item($ews, $subject, $body, $start_date, $end_date, $allday, $location, $attendees, $importance, $sensitivity){
		
		// Start building the request.
		$request = new EWSType_CreateItemType();
		$request->Items = new EWSType_NonEmptyArrayOfAllItemsType();
		$request->Items->CalendarItem = new EWSType_CalendarItemType();
		
		// Set the subject.
		$request->Items->CalendarItem->Subject = $subject;
		//Set the Location
		$request->Items->CalendarItem->Location = $location;
		
		//if all day event set all day variable else set time variables
		if(empty($allday)){
			// Set the start and end times. For Exchange 2007, you need to include the timezone offset.
			// For Exchange 2010, you should set the StartTimeZone and EndTimeZone properties.
			$request->Items->CalendarItem->Start = $start_date; // an ISO8601 date e.g. 2012-06-12T12:30:00+00:00
			$request->Items->CalendarItem->End = $end_date; // an ISO8601 date later than the above
		}
		else{
			if(empty($start_date)){
				$start_date = date("Y-m-d")."T00:00:00+02:00";//To addjust your timezome you change the number behinf the + symbol
			}
			$request->Items->CalendarItem->Start = date('c', strtotime($start_date)); // an ISO8601 date e.g. 2012-06-12T12:30:00+00:00
			$request->Items->CalendarItem->End = date('c', strtotime($start_date.' + 1 day')); // an ISO8601 date later than the above
			$request->Items->CalendarItem->IsAllDayEvent = true;
		}
		
		//Staus to display
		$request->Items->CalendarItem->LegacyFreeBusyStatus = 'Busy';
		
		// Set reminder
		$request->Items->CalendarItem->ReminderMinutesBeforeStart = 15; //you can input your own param here , i never need it. 
		
		// Build the body.
		$request->Items->CalendarItem->Body = new EWSType_BodyType();
		$request->Items->CalendarItem->Body->BodyType = EWSType_BodyTypeType::HTML;
		$request->Items->CalendarItem->Body->_ = $body;
		
		// Set the sensativity of the event (defaults to normal).
		$request->Items->CalendarItem->Sensitivity = new EWSType_SensitivityChoicesType();
		switch($sensitivity){
			case "NORMAL":
				$request->Items->CalendarItem->Sensitivity->_ = EWSType_SensitivityChoicesType::NORMAL;
				break;
			case "PERSONAL":
				$request->Items->CalendarItem->Sensitivity->_ = EWSType_SensitivityChoicesType::PERSONAL;
				break;
			case "PRIVATE_ITEM":
				$request->Items->CalendarItem->Sensitivity->_ = EWSType_SensitivityChoicesType::PRIVATE_ITEM;
				break;
			case "CONFIDENTIAL":
				$request->Items->CalendarItem->Sensitivity->_ = EWSType_SensitivityChoicesType::CONFIDENTIAL;
				break;
			default;
				$request->Items->CalendarItem->Sensitivity->_ = EWSType_SensitivityChoicesType::NORMAL;
				break;
		}
		
		// Set the importance of the event.
		$request->Items->CalendarItem->Importance = new EWSType_ImportanceChoicesType();
		switch($importance){
			case "HIGH":
				$request->Items->CalendarItem->Importance->_ = EWSType_ImportanceChoicesType::HIGH;
				break;
			case "NORMAL":
				$request->Items->CalendarItem->Importance->_ = EWSType_ImportanceChoicesType::NORMAL;
				break;
			case "LOW":
				$request->Items->CalendarItem->Importance->_ = EWSType_ImportanceChoicesType::LOW;
				break;
			default;
				$request->Items->CalendarItem->Importance->_ = EWSType_ImportanceChoicesType::NORMAL;
				break;
		}
		
		// Set the item class type (not required).
		$request->Items->CalendarItem->ItemClass = new EWSType_ItemClassType();
		$request->Items->CalendarItem->ItemClass->_ = EWSType_ItemClassType::APPOINTMENT;
		
		
		// if Appointment don't send meeting invitations else if Meeting invite Attendees.
		if(empty($attendees)){
			$request->SendMeetingInvitations = EWSType_CalendarItemCreateOrDeleteOperationType::SEND_TO_NONE;
		}else{
			$request->SendMeetingInvitations = EWSType_CalendarItemCreateOrDeleteOperationType::SEND_TO_ALL_AND_SAVE_COPY;
			//Now add Attendees
			$attendees = explode(";",$attendees);
			for($i = 0; $i < count($attendees); $i++){
				$toattend = explode("|",$attendees[$i]);
				//Name of Attendee
				$request->Items->CalendarItem->RequiredAttendees->Attendee[$i]->Mailbox->Name = $toattend[0];
				//Email Address of Attendee
				$request->Items->CalendarItem->RequiredAttendees->Attendee[$i]->Mailbox->EmailAddress = $toattend[1];
				//Routing Type 
				$request->Items->CalendarItem->RequiredAttendees->Attendee[$i]->Mailbox->RoutingType = 'SMTP';
			}
		}
		
		$response = $ews->CreateItem($request);	
		$event_data = array();
		array_push($event_data,$response->ResponseMessages->CreateItemResponseMessage);
		return $event_data;
		//print_r($event_data);
	 }
	 
	/**
	* Method for Calendar: Edit Item (Edits Calendar Event information by ID)
	* @param string $id  		- event id
	* @param string $ckey  		- event change key
	* @param string $subject  	- event subject
	* @param string $body     	- event body
	* @param int $start 		- event start time
	* @param int $end			- event end time
	* @param string $location 	- event location
	* @param string $anttendees	- String of Name|EmailAddresse|RespondsType eg: Unknown,Tentative,Accept,Decline. format string as so "Name|EmailAddress|RespondsType;Name|EmailAddress|RespondsType" function below will explode into array and loop through to input into update. if attendees id empty will act as Appointment not Meeting
	* @param bool $allday		- is it an all-day event?
	* @param string $importance	- event important
	* @param string $sensitivity	- event sensitivity
	* @param string $cancelled	- event cancelled
	* @returns array[]
	*/
	
	 public function calendar_edit_item($ews, $event_id, $event_change_key, $subject, $body, $bodytype, $start_date, $end_date, $location, $attendees, $allday, $importance, $sensitivity, $cancelled)
	 {
        $request = new EWSType_UpdateItemType();
		$request->ConflictResolution = 'AlwaysOverwrite';
		$request->MessageDisposition = 'SaveOnly';
		if(!empty($attendees)){
			$request->SendMeetingInvitationsOrCancellations = 'SendToChangedAndSaveCopy';
		}else{
			$request->SendMeetingInvitationsOrCancellations = 'SendToNone';
		}

		$request->ItemChanges = new EWSType_NonEmptyArrayOfItemChangesType();
		$request->ItemChanges->ItemChange->ItemId->Id = $event_id;
		$request->ItemChanges->ItemChange->ItemId->ChangeKey = $event_change_key;
		$request->ItemChanges->ItemChange->Updates = new EWSType_NonEmptyArrayOfItemChangeDescriptionsType();
		
		//popoulate update array
		$updates = array(
			'calendar:Start' =>  $start_date, //e.g. 2012-06-12T12:30:00+00:00
			'calendar:End'	=> $end_date, //e.g. 2012-06-12T12:30:00+00:00
			'calendar:Location' => $location,
			'calendar:IsAllDayEvent' => $allday, //boolean true
			'calendar:IsCancelled' => $cancelled,
			'item:Importance' => $importance,
			'item:Sensitivity' => $sensitivity,
			'item:Subject' => $subject,
		);
		$n = 0;
		$request->ItemChanges->ItemChange->Updates->SetItemField = array();

		foreach($updates as $furi => $update){
			if($update){
				$prop = array_pop(explode(':',$furi));
				$request->ItemChanges->ItemChange->Updates->SetItemField[$n]->FieldURI->FieldURI = $furi;
				$request->ItemChanges->ItemChange->Updates->SetItemField[$n]->CalendarItem->$prop = $update;
				$n++;
			}
		}
		//Update Attendees
		//Note: Only the organizer can update or change attendees, if you try to update an event that you are not the organizer of that has atendees you will recieve the error: "Set action is invalid for property"
		if(!empty($attendees)){
			$attendees = explode(";",$attendees);
			$request->ItemChanges->ItemChange->Updates->SetItemField[$n]->FieldURI->FieldURI = 'calendar:RequiredAttendees';
			for($i = 0; $i < count($attendees)-1; $i++){
				$toattend = explode("|",$attendees[$i]);
				//Name of Attendee
				$request->ItemChanges->ItemChange->Updates->SetItemField[$n]->CalendarItem->RequiredAttendees->Attendee[$i]->Mailbox->Name = $toattend[0];
				//Email Address of Attendee
				$request->ItemChanges->ItemChange->Updates->SetItemField[$n]->CalendarItem->RequiredAttendees->Attendee[$i]->Mailbox->EmailAddress = $toattend[1];
				//Routing Type 
				$request->ItemChanges->ItemChange->Updates->SetItemField[$n]->CalendarItem->RequiredAttendees->Attendee[$i]->Mailbox->RoutingType = 'SMTP';
				//Responds Type of attendee
				$request->ItemChanges->ItemChange->Updates->SetItemField[$n]->CalendarItem->RequiredAttendees->Attendee[$i]->ResponseType = $toattend[2];
			}
			$n++;	
		}
		//Update body
		if(!empty($body)){
			$request->ItemChanges->ItemChange->Updates->SetItemField[$n]->FieldURI->FieldURI = 'item:Body';
			$request->ItemChanges->ItemChange->Updates->SetItemField[$n]->CalendarItem->Body->BodyType = $bodytype;
			$request->ItemChanges->ItemChange->Updates->SetItemField[$n]->CalendarItem->Body->_ = $body;
			$n++;
		}
		//print_r($request); die();
		
		$response = $ews->UpdateItem($request);
		
		//$responseCode = $response->ResponseMessages->UpdateItemResponseMessage->ResponseCode;
		//$id = $response->ResponseMessages->UpdateItemResponseMessage->Items->CalendarItem->ItemId->Id;
		//$changeKey = $response->ResponseMessages->UpdateItemResponseMessage->Items->CalendarItem->ItemId->ChangeKey;	
		
		$event_data = array();
		array_push($event_data,$response->ResponseMessages->UpdateItemResponseMessage);
		return $event_data;
		//print_r($event_data);
	}	
	
	########################################################################################End of Calendar Functions#############################################################################################
	
	
	########################################################################################Contact Functions#####################################################################################################
	
	 /**
     * Method for Contacts: Get List (Retrieving ID and ChangeKey)
	 * $intail_name // start looking at this point - 0-9-a-z
	 * $final_name // ends on this point - 0-9-a-z
     * @returns array[] 
     */
	 
	 public function contact_get_list($ews,$intail_name, $final_name){
		$request = new EWSType_FindItemType();
		
		$request->ItemShape = new EWSType_ItemResponseShapeType();
		$request->ItemShape->BaseShape = EWSType_DefaultShapeNamesType::ALL_PROPERTIES;
		
		$request->ContactsView = new EWSType_ContactsViewType();
		$request->ContactsView->InitialName = $intail_name;
		$request->ContactsView->FinalName = $final_name;
		
		$request->ParentFolderIds = new EWSType_NonEmptyArrayOfBaseFolderIdsType();
		$request->ParentFolderIds->DistinguishedFolderId = new EWSType_DistinguishedFolderIdType();
		$request->ParentFolderIds->DistinguishedFolderId->Id = EWSType_DistinguishedFolderIdNameType::CONTACTS;
		
		$request->Traversal = EWSType_ItemQueryTraversalType::SHALLOW;
		
		$response = $ews->FindItem($request);
		
		// Add Contacts to array if contact(s) were found
		if ($response->ResponseMessages->FindItemResponseMessage->RootFolder->TotalItemsInView > 0){
		   $contact_data = $response->ResponseMessages->FindItemResponseMessage->RootFolder->Items->Contact;	
		}
		else{
			if(empty($contact_data)){
				$contact_data = "No Contacts Found";  	
			}
		}
		return $contact_data;
		//return $contact_data; //remember to use the php function urlencode on the id and changekey else it will not work when sent to other EWS functions
	 }
	 
	 /**
     * Method for Contacts: Get Item (Retrieves contact Information information by ID)
     * @returns array[] 
     */
	 
	 public function contact_get_item($ews,$contact_id){
		$request = new EWSType_GetItemType();
		
		$request->ItemShape = new EWSType_ItemResponseShapeType();
		$request->ItemShape->BaseShape = EWSType_DefaultShapeNamesType::ALL_PROPERTIES;
		
		$request->ItemIds = new EWSType_NonEmptyArrayOfBaseItemIdsType();
		$request->ItemIds->ItemId = new EWSType_ItemIdType();
		$request->ItemIds->ItemId->Id = $contact_id; 
		
		$response = $ews->GetItem($request);
		//create an array to hold response data
		$contact_data = array();
		array_push($contact_data,$response->ResponseMessages->GetItemResponseMessage->Items->Contact);
		return $contact_data;
	 }
	 
	 /**
     * Method for Contacts: Delete Item (Deletes Contact information by ID)
     * @returns array[] 
     */
	 
	 public function contact_delete_item($ews,$contact_id,$contact_change_key){
		// Define the delete item class
		$request = new EWSType_DeleteItemType();
		// Send to trash can, or use EWSType_DisposalType::HARD_DELETE instead to bypass the bin directly
		$request->DeleteType = EWSType_DisposalType::MOVE_TO_DELETED_ITEMS;
		// Inform all who shares the item that it has been deleted and save in sent items
		$request->SendMeetingCancellations = EWSType_CalendarItemCreateOrDeleteOperationType::SEND_TO_ALL_AND_SAVE_COPY;
		
		// Set the item to be deleted
		$item = new EWSType_ItemIdType();
		$item->Id = $contact_id;
		$item->ChangeKey = $contact_change_key;
		
		// We can use this to mass delete but in this case it's just one item
		$items = new EWSType_NonEmptyArrayOfBaseItemIdsType();
		$items->ItemId = $item;
		$request->ItemIds = $items;
		
		// Send the request
		$response = $ews->DeleteItem($request);
		//create an array to hold response data
		$contact_data = array();
		array_push($contact_data,$response->ResponseMessages->DeleteItemResponseMessage);
		return $contact_data;
	 }

	/**
	* Method for Contact: Add Item (Add Contact)
	* @param string $given_name  	- Contacts Name
	* @param string $surname     	- Contacts surname
	* @param string $company_name  	- Contacts Name
	* @param string $job_title     	- Contacts surname
	* @param int $email_address 	- Contacts Emaill Address
	* @param int $street			- Contacts Street Name
	* @param string $city 			- Contacts City Name
	* @param string $province_state	- Contacts State or Province Name
	* @param string $postal_code	- Contacts Postal Code
	* @param string $country_region	- Contacts Country // Use your countrys name eg: 'USA' or 'United States' or 'SA' or 'South Africa' etc..
	* @param string $contact_number - Contacts Phone Number
	* @returns array[]
	*/
	 
	 public function contact_add_item($ews, $given_name, $surname,$company_name,$job_title, $email_address, $street, $city, $province_state, $postal_code, $country_region, $contact_number){
		 //Start building request
		$request = new EWSType_CreateItemType();
		//Start building Contact
		$contact = new EWSType_ContactItemType();
		$contact->GivenName = $given_name;
		$contact->Surname = $surname;
		$contact->Companyname = $company_name;
		$contact->JobTitle = $job_title;
		
		// Create an email address
		$email = new EWSType_EmailAddressDictionaryEntryType();
		$email->Key = new EWSType_EmailAddressKeyType();
		$email->Key->_ = EWSType_EmailAddressKeyType::EMAIL_ADDRESS_1;
		$email->_ = $email_address;
		
		// set the email
		$contact->EmailAddresses = new EWSType_EmailAddressDictionaryType();
		$contact->EmailAddresses->Entry[] = $email;
		
		// create an address
		$address = new EWSType_PhysicalAddressDictionaryEntryType();
		$address->Key = new EWSType_PhysicalAddressKeyType();
		$address->Key->_ = EWSType_PhysicalAddressKeyType::HOME;
		$address->Street = $street;
		$address->City = $city;
		$address->State = $province_state;
		$address->PostalCode = $postal_code;
		$address->CountryOrRegion = $country_region;
		
		// set the address
		$contact->PhysicalAddresses = new EWSType_PhysicalAddressDictionaryType();
		$contact->PhysicalAddresses->Entry[] = $address;
		
		// create a phone number
		$phone = new EWSType_PhoneNumberDictionaryEntryType();
		$phone->Key = new EWSType_PhoneNumberKeyType();
		$phone->Key->_ = EWSType_PhoneNumberKeyType::HOME_PHONE;
		$phone->_ = $contact_number;
		
		// set the phone number
		$contact->PhoneNumbers = new EWSType_PhoneNumberDictionaryType();
		$contact->PhoneNumbers->Entry[] = $phone;
		
		// set the "file as" mapping to "first name last name"
		$contact->FileAsMapping = new EWSType_FileAsMappingType();
		$contact->FileAsMapping->_ = EWSType_FileAsMappingType::FIRST_SPACE_LAST;
		
		$request->Items->Contact[] = $contact;
		
		$response = $ews->CreateItem($request);
		$contact_data = array();
		array_push($contact_data,$response->ResponseMessages->CreateItemResponseMessage);
		return $contact_data;
	 }
	 
	/**
	* Method for Contact: Edit/Update Item (Edit/Update Contact)
	* @param string $given_name  	- Contacts Name
	* @param string $surname     	- Contacts surname
	* @param string $company_name  	- Contacts Name
	* @param string $job_title     	- Contacts surname
	* @param int $email_address 	- Contacts Emaill Address
	* @param int $street			- Contacts Street Name
	* @param string $city 			- Contacts City Name
	* @param string $province_state	- Contacts State or Province Name
	* @param string $postal_code	- Contacts Postal Code
	* @param string $country_region	- Contacts Country // Use your countrys name eg: 'USA' or 'United States' or 'SA' or 'South Africa' etc..
	* @param string $contact_number - Contacts Phone Number
	* @returns array[]
	*/
	 
	 public function contact_edit_item($ews, $contact_id, $contact_change_key,$given_name, $surname,$company_name,$job_title, $email_address, $street, $city, $province_state, $postal_code, $country_region, $contact_number){
		//Start Update	
		$request = new EWSType_UpdateItemType();
		
		$request->SendMeetingInvitationsOrCancellations = 'SendToNone';
		$request->MessageDisposition = 'SaveOnly';
		$request->ConflictResolution = 'AlwaysOverwrite';
		$request->ItemChanges = array();
		
		// Build out item change request.
		$change = new EWSType_ItemChangeType();
		$change->ItemId = new EWSType_ItemIdType();
		$change->ItemId->Id = $contact_id;
		$change->ItemId->ChangeKey = $contact_change_key;
		$change->Updates = new EWSType_NonEmptyArrayOfItemChangeDescriptionsType();
		$change->Updates->SetItemField = array(); // Array of fields to be update
		
		//popoulate update array
		$updates = array(
			'contacts:GivenName' => $given_name,
			'contacts:Surname' => $surname,
			'contacts:CompanyName' => $company_name,
			'contacts:JobTitle' => $job_title,
		);

		foreach($updates as $furi => $update){
			if($update){
				$prop = array_pop(explode(':',$furi));
				// loop through array and update each item
				$field = new EWSType_SetItemFieldType();
				$field->FieldURI->FieldURI = $furi;
				$field->Contact = new EWSType_ContactItemType();
				$field->Contact->$prop = $update;
				//set array
				$change->Updates->SetItemField[] = $field;
			}
		}
					
		// Update Email1 (indexed property).
		$field = new EWSType_SetItemFieldType();
		$field->IndexedFieldURI->FieldURI = 'contacts:EmailAddress';
		$field->IndexedFieldURI->FieldIndex = EWSType_EmailAddressKeyType::EMAIL_ADDRESS_1;
		
		$field->Contact = new EWSType_ContactItemType();
		$field->Contact->EmailAddresses = new EWSType_EmailAddressDictionaryType();
		
		$entry = new EWSType_EmailAddressDictionaryEntryType();
		$entry->_ = $email_address;
		$entry->Key = EWSType_EmailAddressKeyType::EMAIL_ADDRESS_1;
		
		//var_dump($entry);
		
		$field->Contact->EmailAddresses->Entry = $entry;
		
		$change->Updates->SetItemField[] = $field; 

		// Update Physical Address.
		//Create Address Array
		$address_array = array(
			'contacts:PhysicalAddress:Street' => $street,
			'contacts:PhysicalAddress:City' => $city,
			'contacts:PhysicalAddress:State' => $province_state,
			'contacts:PhysicalAddress:CountryOrRegion' => $country_region,
			'contacts:PhysicalAddress:PostalCode' => $postal_code,
		);

		foreach($address_array as $address_info => $info){
			if($info){
				$pos = array_pop(explode(':',$address_info));
				$field = new EWSType_SetItemFieldType();
				$field->IndexedFieldURI->FieldURI = $address_info; //Street/City/State/Country/PostalCode
				$field->IndexedFieldURI->FieldIndex = EWSType_PhysicalAddressKeyType::HOME;//just change this according to the key type - Home/Business/Other
				$field->Contact = new EWSType_ContactItemType();
				$field->Contact->PhysicalAddresses = new EWSType_PhysicalAddressDictionaryType();
				$address = new EWSType_PhysicalAddressDictionaryEntryType();
				$address->Key = EWSType_PhysicalAddressKeyType::HOME;
				
				$field->Contact->PhysicalAddresses->Entry = $address;
				$field->Contact->PhysicalAddresses->Entry->$pos = $info;
				
				$change->Updates->SetItemField[] = $field; 
			}
		}
		
		//Update an Contact number
		//available types are BusinessPhone, BusinessFax, HomePhone, HomeFax, MobilePhone, Fax, etc...
		$field = new EWSType_SetItemFieldType();
		$field->IndexedFieldURI->FieldURI = 'contacts:PhoneNumber';
		$field->IndexedFieldURI->FieldIndex = EWSType_PhoneNumberKeyType::HOME_PHONE;
		
		$field->Contact = new EWSType_ContactItemType();
		$field->Contact->PhoneNumber = new EWSType_PhoneNumberDictionaryType();

		$contact = new EWSType_PhoneNumberDictionaryEntryType();
        $contact->_ = $contact_number;
        $contact->Key = EWSType_PhoneNumberKeyType::HOME_PHONE;
		
		// set the phone number
		$field->Contact->PhoneNumbers->Entry = $contact;
		$change->Updates->SetItemField[] = $field;

		// Set all changes
		$request->ItemChanges[] = $change;
		
		// Send request
		$response = $ews->UpdateItem($request);
		
		$contact_data = array();
		array_push($contact_data,$response->ResponseMessages->UpdateItemResponseMessage);
		return $contact_data;
		//print_r($event_data);
		
	}
	 
	########################################################################################End of Contact Functions#############################################################################################
	
	
	#######################################################################################Folder Functions#############################################################################################
	
	
	public function folder_get_list($ews,$folder_id=null){
		$request = new EWSType_FindFolderType();
		$request->Traversal = EWSType_FolderQueryTraversalType::DEEP; // use EWSType_FolderQueryTraversalType::DEEP for subfolders too
		$request->FolderShape = new EWSType_FolderResponseShapeType();
		$request->FolderShape->BaseShape = EWSType_DefaultShapeNamesType::ALL_PROPERTIES;
		
		// configure the view
		$request->IndexedPageFolderView = new EWSType_IndexedPageViewType();
		$request->IndexedPageFolderView->BasePoint = 'Beginning';
		$request->IndexedPageFolderView->Offset = 0;
		
		$request->ParentFolderIds = new EWSType_NonEmptyArrayOfBaseFolderIdsType();
		
		if($folder_id != null){
			// if you know exact folder id, then use this piece of code instead. For example
			$request->ParentFolderIds->FolderId = new EWSType_FolderIdType();
			$request->ParentFolderIds->FolderId->Id = $folder_id;
		}else{		
			// use a distinguished folder name to find folders inside it
			$request->ParentFolderIds->DistinguishedFolderId = new EWSType_DistinguishedFolderIdType();
			$request->ParentFolderIds->DistinguishedFolderId->Id = EWSType_DistinguishedFolderIdNameType::MESSAGE_FOLDER_ROOT;
		}
		
		// request
		$response = $ews->FindFolder($request);
		$folder_data = array();
		array_push($folder_data,$response->ResponseMessages->FindFolderResponseMessage);
		return $folder_data;
	}
	
	public function folder_get_details($ews, $folder_id=null){
		$request = new EWSType_GetFolderType();
		$request->FolderShape = new EWSType_FolderResponseShapeType();
		// to get a shorter list of properties use EWSType_DefaultShapeNamesType::DEFAULT_PROPERTIES
		$request->FolderShape->BaseShape = EWSType_DefaultShapeNamesType::ALL_PROPERTIES;
		
		// set the starting folder as the inbox
		$request->FolderIds = new EWSType_NonEmptyArrayOfBaseFolderIdsType();
		
		if($folder_id != null){
			// if you know exact folder id, then use this piece of code instead. For example
			$request->FolderIds->FolderId = new EWSType_FolderIdType();
			$request->FolderIds->FolderId->Id = $folder_id;
		}else{		
			// get details on public folder. by the same token you may get details on an inbox or contacts folder, for example
			$request->FolderIds->DistinguishedFolderId = new EWSType_DistinguishedFolderIdType();
			$request->FolderIds->DistinguishedFolderId->Id = EWSType_DistinguishedFolderIdNameType::INBOX;
		}
		
		// request
		$response = $ews->GetFolder($request);
		$folder_data = array();
		array_push($folder_data,$response->ResponseMessages->GetFolderResponseMessage);
		return $folder_data;
		
	}
	
	
}
/* End of file EWS.php */
/* Location: ./application/libraries/EWS.php */