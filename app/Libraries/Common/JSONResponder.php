<?php namespace App\Libraries\Common;
/**
 * 
 * JSON Responder
 * 
 * Generates templated JSON encoded string
 * responses.
 * 
 * @package App.Libraries
 * @author Bill Dwight Ijiran <dwight.ijiran@gmail.com>
 */

class JSONResponder
{
    public function __construct() {
        $this->Response = \Config\Services::response();
        $this->Response
            ->setHeader('Content-Type', 'application/json; charset=utf-8')
            ->setHeader('Access-Control-Allow-Origin', '*')
            ->setHeader('Access-Control-Allow-Methods', 'GET, HEAD, OPTIONS, POST, PUT, DELETE')
            ->setHeader(
                'Access-Control-Allow-Headers', 'Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With'
            );
    }

    /**
     * Creates a response.
     *
     * @return void
     */
    private function buildResponse($httpCode = 200, $status = 'success', $data = [])
    {
        $this->Response->setStatusCode($httpCode);

        $responseObject = [
            'status' => $status,
            'timestamp' => strtotime(date('Y-m-d H:i:s'))
        ];

        if ($data !== null && !empty($data)) {
            $responseObject['data'] = $data;
        }

        $this->Response->setBody(json_encode($responseObject));

        return $this->Response;
    }

    /**
     * Create a success response.
     *
     * @param string $status
     * @param mixed $data
     * @return Response
     */
    public function success($status = 'success', $data = null)
    {
        return $this->buildResponse(200, $status, $data);
    }

    /**
     * Create a failed response.
     *
     * @param string $status
     * @param mixed $data
     * @return Response
     */
    public function failed($status = 'failed', $data = null)
    {
        return $this->buildResponse(400, $status, $data);
    }

    /**
     * Creates invalid input response.
     *
     * @param string $status
     * @param mixed $data
     * @return Response
     */
    public function invalidInput($status = 'invalidInput', $data = null)
    {
        return $this->buildResponse(400, $status, $data);
    }

    /**
     * Create forbidden response.
     *
     * @param string $status
     * @param mixed $data
     * @return Response
     */
    public function forbidden($status = "forbidden", $data = null)
    {
        return $this->buildResponse(403, $status, $data);
    }

    /**
     * Creates a 404 not found response.
     *
     * @param string $status
     * @param mixed $data
     * @return Response
     */
    public function notFound($status = "notFound", $data = null)
    {
        return $this->buildResponse(404, $status, $data);
    }

    /**
     * Creates a 500 Internal Error response.
     *
     * @param string $status
     * @param mixed $data
     * @return Response
     */
    public function internalError($status = "internalError", $data = null)
    {
        return $this->buildResponse(500, $status, $data);
    }

    /**
     * Creates a 401 unauthorized response.
     *
     * @param string $status
     * @param mixed $data
     * @return Response
     */
    public function unauthorized($status = "unauthorized", $data = null)
    {
        return $this->buildResponse(401, $status, $data);
    }
}