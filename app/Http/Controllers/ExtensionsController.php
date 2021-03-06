<?php

namespace App\Http\Controllers;

use cache;
use App\Models\User;
use App\Models\Extensions;
use App\Models\Recordings;
use App\Models\Voicemails;
use App\Models\MusicOnHold;
use App\Models\Destinations;
use Illuminate\Http\Request;
use App\Models\ExtensionUser;
use App\Models\DefaultSettings;
use Illuminate\Validation\Rule;
use App\Models\NemeraldAppUsers;
use App\Models\FreeswitchSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\VoicemailDestinations;
use libphonenumber\PhoneNumberFormat;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Propaganistas\LaravelPhone\PhoneNumber;

class ExtensionsController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth')->except(['callerId','updateCallerID']);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Check permissions
        if (!userCheckPermission("extension_view")){
            return redirect('/');
        }

        // Get all extensions
        $extensions = Extensions::where ('domain_uuid', Session::get('domain_uuid'))
        ->get()
        ->sortBy('extension');
        // ->toArray();

        //Get libphonenumber object
        $phoneNumberUtil = \libphonenumber\PhoneNumberUtil::getInstance();

        foreach($extensions as $extension) {
            if ($extension['outbound_caller_id_number']){
                $phoneNumberObject = $phoneNumberUtil->parse($extension['outbound_caller_id_number'], 'US');
                if ($phoneNumberUtil->isValidNumber($phoneNumberObject)){
                    $extension->outbound_caller_id_number = $phoneNumberUtil
                        ->format($phoneNumberObject, \libphonenumber\PhoneNumberFormat::NATIONAL);
                }
            }
        }

        return view('layouts.extensions.list')
        ->with("extensions",$extensions);
        // ->with("conn_params", $conn_params);
    }

    /**
     * Display page with Caller ID options.
     *
     * @return \Illuminate\Http\Response
     */
    public function callerId(Request $request)
    {
        // Find user trying to access the page
        $appUser = NemeraldAppUsers::where('user_id', $request->user)->first();

        // If user not found throw an error
        if (!isset($appUser)){
            abort(403, 'Unauthorized user. Contact your administrator');
        }

        // Get all active phone numbers 
        $destinations = Destinations::where('destination_enabled', 'true')
            ->where ('domain_uuid', $appUser->domain_uuid)
            ->get([
                'destination_uuid',
                'destination_number',
                'destination_enabled',
                'destination_description',
                DB::Raw("coalesce(destination_description , 'n/a') as destination_description"),
            ])
            ->sortBy('destination_description')
            ->toArray();

        // If destinaions not found throw an error
        if (!isset($destinations)){
            abort(403, 'Unauthorized action. Contact your administrator1');
        }

        // Get extension for user accessing the page
        $extension = Extensions::find($appUser->extension_uuid);
 
        // If extension not found throw an error
        if (!isset($extension)){
            abort(403, 'Unauthorized extension. Contact your administrator');
        }

        //check if any of the extentions already have caller IDs assigend to them
        // if yes add TRUE column to the new array $phone_numbers
        $phone_numbers = array();
        // foreach ($extensions as $extension){
            foreach ($destinations as $destination){
                if ($destination['destination_number'] == $extension->outbound_caller_id_number){
                    $destination['isCallerID'] = true;
                    $phone_numbers[] = $destination;
                } else {
                    $destination['isCallerID'] = false;
                    $phone_numbers[] = $destination;
                }

            }
        // }

        // $format = PhoneNumberFormat::NATIONAL;
        // $phone_number = phone("6467052267","US",$format);
        // dd($phone_numbers);

        return view('layouts.extensions.callerid')
            ->with('destinations',$phone_numbers)
            ->with('national_phone_number_format',PhoneNumberFormat::NATIONAL)
            ->with ('extension',$extension);
    }

    /**
     * Update caller ID for user.
     *
     * @return \Illuminate\Http\Response
     */
    public function updateCallerID(Request $request)
    {
        // $request->destination_uuid = '4a40ab82-a9a8-4506-9f48-980cb902bcc4';
        // $request->extension_uuid = 'a2c612cc-0b8e-4e21-a8d1-81d75e8333f9';

        $destination = Destinations::find($request->destination_uuid);
        if (!$destination){
            return response()->json([
                'error' => 401,
                'message' => 'Invalid phone number ID submitted']);
        }

        if (!isset($destination)){
            return response()->json([
                'error' => 401,
                'message' => 'Unable to update Caller ID']);
        }

        // Get extension for user accessing the page
        $extension = Extensions::find($request->extension_uuid);


        if (!isset($extension)){
            return response()->json([
                'error' => 401,
                'message' => 'Unable to update Caller ID']);
        }

        // Update the caller ID field for user's extension
        // If successful delete cache
        if (session_status() == PHP_SESSION_NONE  || session_id() == '') {
            $method_setting = DefaultSettings::where('default_setting_enabled','true')
            ->where('default_setting_category','cache')
            ->where('default_setting_subcategory','method')
            ->get()
            ->first();

            $location_setting = DefaultSettings::where('default_setting_enabled','true')
            ->where('default_setting_category','cache')
            ->where('default_setting_subcategory','location')
            ->get()
            ->first();

            $freeswitch_settings = FreeswitchSettings::first();

            session_start();
//  dd($freeswitch_settings);
            $_SESSION['cache']['method']['text'] = $method_setting->default_setting_value;
            $_SESSION['cache']['location']['text'] = $location_setting->default_setting_value;
            $_SESSION['event_socket_ip_address'] = $freeswitch_settings['event_socket_ip_address'];
            $_SESSION['event_socket_port'] = $freeswitch_settings['event_socket_port'];
            $_SESSION['event_socket_password'] = $freeswitch_settings['event_socket_password'];
        }

        $cache = new cache;
        $extension->outbound_caller_id_number = $destination->destination_number;
        $extension->save();
        // dd($extension);
        $cache->delete("directory:".$extension->extension."@".$extension->user_context);

        session_destroy();

        // If successful return success status
        if ($extension->outbound_caller_id_number = $destination->destination_number){
            return response()->json([
                'extension' => $extension->extension,
                'callerID' => $destination->destination_number,
                'message' => 'Caller ID sucesfully updated',
            ]);
        // Otherwise return failed status
        } else {
            return response()->json([
                'error' => 401,
                'message' => 'Unable to update Caller ID']);
        }

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //check permissions
	    if (!userCheckPermission('extension_add') || !userCheckPermission('extension_edit')) {
            return redirect('/');
	    }

        // Get all phone numbers
        $destinations = Destinations::where('destination_enabled', 'true')
        ->where ('domain_uuid', Session::get('domain_uuid'))
        ->get([
            'destination_uuid',
            'destination_number',
            'destination_enabled',
            'destination_description',
            DB::Raw("coalesce(destination_description , '') as destination_description"),
        ])
        ->sortBy('destination_number');

        // Get music on hold 
        $moh = MusicOnHold::where('domain_uuid', Session::get('domain_uuid'))
        ->orWhere('domain_uuid', null)
        ->orderBy('music_on_hold_name', 'ASC')
        ->get()
        ->unique('music_on_hold_name');

        $recordings = Recordings::where('domain_uuid', Session::get('domain_uuid'))
        ->orderBy('recording_name', 'ASC')
        ->get();

        $extension = new Extensions();
        $extension->directory_visible = "true";
        $extension->directory_exten_visible = "true";
        $extension->enabled = "true";
        $extension->user_context = Session::get('domain_name');
        $extension->accountcode = Session::get('domain_name');
        $extension->limit_destination = "!USER_BUSY";
        $extension->limit_max = "5";
        $extension->call_timeout = "25";

        //dd($extension->domain->users);
        return view('layouts.extensions.createOrUpdate')
            -> with('extension',$extension)
            -> with('destinations',$destinations)
            -> with('domain_users',$extension->domain->users)
            -> with ('moh', $moh)
            -> with ('recordings', $recordings)
            -> with('national_phone_number_format',PhoneNumberFormat::NATIONAL);

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, Extensions $extension)
    {
        $attributes = [
            'directory_first_name' => 'first name',
            'directory_last_name' => 'last name',
            'extension' =>'extension number',
            'voicemail_mail_to' => 'email address',
            'users' => 'users field',
            'voicemail_password' => 'voicemail pin',
            'outbound_caller_id_number' => 'external caller ID',
            'voicemail_description' => 'description',
            'domain_uuid' => 'domain',
            'user_context' => 'context',
            'max_registrations' => 'registrations',
            'accountcode' => 'account code',
            'limit_max' => 'total allowed outbound calls'
            

        ];

        $validator = Validator::make($request->all(), [
            'directory_first_name' => 'required|string',
            'directory_last_name' => 'nullable|string',
            'extension' =>[
                'required',
                'numeric',
                Rule::unique('App\Models\Extensions','extension')
                    ->ignore($extension->extension_uuid,'extension_uuid')
                    ->where('domain_uuid', Session::get('domain_uuid')),
                Rule::unique('App\Models\Voicemails','voicemail_id')
                    ->where('domain_uuid', Session::get('domain_uuid')),
            ],
            'voicemail_mail_to' => 'nullable|email:rfc,dns',
            'users' => 'nullable|array',
            'directory_visible' => 'present',
            'directory_exten_visible' => 'present',
            'call_timeout' => "numeric",
            'enabled' => 'present',
            'description' => "nullable|string|max:100",
            'outbound_caller_id_number' => "present",
            'emergency_caller_id_number' => 'present',
            

            'domain_uuid' => 'required',
            'user_context' => 'required|string',
            'number_alias' => 'nullable',
            'accountcode' => 'nullable',
            'max_registrations' => 'nullable|numeric',
            'limit_max' => 'nullable|numeric',
            'limit_destination' => 'nullable|string',
            'toll_allow' => 'nullable|string',
            'call_group' => 'nullable|string',
            'call_screen_enabled' => 'nullable',
            'user_record' => 'nullable|string',
            'auth_acl' => 'nullable|string',
            'cidr' => 'nullable|string',
            'sip_force_contact' => 'nullable|string',
            'sip_force_expires' => 'nullable|numeric',
            'mwi_account' => 'nullable|string',
            'sip_bypass_media' => 'nullable|string',
            'absolute_codec_string' => 'nullable|string',
            'force_ping' => "nullable|string",
            'dial_string' => 'nullable|string',
            'hold_music' => 'nullable',

        ], [], $attributes);

        if ($validator->fails()) {
            return response()->json(['error'=>$validator->errors()]);
        }

        // Retrieve the validated input assign all attributes
        $attributes = $validator->validated();
        $attributes['effective_caller_id_name'] = $attributes['directory_first_name'] . " " . $attributes['directory_last_name'];
        $attributes['effective_caller_id_number'] = $attributes['extension'];
        if (isset($attributes['directory_visible']) && $attributes['directory_visible']== "on")  $attributes['directory_visible'] = "true";
        if (isset($attributes['directory_exten_visible']) && $attributes['directory_exten_visible']== "on")  $attributes['directory_exten_visible'] = "true";
        if (isset($attributes['enabled']) && $attributes['enabled']== "on")  $attributes['enabled'] = "true";
        $attributes['voicemail_enabled'] = "true";
        $attributes['voicemail_transcription_enabled'] = "true";
        $attributes['voicemail_local_after_email'] = "true";
        $attributes['voicemail_tutorial'] = "true";
        $attributes['voicemail_id'] = $attributes['extension'];
        $attributes['voicemail_password'] = $attributes['extension'];
        if (isset($attributes['call_screen_enabled']) && $attributes['call_screen_enabled']== "on")  $attributes['call_screen_enabled'] = "true";


        $extension->fill($attributes);    
        $extension->save();

        if (isset($attributes['users'])) {
            foreach($attributes['users'] as $ext_user){
                $extension_users = new ExtensionUser();
                $extension_users->user_uuid = $ext_user;
                $extension_users->domain_uuid = Session::get('domain_uuid');
                $extension->extension_users()->save($extension_users);
            }
        }

        $extension->voicemail = new Voicemails();
        $extension->voicemail->fill($attributes);
        //dd($extension->voicemail);
        $extension->voicemail->save();

        if (session_status() == PHP_SESSION_NONE  || session_id() == '') {
            session_start();
        }
        $cache = new cache;
        $cache->delete("directory:".$extension->extension."@".$extension->user_context);      

        //clear the destinations session array
        if (isset($_SESSION['destinations']['array'])) {
            unset($_SESSION['destinations']['array']);
        }

        return response()->json([
            'extension' => $extension->extension_uuid,
            'redirect_url' => route('extensions.edit', $extension),
            'status' => 'success',
            'message' => 'User has been saved'
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Extentions  $extentions
     * @return \Illuminate\Http\Response
     */
    public function show(Extensions $extensions)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  guid  $extention
     * @return \Illuminate\Http\Response
     */
    public function edit($extension_uuid)
    {

        //check permissions
	    if (!userCheckPermission('extension_add') || !userCheckPermission('extension_edit')) {
            return redirect('/');
	    }

        //Check FusionPBX login status
        session_start();
        if(session_status() === PHP_SESSION_NONE) {
            return redirect()->route('logout');
        }

        // get the extension
        $extension = Extensions::find($extension_uuid);

        // Get all phone numbers
        $destinations = Destinations::where('destination_enabled', 'true')
        ->where ('domain_uuid', Session::get('domain_uuid'))
        ->get([
            'destination_uuid',
            'destination_number',
            'destination_enabled',
            'destination_description',
            DB::Raw("coalesce(destination_description , '') as destination_description"),
        ])
        ->sortBy('destination_number');

        $vm_unavailable_file_exists = Storage::disk('voicemail')
            ->exists(Session::get('domain_name') .'/' . $extension->extension . '/greeting_1.wav');

        $vm_name_file_exists = Storage::disk('voicemail')
            ->exists(Session::get('domain_name') .'/' . $extension->extension . '/recorded_name.wav');

        // Get music on hold 
        $moh = MusicOnHold::where('domain_uuid', Session::get('domain_uuid'))
            ->orWhere('domain_uuid', null)
            ->orderBy('music_on_hold_name', 'ASC')
            ->get()
            ->unique('music_on_hold_name');

        $recordings = Recordings::where('domain_uuid', Session::get('domain_uuid'))
            ->orderBy('recording_name', 'ASC')
            ->get();

        //Check if there is voicemail for this extension
        if (!isset($extension->voicemail)){
            $extension->voicemail = new Voicemails();
        }

        // dd($vm_unavailable_file_exists);
        return view('layouts.extensions.createOrUpdate')
            -> with('extension',$extension)
            -> with('domain_users',$extension->domain->users)
            -> with('domain_voicemails', $extension->domain->voicemails)
            -> with('extension_users',$extension->users())
            -> with('destinations',$destinations)
            -> with('vm_unavailable_file_exists', $vm_unavailable_file_exists)
            -> with('vm_name_file_exists', $vm_name_file_exists)
            -> with ('moh', $moh)
            -> with ('recordings', $recordings)
            -> with('national_phone_number_format',PhoneNumberFormat::NATIONAL);
            
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Extentions  $extentions
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Extensions $extension)
    {

        $attributes = [
            'directory_first_name' => 'first name',
            'directory_last_name' => 'last name',
            'extension' =>'extension number',
            'voicemail_mail_to' => 'email address',
            'users' => 'users field',
            'voicemail_password' => 'voicemail pin',
            'outbound_caller_id_number' => 'external caller ID',
            'voicemail_description' => 'description',
            'domain_uuid' => 'domain',
            'user_context' => 'context',
            'max_registrations' => 'registrations',
            'accountcode' => 'account code',
            'limit_max' => 'total allowed outbound calls'
            

        ];
// dd($request->all());
        $validator = Validator::make($request->all(), [
            'directory_first_name' => 'required|string',
            'directory_last_name' => 'nullable|string',
            'extension' =>[
                'required',
                'numeric',
                Rule::unique('App\Models\Extensions','extension')
                    ->ignore($extension->extension_uuid,'extension_uuid')
                    ->where('domain_uuid', Session::get('domain_uuid')),
                Rule::unique('App\Models\Voicemails','voicemail_id')
                    ->ignore($extension->voicemail->voicemail_uuid,'voicemail_uuid')
                    ->where('domain_uuid', Session::get('domain_uuid')),
            ],
            'voicemail_mail_to' => 'nullable|email:rfc,dns',
            'users' => 'nullable|array',
            'directory_visible' => 'present',
            'directory_exten_visible' => 'present',
            'enabled' => 'present',
            'description' => "nullable|string|max:100",
            'outbound_caller_id_number' => "present",
            'emergency_caller_id_number' => 'present',
            
            'voicemail_id' => 'present',
            'voicemail_enabled' => "present",
            'call_timeout' => "numeric",
            'voicemail_password' => 'bail|required_if:voicemail_enabled,==,on|nullable|numeric|digits_between:3,10',
            'voicemail_file' => "present",
            'voicemail_transcription_enabled' => 'nullable',
            'voicemail_local_after_email' => 'present',
            'voicemail_description' => "nullable|string|max:100",
            'voicemail_alternate_greet_id' => "nullable|numeric",   
            'voicemail_tutorial' => "present",
            'voicemail_destinations'  => 'nullable|array',

            'domain_uuid' => 'required',
            'user_context' => 'required|string',
            'number_alias' => 'nullable',
            'accountcode' => 'nullable',
            'max_registrations' => 'nullable|numeric',
            'limit_max' => 'nullable|numeric',
            'limit_destination' => 'nullable|string',
            'toll_allow' => 'nullable|string',
            'call_group' => 'nullable|string',
            'call_screen_enabled' => 'nullable',
            'user_record' => 'nullable|string',
            'auth_acl' => 'nullable|string',
            'cidr' => 'nullable|string',
            'sip_force_contact' => 'nullable|string',
            'sip_force_expires' => 'nullable|numeric',
            'mwi_account' => 'nullable|string',
            'sip_bypass_media' => 'nullable|string',
            'absolute_codec_string' => 'nullable|string',
            'force_ping' => "nullable|string",
            'dial_string' => 'nullable|string',
            'hold_music' => 'nullable',

        ], [], $attributes);

        if ($validator->fails()) {
            return response()->json(['error'=>$validator->errors()]);
        }

        // Retrieve the validated input assign all attributes
        $attributes = $validator->validated();
        $attributes['effective_caller_id_name'] = $attributes['directory_first_name'] . " " . $attributes['directory_last_name'];
        $attributes['effective_caller_id_number'] = $attributes['extension'];
        if (isset($attributes['directory_visible']) && $attributes['directory_visible']== "on")  $attributes['directory_visible'] = "true";
        if (isset($attributes['directory_exten_visible']) && $attributes['directory_exten_visible']== "on")  $attributes['directory_exten_visible'] = "true";
        if (isset($attributes['enabled']) && $attributes['enabled']== "on")  $attributes['enabled'] = "true";
        if (isset($attributes['voicemail_enabled']) && $attributes['voicemail_enabled']== "on")  $attributes['voicemail_enabled'] = "true";
        if (isset($attributes['voicemail_transcription_enabled']) && $attributes['voicemail_transcription_enabled']== "on")  $attributes['voicemail_transcription_enabled'] = "true";
        if (isset($attributes['voicemail_local_after_email']) && $attributes['voicemail_local_after_email']== "false")  $attributes['voicemail_local_after_email'] = "true";
        if (isset($attributes['voicemail_local_after_email']) && $attributes['voicemail_local_after_email']== "on")  $attributes['voicemail_local_after_email'] = "false";
        if (isset($attributes['voicemail_tutorial']) && $attributes['voicemail_tutorial']== "on")  $attributes['voicemail_tutorial'] = "true";
        if (isset($attributes['call_screen_enabled']) && $attributes['call_screen_enabled']== "on")  $attributes['call_screen_enabled'] = "true";

        // Check if voicemail directory needs to be renamed 
        if($attributes['voicemail_id'] != $attributes['extension']) {
            if (file_exists(getDefaultSetting('switch','voicemail')."/default/".Session::get('domain_name')."/".$attributes['voicemail_id'])) {
                rename(
                    getDefaultSetting('switch','voicemail')."/default/".Session::get('domain_name')."/".$attributes['voicemail_id'],
                    getDefaultSetting('switch','voicemail')."/default/".Session::get('domain_name')."/".$attributes['extension']
                );
            }
            $attributes['voicemail_id'] = $attributes['extension'];

        }

        // Update Voicemail Destinations table
        foreach($extension->voicemail->voicemail_destinations as $vm_destination) {
            $vm_destination->delete();
        }
        if (isset($attributes['voicemail_destinations'])) {
            foreach($attributes['voicemail_destinations'] as $voicemail_destination){
                $destination = new VoicemailDestinations();
                $destination->voicemail_uuid_copy=$voicemail_destination;
                $destination->domain_uuid = Session::get('domain_uuid');
                $extension->voicemail->voicemail_destinations()->save($destination);
            }
        }

        // Update Extension users table
        foreach($extension->extension_users as $ext_user) {
            $ext_user->delete();
        }

        if (isset($attributes['users'])) {
            foreach($attributes['users'] as $ext_user){
                $extension_users = new ExtensionUser();
                $extension_users->user_uuid = $ext_user;
                $extension_users->domain_uuid = Session::get('domain_uuid');
                $extension->extension_users()->save($extension_users);
            }
        }
        // return response()->json([
        //     'status' => 'success',
        //     'message' => 'Extension has been saved'
        // ]);
        // Delete cache and update extension
        if (session_status() == PHP_SESSION_NONE  || session_id() == '') {
            session_start();
        }
        $cache = new cache;
        $cache->delete("directory:".$extension->extension."@".$extension->user_context);
        $extension->voicemail->update($attributes);
        $extension->update($attributes);
      

        //clear the destinations session array
        if (isset($_SESSION['destinations']['array'])) {
            unset($_SESSION['destinations']['array']);
        }

        return response()->json([
            'status' => 'success',
            'extension' => $extension->extension_uuid,
            'message' => 'Extension has been saved'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Extentions  $extentions
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $extension = Extensions::findOrFail($id);

        if(isset($extension)){
            if (isset($extension->voicemail)) {
                $deletedvm = $extension->voicemail->delete();
            }

            if (isset($extension->extension_users)) {
                $deleted = $extension->extension_users()->delete();
            }   

            $deleted = $extension->delete();

            if ($deleted){
                return response()->json([
                    'status' => 'success',
                    'id' => $id,
                    'message' => 'Selected extensions have been deleted'
                ]);
            } else {
                return response()->json([
                    'error' => 401,
                    'message' => 'There was an error deleting this extension'
                ]);
            }
        }
    }

}
