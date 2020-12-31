<?php

class Api
{

//    실패 Return
    public function callError($resultCode, $resultMessage = '')
    {
        if ($resultMessage == '') {
            switch ($resultCode) {
                case '14':
                    $resultMessage = 'No Result';
                    break;
                case '19':
                    $resultMessage = 'Duplicate entry';
                    break;
                case '50':
                    $resultMessage = 'Empty Value';
                    break;
                case '51':
                    $resultMessage = 'required parameter missing';
                    break;
                case '52':
                    $resultMessage = 'required parameter exceeded';
                    break;
                case '54':
                    $resultMessage = 'Not Found';
                    break;
                case '55':
                    $resultMessage = 'Method Not Allowed';
                    break;
                case '98':
                    $resultMessage = 'exception error';
                    break;
                case '99':
                    $resultMessage = 'fail';
                    break;
                default:
                    break;
            }
        }

        $response = array(
            'resultCode' => $resultCode,
            'resultMessage' => $resultMessage,
            'value' => new stdClass
        );

        return $response;
    }

//    Return
    public function callResponse($data = array(), $resultCode = '10')
    {
        $resultMessage = 'ok';
        if (gettype($data) == 'boolean' && $data == false) {
            $resultCode = '99';
            $resultMessage = 'fail';
        }
        $response = array(
            'resultCode' => $resultCode,
            'resultMessage' => $resultMessage,
            'value' => (gettype($data) == 'boolean') ? new stdClass : $data
        );

        return $response;
    }

}