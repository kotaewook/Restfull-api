<?php

use Psr\Container\ContainerInterface;

require_once '../src/objects/Member.php';
require_once '../src/objects/Chat.php';
require_once '../src/objects/Post.php';
require_once '../src/objects/Activity.php';
require_once '../src/objects/Push.php';

class MemberController
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /* ******************************
     * 회원 가입
     * [POST] /aiinz/members/member
     * ******************************/
    public function insertMember($req, $res)
    {
        // CLS 선언
        $api = new Api;
        $member = new Member;

        // Request
        $phoneNumber = $req->getParam('phoneNumber');
        $nickName = $req->getParam('nickName');
        $fcmToken = $req->getParam('fcmToken');
        // 필수 파라미터 누락
        if (($phoneNumber == null || '') || ($nickName == null || '') || ($fcmToken == null || '')) {
            return $res->withJson($api->callError(51));
        }

        try {
            // 휴대폰 번호 중복 체크
            $mobileResult = $member->getMemberSeq($phoneNumber, '');
            $mobileMemSeq = $mobileResult->memberSeq;
            if ($mobileMemSeq !== null && $mobileMemSeq > 0) {
                return $res->withJson($api->callError(21, 'duplicate phonenumber'));
            }

            // 별명 중복 체크
            $nickResult = $member->getMemberSeq('', $nickName);
            $nickMemSeq = $nickResult->memberSeq;
            if ($nickMemSeq !== null && $nickMemSeq > 0) {
                return $res->withJson($api->callError(22, 'duplicate nickname'));
            }

            // 회원 등록
            $insertResult = $member->insertMember($phoneNumber, $nickName, $fcmToken);
            // PDO Exception Error
            if (!$insertResult) {
                return $res->withJson($api->callError(99));
            }

            // 회원가입 된 MemberSeq 조회
            $memberResult = $member->getMemberSeq($phoneNumber, $nickName);
            $insertResponse['memberSeq'] = $memberResult->memberSeq;
        } catch (Exception $e) {
            return $res->withJson($api->callError(98));
        }
        // Response
        return $res->withJson($api->callResponse($insertResponse), 200, JSON_NUMERIC_CHECK);
    }

    /* ******************************
     * 닉네임 회원 조회 (부분 일치)
     * [GET] /aiinz/members/nickname/{nickName}
     * ******************************/
    public function getMemberByNickname($req, $res)
    {
        // CLS 선언
        $api = new Api;
        $member = new Member;
        // Request
        $nickName = $req->getAttribute('nickName');
        // 필수 파라미터 누락
        if ($nickName == null || '') {
            return $res->withJson($api->callError(51));
        }

        try {
            // 닉네임 조회
            $memberList = $member->getMemberByNickname($nickName);
            // PDO Exception Error
            if (!$memberList && gettype($memberList) == 'boolean') {
                return $res->withJson($api->callError(99));
            }
            $result['memberList'] = (empty($memberList)) ? [] : $memberList;
        } catch (Exception $e) {
            return $res->withJson($api->callError(98));
        }
        // Response
        return $res->withJson($api->callResponse($result), 200, JSON_NUMERIC_CHECK);
    }

    /* ******************************
     * 전화번호 또는 닉네임으로 회원 존재여부 조회
     * [GET] /aiinz/members/member/exists?nickName={nickName}&phoneNumber={phoneNumber}
     * ******************************/
    public function getMemberExists($req, $res)
    {
        // CLS 선언
        $api = new Api;
        $member = new Member;

        // Request
        $phoneNumber = $req->getQueryParam('phoneNumber');
        $nickName = $req->getQueryParam('nickName');
        // 필수 파라미터 누락 :: 파라미터 둘 다 없을 경우
        if (($phoneNumber == null || '') && ($nickName == null || '')) {
            return $res->withJson($api->callError(51));
        }
        // 필수 파라미터 초과 :: 파라미터 둘 다 있을 경우
        if (($phoneNumber !== null && '') && ($nickName !== null && '')) {
            return $res->withJson($api->callError(52));
        }

        try {
            // 전화번호 또는 닉네임으로 회원 존재 여부 조회
            $result = $member->getMemberSeq($phoneNumber, $nickName);
            // PDO Exception Error
            if (!$result && gettype($result) == 'boolean') {
                return $res->withJson($api->callError(99));
            }
            // 중복 회원 memberSeq
            $memberSeq = $result->memberSeq;

            $returnArray = array('isExists' => false, 'memberSeq' => null);
            // 중복 존재 여부
            if ($memberSeq !== null && $memberSeq !== '' && (int)$memberSeq > 0) {
                $returnArray['isExists'] = true;
                $returnArray['memberSeq'] = $memberSeq;
            }
        } catch (Exception $e) {
            return $res->withJson($api->callError(98));
        }
        // Response
        return $res->withJson($api->callResponse($returnArray), 200, JSON_NUMERIC_CHECK);
    }

    /* ******************************
     * 회원 프로필 정보 조회
     * [GET] /aiinz/members/mypage/{memberSeq}
     * ******************************/
    public function getMemberProfile($req, $res)
    {
        // CLS 선언
        $api = new Api;
        $member = new Member;
        $chat = new Chat;
        $post = new Post;

        // Request
        $memberSeq = $req->getAttribute('memberSeq');
        // 필수 파라미터 누락
        if ($memberSeq == null || '') {
            return $res->withJson($api->callError(51));
        }

        try {
            // 회원 프로필 조회
            $memberProfile = $member->getMemberProfile($memberSeq);
            // PDO Exception Error
            if (!$memberProfile && gettype($memberProfile) == 'boolean') {
                return $res->withJson($api->callError(99));
            }

            // 회원 게시글 조회
            $memberPostList = $post->getMemberPosts($memberSeq);
            // PDO Exception Error
            if (!$memberPostList && gettype($memberPostList) == 'boolean') {
                return $res->withJson($api->callError(99));
            }

            // 회원 채팅 목록 조회
            $memberChatList = $chat->getChatList($memberSeq);
            // PDO Exception Error
            if (!$memberChatList && gettype($memberChatList) == 'boolean') {
                return $res->withJson($api->callError(99));
            }

            $rsMemberProfile = $memberProfile;
            if ($memberProfile != null) {
                // 회원 작성 게시글 개수
                $rsMemberProfile->postsCount = count($memberPostList);
                $rsMemberProfile->postsList = $memberPostList;
            }
            // return Array
            $profileResponse['profile'] = $rsMemberProfile;
            $profileResponse['chatList'] = $memberChatList;
        } catch (Exception $e) {
            return $res->withJson($api->callError(98));
        }
        // Response
        return $res->withJson($api->callResponse($profileResponse), 200, JSON_NUMERIC_CHECK);
    }

    /* ******************************
     * 회원 프로필 정보 조회 (V2)
     * [GET] /aiinz/v2/members/mypage/{memberSeq}
     * ******************************/
    public function getMemberProfileV2($req, $res)
    {
        // CLS 선언
        $api = new Api;
        $member = new Member;
        $post = new Post;
        $chat = new Chat;

        // Request
        $memberSeq = $req->getAttribute('memberSeq');
        // 필수 파라미터 누락
        if ($memberSeq == null || '') {
            return $res->withJson($api->callError(51));
        }

        try {
            // 회원 프로필 조회
            $memberProfile = $member->getMemberProfile($memberSeq);
            // PDO Exception Error
            if (!$memberProfile && gettype($memberProfile) == 'boolean') {
                return $res->withJson($api->callError(99));
            }

            // 회원 게시글 조회
            $memberPostList = $post->getMemberPostsV2($memberSeq);
            // PDO Exception Error
            if (!$memberPostList && gettype($memberPostList) == 'boolean') {
                return $res->withJson($api->callError(99));
            }

            // 회원 채팅 목록 조회
            $memberChatList = $chat->getChatList($memberSeq);
            // PDO Exception Error
            if (!$memberChatList && gettype($memberChatList) == 'boolean') {
                return $res->withJson($api->callError(99));
            }

            $rsMemberProfile = $memberProfile;
            if ($memberProfile != null) {
                // 회원 작성 게시글 개수
                $rsMemberProfile->postsCount = count($memberPostList);
                $rsMemberProfile->postsList = $memberPostList;
            }
            // return Array
            $profileResponse['profile'] = $rsMemberProfile;
            $profileResponse['chatList'] = $memberChatList;
        } catch (Exception $e) {
            return $res->withJson($api->callError(99));
        }
        // Response
        return $res->withJson($api->callResponse($profileResponse), 200, JSON_NUMERIC_CHECK);
    }

    /* ******************************
     * 팔로우 구분 조회
     * [GET] /aiinz/members/followtype
     * ******************************/
    public function getMemberFollowtype($req, $res)
    {
        // CLS 선언
        $api = new Api;
        $member = new Member;

        // Request
        $memberSeq = $req->getQueryParam('memberSeq');
        $followMemberSeq = $req->getQueryParam('followMemberSeq');
        // 필수 파라미터 누락
        if (($memberSeq == null || '') || ($followMemberSeq == null || '')) {
            return $res->withJson($api->callError(51));
        }

        try {
            // 팔로우 구분 조회
            $followType = $member->getMemberFollowtype($memberSeq, $followMemberSeq);
            // PDO Exception Error
            if (!$followType) {
                return $res->withJson($api->callError(99));
            }
            $result['followType'] = $followType;
        } catch (Exception $e) {
            return $res->withJson($api->callError(98));
        }
        // Response
        return $res->withJson($api->callResponse($followType), 200, JSON_NUMERIC_CHECK);
    }

    /* ******************************
     * 프로필 사진 수정
     * [PUT] /aiinz/members/mypage/{memberSeq}/profile
     * ******************************/
    public function updateMemberProfile($req, $res)
    {
        // CLS 선언
        $api = new Api;
        $member = new Member;

        // Request
        $memberSeq = $req->getAttribute('memberSeq');
        $profileImagePath = $req->getParam('profileImagePath');
        // 필수 파라미터 누락
        if (($memberSeq == null || '') || ($profileImagePath == null || '')) {
            return $res->withJson($api->callError(51));
        }

        try {
            // 프로필 사진 수정
            $result = $member->updateMemberProfile($memberSeq, $profileImagePath);
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
     * 특정회원 팔로우
     * [POST] /aiinz/members/follow
     * ******************************/
    public function insertMemberFollow($req, $res)
    {
        // CLS 선언
        $api = new Api;
        $member = new Member;
        $activity = new Activity;
        $push = new Push;

        // Request
        $toMemberSeq = $req->getParam('memberSeq');
        $fromMemberSeq = $req->getParam('followMemberSeq');
        // 필수 파라미터 누락
        if (($toMemberSeq == null || '') || ($fromMemberSeq == null || '')) {
            return $res->withJson($api->callError(51));
        }

        try {
            // 회원 팔로우 여부 확인
            $duplicateCount = (int)$member->getMemberFollowCount($toMemberSeq, $fromMemberSeq);
            if ($duplicateCount > 0) {
                return $res->withJson($api->callError(90, 'Already follow'));
            }

            // 회원 팔로우 등록
            $insertFollow = $member->insertMemberFollow($toMemberSeq, $fromMemberSeq);
            // PDO Exception Error
            if (!$insertFollow) {
                return $res->withJson($api->callError(99));
            }

            // 팔로우를 요청한 회원 닉네임
            $toMemberNickname = $member->getMemberProfile($toMemberSeq);
            $toMemberNickname = $toMemberNickname->nickName;

            // Activity, Push Contents
            $activityMessage = $toMemberNickname . "님이 회원님을 팔로우하기 시작했습니다.";

            /* ****************
             * 팔로우 이력 저장
             * ****************/
            $activity->insertActivity('follow', $activityMessage, $toMemberSeq, $fromMemberSeq);

            /* ****************
             * PUSH 발송
            * ****************/
            // 팔로우 대상 회원 FcmToken 조회
            $fcmToken = $member->getMemberFcmToken($fromMemberSeq);
            // Push 발송
            $result = $push->sendPush($fcmToken, $activityMessage);
        } catch (Exception $e) {
            return $res->withJson($api->callError(98));
        }
        // Response
        return $res->withJson($api->callResponse($result), 200, JSON_NUMERIC_CHECK);
    }

    /* ******************************
     * 특정회원 팔로우 해제
     * [DELETE] /aiinz/members/follow
     * ******************************/
    public function deleteMemberFollow($req, $res)
    {
        // CLS 선언
        $api = new Api;
        $member = new Member;
        // Request
        $memberSeq = $req->getParam('memberSeq');
        $followMemberSeq = $req->getParam('followMemberSeq');

        // 필수 파라미터 누락
        if (($memberSeq == null || '') || ($followMemberSeq == null || '')) {
            return $res->withJson($api->callError(51));
        }

        try {
            // 회원 팔로우 여부 확인
            $duplicateCount = (int)$member->getMemberFollowCount($memberSeq, $followMemberSeq);

            // 이미 팔로우 해제 된 경우
            if ($duplicateCount == 0) {
                return $res->withJson($api->callError(90, 'Unfollow already'));
            }

            // 회원 팔로우 해제
            $deleteFollow = $member->deleteMemberFollow($memberSeq, $followMemberSeq);
            // PDO Exception Error
            if (!$deleteFollow) {
                return $res->withJson($api->callError(99, 'fail'));
            }
        } catch (Exception $e) {
            return $res->withJson($api->callError(98));
        }
        // Response
        return $res->withJson($api->callResponse($deleteFollow), 200, JSON_NUMERIC_CHECK);
    }

    /* ******************************
     * 회원의 팔로워 목록 조회
     * [GET] /aiinz/members/{memberSeq}/follower
     * ******************************/
    public function getMemberFollower($req, $res)
    {
        // CLS 선언
        $api = new Api;
        $member = new Member;

        // Request
        $memberSeq = $req->getAttribute('memberSeq');
        // 필수 파라미터 누락
        if ($memberSeq == null || '') {
            return $res->withJson($api->callError(51));
        }

        try {
            // 회원 팔로워 목록 조회
            $memberFollow = $member->getMemberFollower($memberSeq);
            // PDO Exception Error
            if (!$memberFollow && gettype($memberFollow) == 'boolean') {
                return $res->withJson($api->callError(99));
            }
            $result['followerList'] = $memberFollow;
        } catch (Exception $e) {
            return $res->withJson($api->callError(98));
        }
        // Response
        return $res->withJson($api->callResponse($result), 200, JSON_NUMERIC_CHECK);
    }

    /* ******************************
     * 회원의 팔로잉 목록 조회
     * [GET] /aiinz/members/{memberSeq}/following
     * ******************************/
    public function getMemberFollowing($req, $res)
    {
        // CLS 선언
        $api = new Api;
        $member = new Member;

        // Request
        $memberSeq = $req->getAttribute('memberSeq');
        // 필수 파라미터 누락
        if ($memberSeq == null || '') {
            return $res->withJson($api->callError(51));
        }

        try {
            // 회원 팔로잉 목록 조회
            $memberFollowing = $member->getMemberFollowing($memberSeq);
            // PDO Exception Error
            if (gettype($memberFollowing) == 'boolean' && !$memberFollowing) {
                return $res->withJson($api->callError(99));
            }
            $result['followingList'] = $memberFollowing;
        } catch (Exception $e) {
            return $res->withJson($api->callError(98));
        }
        // Response
        return $res->withJson($api->callResponse($result), 200, JSON_NUMERIC_CHECK);
    }

    /* ******************************
     * 회원의 FCM 토큰을 업데이트
     * [PUT] /aiinz/members/{memberSeq}/fcmtoken
     * ******************************/
    public function updateMemberFcmToken($req, $res)
    {
        // CLS 선언
        $api = new Api;
        $member = new Member;

        // Request
        $memberSeq = $req->getAttribute('memberSeq');
        $fcmToken = $req->getParam('fcmToken');
        // 필수 파라미터 누락
        if (($memberSeq == null || '') || ($fcmToken == null || '')) {
            return $res->withJson($api->callError(51));
        }

        try {
            // 회원 FCM 토큰 업데이트
            $result = $member->updateMemberFcmToken($memberSeq, $fcmToken);
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
}