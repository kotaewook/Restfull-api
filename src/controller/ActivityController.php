<?php

use Psr\Container\ContainerInterface;

require_once '../src/objects/Activity.php';

class ActivityController
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /* ******************************
     * 활동 이력 조회
     * [GET] /aiinz/activity/{memberSeq}
     * ******************************/
    public function getActivityList($req, $res)
    {
        // CLS 선언
        $api = new Api;
        $activity = new Activity;

        // Request
        $memberSeq = $req->getAttribute('memberSeq');
        // 필수 파라미터 누락
        if ($memberSeq == null || '') {
            return $api->callError(51);
        }

        try {
            // 활동 이력 조회
            $activityList = $activity->getActivityList($memberSeq);
            // PDO Exception Error
            if (!$activityList && gettype($activityList) == 'boolean') {
                return $res->withJson($api->callError(99));
            }
            $result['activityList'] = $activityList;
        } catch (Exception $e) {
            return $res->withJson($api->callError(98));
        }
        // Response
        return $res->withJson($api->callResponse($result), 200, JSON_NUMERIC_CHECK);
    }
}