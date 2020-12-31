<?php

use Psr\Container\ContainerInterface;

require_once '../src/objects/Member.php';
require_once '../src/objects/Chat.php';
require_once '../src/objects/Push.php';

class ChatController
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /* ******************************
     * 채팅방 정보 등록
     * [POST] /aiinz/chat
     * ******************************/
    public function insertChat($req, $res)
    {
        // CLS 선언
        $api = new Api;
        $chat = new Chat;

        // Request
        $chatSeq = $req->getParam('chatSeq');
        $toMemberSeq = $req->getParam('toMemberSeq');
        $fromMemberSeq = $req->getParam('fromMemberSeq');
        // 필수 파라미터 누락
        if (($chatSeq == null || '') || ($toMemberSeq == null || '') || ($fromMemberSeq == null || '')) {
            return $res->withJson($api->callError(51));
        }

        try {
            // 동일 채팅방 존재 여부 확인
            $chatInfo = $chat->getChat($chatSeq);
            if ($chatInfo) {
                return $res->withJson($api->callError(90, 'fail'));
            }

            // 채팅방 정보 등록
            $result = $chat->insertChat($chatSeq, $toMemberSeq, $fromMemberSeq);
            // PDO Exception Error
            if (!$result) {
                return $res->withJson($api->callError(99));
            }
        } catch (Exception $e) {
            return $res->withJson($api->callError(98));
        }
        // Response
        return $res->withJson($api->callResponse($result), 200, JSON_NUMERIC_CHECK);
    }

    /* ******************************
     * 채팅방 정보 조회
     * [GET] /aiinz/chat/{chatSeq}
     * ******************************/
    public function getChat($req, $res)
    {
        // CLS 선언
        $api = new Api;
        $chat = new Chat;

        // Request
        $memberSeq = $req->getQueryParam('memberSeq');
        $chatSeq = $req->getAttribute('chatSeq');
        // 필수 파라미터 누락
        if (($memberSeq == null || '') || ($chatSeq == null || '')) {
            return $res->withJson($api->callError(51));
        }

        try {
            // 채팅방 정보 조회
            $orgChat = $chat->getChat($chatSeq);
            // PDO Exception Error
            if (!$orgChat) {
                return $res->withJson($api->callError(99));
            }

            // 입력받은 회원정보를 기준으로 to, from 을 변경: client 요청사항
            $toMemberSeq = $orgChat->toMemberSeq;
            if ($toMemberSeq !== $memberSeq) {
                $newChat = new stdClass;
                $newChat->chatSeq = $orgChat->chatSeq;
                $newChat->toMemberSeq = $orgChat->fromMemberSeq;
                $newChat->toNickName = $orgChat->fromNickName;
                $newChat->toProfileImagePath = $orgChat->fromProfileImagePath;
                $newChat->toMemberIndex = $orgChat->fromMemberIndex;
                $newChat->fromMemberSeq = $orgChat->toMemberSeq;
                $newChat->fromNickName = $orgChat->toNickName;
                $newChat->fromProfileImagePath = $orgChat->toProfileImagePath;
                $newChat->fromMemberIndex = $orgChat->toMemberIndex;
                $newChat->outMemberSeq = $orgChat->outMemberSeq;
                $newChat->regDate = $orgChat->regDate;
                $orgChat = $newChat;
            }
            $result['chat'] = $orgChat;
        } catch (Exception $e) {
            return $res->withJson($api->callError(98));
        }
        // Response
        return $res->withJson($api->callResponse($result), 200, JSON_NUMERIC_CHECK);
    }

    /* ******************************
     * 채팅방 회원의 메시지 위치값을 업데이트
     * [PUT] /aiinz/chat/{chatSeq}/message/index
     * ******************************/
    public function updateMemberChatMessageIndex($req, $res)
    {
        // CLS 선언
        $api = new Api;
        $chat = new Chat;

        // Request
        $chatSeq = $req->getAttribute('chatSeq');
        $memberSeq = $req->getParam('memberSeq');
        $memberIndex = $req->getParam('memberIndex');
        // 필수 파라미터 누락
        if (($chatSeq == null || '') || ($memberSeq == null || '') || ($memberIndex == null || '')) {
            return $res->withJson($api->callError(51));
        }

        try {
            // 회원이 to/from 인지 확인
            $orgChat = $chat->getChat($chatSeq);
            $toMemberSeq = $orgChat->toMemberSeq;
            $memberIndexType = ($toMemberSeq == $memberSeq) ? 'toMemberIndex' : 'fromMemberIndex';

            // 채팅방 회원의 메시지 위치값 업데이트
            $result = $chat->updateMemberChatMessageIndex($chatSeq, $memberIndexType, $memberIndex);
            // PDO Exception Error
            if (!$result) {
                return $res->withJson($api->callError(99));
            }
        } catch (Exception $e) {
            return $res->withJson($api->callError(98));
        }
        // Response
        return $res->withJson($api->callResponse($result), 200, JSON_NUMERIC_CHECK);
    }

    /* ******************************
     * 채팅방 나갔던 회원이 다시 입장할 경우
     * [PUT] /aiinz/chat/{chatSeq}/message/outMember
     * ******************************/
    public function updateChatOutMemberSeq($req, $res)
    {

        // CLS 선언
        $api = new Api;
        $chat = new Chat;

        // Request
        $chatSeq = $req->getAttribute('chatSeq');
        $memberSeq = $req->getParam('memberSeq');
        // 필수 파라미터 누락
        if (($chatSeq == null || '') || ($memberSeq == null || '')) {
            return $res->withJson($api->callError(51));
        }

        try {
            // 회원이 to/from 인지 확인
            $orgChat = $chat->getChat($chatSeq);
            $toMemberSeq = $orgChat->toMemberSeq;
            $myMemberType = ($toMemberSeq == $memberSeq) ? 'toMember' : 'fromMember';

            $result = $chat->updateChatOutMemberSeq($chatSeq, $myMemberType, $memberSeq);
            // PDO Exception Error
            if (!$result) {
                return $res->withJson($api->callError(99));
            }
        } catch (Exception $e) {
            return $res->withJson($api->callError(98));
        }
        // Response
        return $res->withJson($api->callResponse($result), 200, JSON_NUMERIC_CHECK);
    }

    /* ******************************
     * 채팅방 정보 삭제
     * [DELETE] /aiinz/chat/{chatSeq}?memberSeq={memberSeq}&memberIndex={memberIndex}
     * ******************************/
    public function deleteChat($req, $res)
    {
        // CLS 선언
        $api = new Api;
        $chat = new Chat;

        // Request
        $chatSeq = $req->getAttribute('chatSeq');
        $memberSeq = $req->getQueryParam('memberSeq');
        $memberIndex = $req->getQueryParam('memberIndex');
        // 필수 파라미터 누락
        if (($chatSeq == null || '') || ($memberSeq == null || '') || ($memberIndex == null || '')) {
            return $res->withJson($api->callError(51));
        }

        try {
            // 채팅방 정보 조회
            $chatInfo = $chat->getChat($chatSeq);
            if (!$chatInfo) {
                return $res->withJson($api->callError(90, 'This chat room has already been deleted or not found.'));
            }

            $outMemberSeq = $chatInfo->outMemberSeq;
            $toMemberSeq = $chatInfo->toMemberSeq;
            $myMemberType = ($toMemberSeq == $memberSeq) ? 'toMember' : 'fromMember';


            if ($outMemberSeq != -1) {
                // 회원 인덱스가 같을 경우만 실제 삭제 처리 (발/수신자 모두 채팅방 삭제하기시)
                $result = $chat->deleteChat($chatSeq, $memberSeq, $myMemberType);
            } else {
                // 발/수신자 중 한사람만 채팅방 삭제하기
                $result = $chat->updateChatOutMemberSeq($chatSeq, $myMemberType, $memberSeq, 'delete', $memberIndex);
            }
            // PDO Exception Error
            if (!$result) {
                return $res->withJson($api->callError(99));
            }
        } catch (Exception $e) {
            return $res->withJson($api->callError(98));
        }
        // Response
        return $res->withJson($api->callResponse($result), 200, JSON_NUMERIC_CHECK);
    }

    /* ******************************
     * 채팅방 존재 여부 확인 (일렬 번호 조회)
     * [GET] /aiinz/chat/{memberSeq}/seq?fromMemberSeq={fromMemberSeq}
     * ******************************/
    public function getChatSeq($req, $res)
    {
        // CLS 선언
        $api = new Api;
        $chat = new Chat;

        // Request
        $toMemberSeq = $req->getAttribute('memberSeq');
        $fromMemberSeq = $req->getQueryParam('fromMemberSeq');
        // 필수 파라미터 누락
        if (($toMemberSeq == null || '') || ($fromMemberSeq == null || '')) {
            return $res->withJson($api->callError(51));
        }

        try {
            // 채팅방 존재 여부 확인
            $chatSeq = $chat->getChatSeq($toMemberSeq, $fromMemberSeq);
            // PDO Exception Error
            if (!$chatSeq && gettype($chatSeq) == 'boolean') {
                return $res->withJson($api->callError(99));
            }
            $result['chatSeq'] = (empty($chatSeq)) ? '' : $chatSeq[0]->chatSeq;
        } catch (Exception $e) {
            return $res->withJson($api->callError(98));
        }
        // Response
        return $res->withJson($api->callResponse($result), 200, JSON_NUMERIC_CHECK);
    }

}