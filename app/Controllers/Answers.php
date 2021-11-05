<?php 
namespace App\Controllers;
use App\Entities\Answer;

class Answers extends BaseController
{
    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
    }
    public function create()
    {
        $payload = $this->request->getJSON(true);
        $answer = new Answer($payload);

        if($this->Answers->save($answer)){
            return $this->r->success(
                'answer_create_success',
                [
                    'id' => $this->Answers->getInsertId(),
                ]
            );            
        }else{
            return $this->r->failed(
                'answer_create_failed',
                [
                    'message' => $this->Answers->errors()
                ]
            );  
        }
    }
    public function retrieve($answer_id = null)
    { 
        if($answer_id!=null){
            
            return $this->r->success(
                'retrieve_success',
                [
                    'answer'=> $this->jointer->Answers(array('answers.id'=>$answer_id))
                ]
            );
        }else{
            
            return $this->r->success(
                'retrieve_success',
                [
                    'answer'=> $this->jointer->Answers()
                ]
            );
        }
    }
    public function update($answer_id)
    {
        if(!$this->Answers->find($answer_id)){
            return $this->r->failed(
                'search_failed',
                [
                    'message' => 'Answer ID: '.$answer_id.' not found'
                ]
            );  
        }
        $payload = $this->request->getJSON(true);
        $answer = new Answer($payload);
        $answer->id = $answer_id;
        
        if($this->Answers->save($answer)){
            return $this->r->success(
                'update_success',
                [
                    'payload' => $answer
                ]
            );          
        }else{
            return $this->r->failed(
                'update_failed',
                [
                    'message' => $this->Answers->errors()
                ]
            );  
        }
    }
    public function delete($id)
    {
        if(!$this->Answers->find($id)){
            return $this->r->failed(
                'search_failed',
                [
                    'message' => 'User ID: '.$id.' not found'
                ]
            );  
        }
        if(!$this->Answers->delete($id,true)){
            return $this->r->failed(
                'update_failed',
                [
                    'message' => $this->Answers->errors()
                ]
            ); 
        }
        return $this->r->success(
            'delete_success'
        );
    }
    //---------------------------------------------


}
?>