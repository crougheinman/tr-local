<?php 
namespace App\Controllers;

class PreflightCatcher extends BaseController
{
    /**
     * Catch options request.
     *
     * @return Response
     */
    public function options()
    {
        return $this->response->setHeader('Access-Control-Allow-Origin', '*')
            ->setHeader('Access-Control-Allow-Headers', 'Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With')
            ->setHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, PUT, DELETE')
            ->setStatusCode(200);
    }
}

?>