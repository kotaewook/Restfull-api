<?php
header("Content-Type: application/json; charset=UTF-8");

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require_once '/home/ec2-user/vendor/autoload.php';
require_once '../src/config/db.php';
require_once '../src/config/api.php';

$app = new \Slim\App;

$api = new Api();

/**
 * 전체 조회
 */
$app->get('/member', function (Request $req, Response $res) {

    $query = "SELECT * FROM test";

    $api = new Api();
    $db = new db();

    $result = $db->fetchAll($query);
    return $res->withJson($api->callResponse($result));
});

/**
 * 상세 조회
 */
$app->get('/member/{id}', function (Request $req, Response $res) {

    $id = $req->getAttribute('id');
    $query = "SELECT * FROM test where id=$id";

    $api = new Api();
    $db = new db();

    $result = $db->fetchAll($query);
    return $res->withJson($api->callResponse($result));
});

/**
 * 데이터 등록
 */
$app->post('/member', function (Request $req, Response $res) {

    $name = $req->getParam('name');
    $hobby = $req->getParam('hobby');
    $password = $req->getParam('password');

    $param = array(':name' => $name, ':hobby' => $hobby, ':password' => $password);

    $query = "INSERT INTO test SET
                name = :name,
                hobby = :hobby,
                password = :password";

    $api = new Api();
    $db = new db();

    $result = $db->Insert($query, $param);
    return $res->withJson($api->callResponse($result));
});

/**
 * 데이터 수정 patch , put
 */
$app->patch('/member/{id}', function (Request $req, Response $res) {

    $id = $req->getAttribute('id');
//    $id = $args['id']; // $res 다음에 $arg를 써주고 사용하기

    $query = "UPDATE test SET ";

    $query_array = array();
    $param = array();

    if ($name = $req->getParam('name')) {
        array_push($query_array, "name = :name");
        $param[':name'] = $name;
    }
    if ($hobby = $req->getParam('hobby')) {
        array_push($query_array, "hobby = :hobby");
        $param[':hobby'] = $hobby;
    }
    if ($password = $req->getParam('password')) {
        array_push($query_array, "password = :password");
        $param[':password'] = $password;
    }

    $query_array = implode(',', $query_array);
    $query = $query . $query_array . " WHERE id = $id";

    $api = new Api();
    $db = new db();

    $result = $db->execute($query, $param);
    return $res->withJson($api->callResponse($result));
});

$app->put('/member/{id}', function (Request $req, Response $res) {

    $id = $req->getAttribute('id');
    $name = $req->getParam('name');
    $hobby = $req->getParam('hobby');
    $password = $req->getParam('password');
    $param = array(':name' => $name, ':hobby' => $hobby, ':password' => $password);

    $query = "UPDATE test SET 
                name     = :name,
                hobby    = :hobby,
                password = :password
                where id=$id";

    $api = new Api();
    $db = new db();

    $result = $db->execute($query, $param);
    return $res->withJson($api->callResponse($result));
});

/**
 * 데이터 삭제
 */
$app->delete('/member/{id}', function (Request $req, Response $res) {

    $id = $req->getAttribute('id');
    $query = "DELETE FROM test where id = {$id}";

    $db = new db();
    $api = new Api();

    $result = $db->execute($query);
    return $res->withJson($api->callResponse($result));
});

$app->run();