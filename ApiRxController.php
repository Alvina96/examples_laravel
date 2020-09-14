<?php

namespace App\Http\Controllers\ApiRx;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Auth;
use Validator;
use App\State;
use App\Location;
use App\ApiAuth;
use App\Prescriber;
use App\Patient;
use App\Script;
use App\ScriptHistoryLog;
use App\Repository\ImageRepository;
use App\Events\ScriptTypeChanged;

class ApiRxController extends Controller
{
    // location create/update
    public function location(Request $request)
    {
        $content = trim(file_get_contents("php://input"));
        $decoded = json_decode($content, true);

        if (!empty($decoded['auth_key']) && ApiAuth::where(['auth_key'=>$decoded['auth_key']])->first()) {
            foreach ($decoded['data'] as $data) {
                $validations = [
                    'pharm_loc_id' => "required",
                ];

                $validator = Validator::make($data, $validations);

                if (isset($data['pharm_loc_id'])) {
                    $location = Location::findByLocationId($data['pharm_loc_id']);
                    // if (!$location) {
                    //     $validator = Validator::make($data, Location::validateCreate());
                    // } else {
                    //     $validator = Validator::make($data, Location::validateUpdate());
                    // }
                }

                if($validator->fails()){
                    return $this->sendError("Validation Error", $validator->errors());
                }

                if (!$location) {
                    if (isset($data['pharm_loc_state']) && State::where('title', 'LIKE', $data['pharm_loc_state'])->orWhere('postal', 'LIKE', $data['pharm_loc_state'])->first()) {
                        $data['pharm_loc_state'] = State::where('title', 'LIKE', $data['pharm_loc_state'])->orWhere('postal', 'LIKE', $data['pharm_loc_state'])->first()->id;
                    } else {
                        return array('status' => false,'message'=>"State name or postal code not correct.");
                    }
                    $location = Location::create($data);
                    $message = 'Location added successfully';
                } else {
                    if (isset($data['pharm_loc_state']) && $data['pharm_loc_state'] != "" && State::where('title', 'LIKE',$data['pharm_loc_state'])->orWhere('postal', 'LIKE', $data['pharm_loc_state'])->first()) {
                        $data['pharm_loc_state'] = State::where('title', 'LIKE', $data['pharm_loc_state'])->orWhere('postal', 'LIKE', $data['pharm_loc_state'])->first()->id;
                    } elseif (!isset($data['pharm_loc_state']) || $data['pharm_loc_state'] == "") {
                        $data['pharm_loc_state'] = $location->pharm_loc_state;
                    } else {
                        return array('status' => false,'message'=>"State name or postal code not correct.");
                    }
                    $location->update($data);
                    $message = 'Location information with id '.$location->id.' updated successfully';
                }
            }
            return array('status' => true,'message' => $message);
        } else {
            return array('status' => false,'message' => "Invalid Auth key.");
        }
    }

    // patient create/update
    public function patient(Request $request)
    {
        $content = trim(file_get_contents("php://input"));
        $decoded = json_decode($content, true);

        if (!empty($decoded['auth_key']) && ApiAuth::where(['auth_key'=>$decoded['auth_key']])->first()) {
            foreach ($decoded['data'] as $data) {
                $validations = [
                    'patient_id' => "required",
                ];

                $validator = Validator::make($data, $validations);

                if (isset($data['patient_id'])) {
                    $patient = Patient::findByPatientId($data['patient_id']);
                    // if (!$patient) {
                    //     $validator = Validator::make($data, Patient::validateCreate());
                    // } else {
                    //     $validator = Validator::make($data, Patient::validateUpdate());
                    // }
                }

                if($validator->fails()){
                    return $this->sendError("Validation Error", $validator->errors());
                }

                if (!$patient) {
                    if (isset($data['patient_state']) && State::where('title', 'LIKE', $data['patient_state'])->orWhere('postal', 'LIKE', $data['patient_state'])->first()) {
                        $data['patient_state'] = State::where('title', 'LIKE', $data['patient_state'])->orWhere('postal', 'LIKE', $data['patient_state'])->first()->id;
                    } else {
                        return array('status' => false,'message'=>"State name or postal code not correct.");
                    }
                    $patient = Patient::create($data);
                    $message = 'Patient added successfully';
                } else {
                    if (isset($data['patient_state']) && $data['patient_state']!="" && State::where('title', 'LIKE', $data['patient_state'])->orWhere('postal', 'LIKE', $data['patient_state'])->first()) {
                        $data['patient_state'] = State::where('title', 'LIKE', $data['patient_state'])->orWhere('postal', 'LIKE', $data['patient_state'])->first()->id;
                    } elseif (!isset($data['patient_state']) || $data['patient_state']=="") {
                        $data['patient_state'] = $patient->patient_state;
                    } else {
                        return array('status' => false,'message'=>"State name or postal code not correct.");
                    }
                    $patient->update($data);
                    $message = 'Patient information with id '.$patient->id.' updated successfully';
                }
            }
            return array('status' => true,'message' => $message);

        } else {
            return array('status' => false,'message' => "Invalid Auth key.");
        }
    }

    // script create/update
    public function script(Request $request)
    {
        $content = trim(file_get_contents("php://input"));
        $decoded = json_decode($content, true);

        if (!empty($decoded['auth_key']) && ApiAuth::where(['auth_key'=>$decoded['auth_key']])->first()) {
            foreach ($decoded['data'] as $data) {
                $validations = [
                    'script_id' => "required",
                ];

                $validator = Validator::make($data, $validations);

                if (isset($data['script_id'])) {
                    $script = Script::findByScriptId($data['script_id']);
                    $refill = Script::where('parent_script_id',$data['script_id'])->first();
                    // if (!$script) {
                    //     $validator = Validator::make($data, Script::validateCreate());
                    // } else {
                    //     $validator = Validator::make($data, Script::validateUpdate());
                    // }
                }

                if($validator->fails()){
                    return $this->sendError("Validation Error", $validator->errors());
                }
                if (isset($data['script_hard_copy_image'])) {
                    ImageRepository::uploadCameraImage($data['script_hard_copy_image'],'hard_copy.jpg',"uploads/scripts/".$data['script_id']."/");
                    unset($data['script_hard_copy_image']);
                }
                if (!$script) {
                    // Add new script if there is no script in scripts table with $data['script_id']
                    $script = Script::create($data);

                    ScriptHistoryLog::create([
                        'user_id' => 0,
                        'script_id' => $script->script_id,
                        'type' => 'history',
                        'description' => 'The prescription was filled for the first time.'
                    ]);
                    $message = 'Prescription added successfully';
                } elseif ($script && $script->inQueue() && $script->script_drug_refills_authorized == $data['script_drug_refills_authorized']
                        && $script->script_drug_refills_dispensed == $data['script_drug_refills_dispensed'] && $script->script_drug_refills_remaining == $data['script_drug_refills_remaining']) {
                    $items='';
                    foreach ($data as $key => $value) {
                        if ($script->$key!=$value) {
                            $items.='<br>-'.str_replace('_',' ',$key)." was updated to ".$value;
                        }
                    }
                    if ($script->script_status != 'Initial' && $script->script_status != 'Rejected') {
                        $data['script_status']='Initial';
                    }
                    if ($items!="") {
                        $data['script_notice']='The following information related to this Rx was updated:'.$items.".";
                    }
                    $script->update($data);
                    $message = 'Prescription updated successfully';
                } elseif ($refill && $refill->inQueue() && $refill->script_drug_refills_authorized == $data['script_drug_refills_authorized']
                        && $refill->script_drug_refills_dispensed == $data['script_drug_refills_dispensed'] && $refill->script_drug_refills_remaining == $data['script_drug_refills_remaining']) {
                    $items='';

                    foreach ($data as $key => $value) {
                        if ($refill->$key!=$value) {
                            $items.='<br>-'.str_replace('_',' ',$key)." was updated to ".$value;
                        }
                    }
                    if ($refill->script_status != 'Initial' && $refill->script_status != 'Rejected') {
                        $data['script_status']='Initial';
                    }
                    if ($items!="") {
                        $data['script_notice']='The following information related to this Rx was updated:'.$items.".";
                    }
                    $data['script_id'] = $refill->script_id;
                    $refill->update($data);
                    $message = 'Refill Prescription updated successfully';
                } elseif ($script && $script->script_drug_refills_authorized == $data['script_drug_refills_authorized']
                    && $script->script_drug_refills_remaining != $data['script_drug_refills_remaining']
                    && $script->script_drug_refills_dispensed != $data['script_drug_refills_dispensed']) {
                    // create ne record with createByType method
                    $newscript = Script::createByType($script,'refill',$data);

                    ScriptHistoryLog::create([
                        'user_id' => 0,
                        'script_id' => $newscript->getParentOriginal()->script_id,
                        'type' => 'history',
                        'description' => 'The prescription was refilled.'
                    ]);
                    $message = 'Prescription with script_id '.$newscript->script_id.' added successfully';
                } elseif($script && $script->inQueue() && $script->script_drug_refills_authorized != $data['script_drug_refills_authorized']) {
                    $items='';
                    foreach ($data as $key => $value) {
                        if ($script->$key!=$value) {
                            $items.='<br>-'.str_replace('_',' ',$key)." was updated to ".$value;
                        }
                    }
                    if ($script->script_status != 'Initial' && $script->script_status != 'Rejected') {
                        $data['script_status']='Initial';
                    }
                    if ($items!="") {
                        $data['script_notice']='The following information related to this Rx was updated:'.$items.".";
                    }
                    if ($script->getParentOriginal()) {
                        $script->getParentOriginal()->update($data);
                    } else {
                        $script->update($data);
                    }
                    $message = 'Prescription updated successfully';
                }
            }

            event(new ScriptTypeChanged($script->script_status,$script->getLocUsers()));
            return array('status' => true,'message' => $message);
        } else {
            return array('status' => false,'message' => "Invalid Auth key.");
        }
    }

    // prescriber create/update
    public function prescriber(Request $request)
    {
        $content = trim(file_get_contents("php://input"));
        $decoded = json_decode($content, true);

        if (!empty($decoded['auth_key']) && ApiAuth::where(['auth_key'=>$decoded['auth_key']])->first()) {
            foreach ($decoded['data'] as $data) {
                $validations = [
                    'prescriber_id' => "required",
                ];

                $validator = Validator::make($data, $validations);

                if (isset($data['prescriber_id'])) {
                    $prescriber = Prescriber::findByPrescriberId($data['prescriber_id']);
                    // if (!$prescriber) {
                    //     $validator = Validator::make($data, Prescriber::validateCreate());
                    // } else {
                    //     $validator = Validator::make($data, Prescriber::validateUpdate());
                    // }
                }

                if($validator->fails()){
                    return $this->sendError("Validation Error", $validator->errors());
                }
                $data['prescriber_last_name']=explode(',',$data['prescriber_last_name'])[0];
                $data['prescriber_first_name']=explode(" undefined",$data['prescriber_first_name'])[0];
                if (!$prescriber) {
                    if (isset($data['prescriber_state']) && State::where('title', 'LIKE', $data['prescriber_state'])->orWhere('postal', 'LIKE', $data['prescriber_state'])->first()) {
                        $data['prescriber_state'] = State::where('title', 'LIKE', $data['prescriber_state'])->orWhere('postal', 'LIKE', $data['prescriber_state'])->first()->id;
                    } else {
                        return array('status' => false,'message'=>"State name or postal code not correct.");
                    }
                    $prescriber = Prescriber::create($data);
                    $message = 'Prescriber added successfully';
                } else {
                    if (isset($data['prescriber_state']) && $data['prescriber_state'] != "" && State::where('title', 'LIKE', $data['prescriber_state'])->orWhere('postal', 'LIKE', $data['prescriber_state'])->first()) {
                        $data['prescriber_state'] = State::where('title', 'LIKE', $data['prescriber_state'])->orWhere('postal', 'LIKE', $data['prescriber_state'])->first()->id;
                    } elseif (!isset($data['prescriber_state']) || $data['prescriber_state'] == "") {
                        $data['prescriber_state'] = $prescriber->prescriber_state;
                    } else {
                        return array('status' => false,'message'=>"State name or postal code not correct.");
                    }
                    $prescriber->update($data);
                    $message = 'Prescriber information with id '.$prescriber->id.' updated successfully';
                }
            }
            return array('status' => true,'message' => $message);
        } else {
            return array('status' => false,'message' => "Invalid Auth key.");
        }
    }

    public function sendError($error, $errorMessages = [], $code = 404)
    {
        $response = [
            'success' => false,
            'message' => $error,
        ];
        if(!empty($errorMessages)){
            $response['data'] = $errorMessages;
        }
        return response()->json($response, $code);
    }
}
