<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\RequestValidation;
use App\Jobs\Notifications;
class RequestValidationController extends APIController
{   
  function __construct(){
    $this->model = new RequestValidation();
  }

  public function getByParams($column, $value){
    $requirements = array(
      array(
        'payload' => 'id',
        'title'   => 'Receiver\'s ID',
        'validations' => null
      ),
      array(
        'payload' => 'photo',
        'title'   => 'Receiver\'s Photo',
        'validations' => null
      ),
      array(
        'payload' => 'signature',
        'title'   => 'Receiver\'s Signature',
        'validations' => null
      )
    );
    $i = 0;
    $flag = true;
    $transferStatus = 'approved';
    $initialFlag = false;
    foreach ($requirements as $key) {
      $validations = RequestValidation::where($column, '=', $value)->where('payload', '=', $key['payload'])->get();
      $requirements[$i]['validations'] = sizeof($validations) > 0 ? $validations[0] : null;

      if($initialFlag == false && sizeof($validations) > 0){
        $initialFlag = true;
      }
      
      if($flag == true && $requirements[$i]['validations'] == null){
        $flag = false;
      }
      if($transferStatus == 'approved' && sizeof($validations) > 0 && $validations[0]['status'] != 'approved'){
        $transferStatus = $validations[0]['status'];
      }
      $i++;
    }
    
    return array(
      'complete_status' => $flag,
      'requirements' => $requirements,
      'transfer_status' => ($initialFlag == true) ? $transferStatus : 'initial'
    );
  }

  public function getDetailsByParams($column, $value){
    $result = RequestValidation::where($column, '=', $value)->get();
    return (sizeof($result) > 0) ? $result[0] : null;
  }

  public function create(Request $request){
    $data = $request->all();
    $insertData = array(
      'account_id'  => $data['account_id'],
      'payload'     => $data['payload'],
      'request_id'  => $data['request_id'],
      'status'      => $data['status']
    );
    $this->insertDB($insertData);
    Notifications::dispatch('validation', $data['messages']);
    return $this->response();
  }

  public function update(Request $request){
    $data = $request->all();
    $updateData = array(
      'id'      => $data['id'],
      'status'  => $data['status']
    );
    $this->updateDB($updateData);
    Notifications::dispatch('validation', $data['messages']);
    return $this->response();
  }
}
