<?php

use Psr\Container\ContainerInterface;

require_once '../src/objects/Member.php';
require_once '../src/objects/Push.php';

class PushController
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /*  채팅 알림 푸시 보내기 */
    public function sendChatPush($req, $res)
    {
        // CLS 선언
        $api = new Api;
        $member = new Member;
        $push = new Push;

        // Request
        $fromMemberSeq = $req->getParam('fromMemberSeq');
        $toMemberSeq = $req->getParam('toMemberSeq');

        // 필수 파라미터 누락
        if (($fromMemberSeq == null || '') || ($toMemberSeq == null || '')) {
            return $res->withJson($api->callError(51));
        }

        try {
            $result = true;
            if ($fromMemberSeq != $toMemberSeq) {
                // 대상 회원 FCM 정보 조회
                $fcmToken = $member->getMemberFcmToken($fromMemberSeq);
                // 대상 회원 정보 조회
                $fromMemberProfile = $member->getMemberProfile($toMemberSeq);
                $fromMemberNickname = $fromMemberProfile->nickName;
                // 발송 회원 정보 조회
                $toMemberProfile = $member->getMemberProfile($fromMemberSeq);

                /* ****************
                 * 리턴 해야 할 파라미터
                 * fromMemberSeq	Integer	대상 회원 일련번호
                 * fromProfileImagePath	String	대상 회원 프로필 이미지 경로
                 * fromNickName	String	대상 회원 별명
                 * profileImagePath	String	회원 프로필 이미지 경로
                * ****************/
                $_fromMemberSeq = $fromMemberProfile->memberSeq;
                $_fromProfileImagePath = $fromMemberProfile->profileImagePath;
                $_fromNickName = $fromMemberProfile->nickName;
                $_profileImagePath = $toMemberProfile->profileImagePath;

                $data['fromMemberSeq'] = $_fromMemberSeq;
                $data['fromProfileImagePath'] = $_fromProfileImagePath;
                $data['fromNickName'] = $_fromNickName;
                $data['profileImagePath'] = $_profileImagePath;

                /* ****************
                 * PUSH 발송
                * ****************/
                $fcmContents = $fromMemberNickname . " 님이 메시지를 보냈습니다.";
                $result = $push->sendPush($fcmToken, $fcmContents, $data);
                // PDO Exception Error
                if (!$result) {
                    return $res->withJson($api->callError(99, 'chat push Error'));
                }
            }
        } catch (Exception $e) {
            return $res->withJson($api->callError(98));
        }
        // Response
        return $res->withJson($api->callResponse($result), 200, JSON_NUMERIC_CHECK);
    }

}