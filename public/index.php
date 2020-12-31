<?php
session_start();

header("Content-Type: application/json; charset=UTF-8");

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require_once '/home/ec2-user/vendor/autoload.php';
require_once '../src/config/db.php';
require_once '../src/config/api.php';

require_once '../src/controller/MemberController.php';
require_once '../src/controller/PostController.php';
require_once '../src/controller/ChatController.php';
require_once '../src/controller/ActivityController.php';
require_once '../src/controller/PushController.php';

$app = new \Slim\App;

$app->group('/aiinz', function (\Slim\App $app) {

    /**
     * 회원
     **/
    $app->group('/members', function (\Slim\App $app) {
        /* 회원가입 :  POST /aiinz/members/member */
        $app->post('/member', \MemberController::class . ':insertMember');

        /* 닉네임 회원 조회 (부분 일치) :  GET /aiinz/members/nickname/{nickName} */
        $app->get('/nickname/{nickName}', \MemberController::class . ':getMemberByNickname');

        /* 전화번호 또는 닉네임으로 회원 존재여부 조회 :  GET /aiinz/members/member/exists */
        $app->get('/member/exists', \MemberController::class . ':getMemberExists');

        /* 회원 프로필 정보 조회 :  GET /aiinz/members/mypage/{memberSeq} */
        $app->get('/mypage/{memberSeq}', \MemberController::class . ':getMemberProfile');

        /* 팔로우 구분 조회 :  GET /aiinz/members/followtype */
        $app->get('/followtype', \MemberController::class . ':getMemberFollowtype');

        /* 프로필 사진 수정 :  PUT /aiinz/members/mypage/{memberSeq}/profile */
        $app->put('/mypage/{memberSeq}/profile', \MemberController::class . ':updateMemberProfile');

        /* 특정회원 팔로우 :  POST /aiinz/members/follow */
        $app->post('/follow', \MemberController::class . ':insertMemberFollow');

        /* 특정회원 팔로우 해제 :  DELETE /aiinz/members/follow */
        $app->delete('/follow', \MemberController::class . ':deleteMemberFollow');

        /* 회원의 팔로워 목록 조회 : GET /aiinz/members/{memberSeq}/follower */
        $app->get('/{memberSeq}/follower', \MemberController::class . ':getMemberFollower');

        /* 회원의 팔로잉 목록 조회 : GET /aiinz/members/{memberSeq}/following */
        $app->get('/{memberSeq}/following', \MemberController::class . ':getMemberFollowing');

        /* 회원의 FCM 토큰을 업데이트 : PUT /aiinz/members/{memberSeq}/fcmtoken */
        $app->put('/{memberSeq}/fcmtoken', \MemberController::class . ':updateMemberFcmToken');

    });

    /**
     * 게시물
     **/
    $app->group('/posts', function (\Slim\App $app) {
        /* 게시물 목록 조회 (타임라인 v1) : GET /aiinz/posts/timeline */
        $app->get('/timeline', \PostController::class . ':getPostsList');

        /* 게시물 조회 : GET /aiinz/posts/{postSeq} */
        $app->get('/{postSeq:[0-9]+}', \PostController::class . ':getPosts');

        /* 전체 게시물 랜덤 조회 :  GET /aiinz/posts/random */
        $app->get('/random', \PostController::class . ':getPostsRandom');

        /* 검색어 해당 해시태그 조회 :  GET /aiinz/posts/search/hashtag */
        $app->get('/search/hashtag', \PostController::class . ':getPostsHashtagList');

        /* 해시태그에 해당하는 게시물 목록 조회 :  GET /aiinz/posts/fromhashtag */
        $app->get('/fromhashtag', \PostController::class . ':getPostsFromHashtagList');

        /* 게시물 등록 : POST /aiinz/posts/post */
        $app->post('/post', \PostController::class . ':insertPosts');

        /* 게시물 수정 : PUT /aiinz/posts/{postSeq} */
        $app->put('/{postSeq:[0-9]+}', \PostController::class . ':updatePost');

        /* 게시물 삭제 : DELETE /aiinz/posts/{postSeq} */
        $app->delete('/{postSeq:[0-9]+}', \PostController::class . ':deletePost');

        /* 게시물 좋아요 : POST /aiinz/posts/likes */
        $app->post('/likes', \PostController::class . ':insertPostsLikes');

        /* 게시물 좋아요 취소 : DELETE /aiinz/posts/likes */
        $app->delete('/likes', \PostController::class . ':deletePostsLikes');

        /* 게시물 댓글 조회 : GET /aiinz/posts/{postSeq}/reply */
        $app->get('/{postSeq:[0-9]+}/reply', \PostController::class . ':getPostsReply');

        /* 게시물 댓글 등록 : POST /aiinz/posts/{postSeq}/reply */
        $app->post('/{postSeq:[0-9]+}/reply', \PostController::class . ':insertPostsReply');

        /* 게시물 댓글 삭제 : DELETE /aiinz/posts/reply/{replySeq} */
        $app->delete('/reply/{replySeq:[0-9]+}', \PostController::class . ':deletePostsReply');

        /* 특정 회원 이미지 게시글 조회 : GET /aiinz/posts/imagePosts?memberSeq={memberSeq} */
        $app->get('/imagePosts', \PostController::class . ':getImagePosts');

        /* 특정 회원 텍스트 게시글 조회 : GET /aiinz/posts/{memberSeq}/textPosts?memberSeq={memberSeq} */
        $app->get('/textPosts', \PostController::class . ':getTextPosts');

        /* 특정 회원 게시물 중 클릭 게시글이 상단에 존재하는 타임라인
        : GET /aiinz/posts/{postSeq}?memberSeq={memberSeq} */
        $app->get('/standard/{postSeq:[0-9]+}', \PostController::class . ':getStandardPosts');
    });


    /**
     * 채팅
     **/
    $app->group('/chat', function (\Slim\App $app) {
        /* 채팅방 정보 등록 : POST /aiinz/chat */
        $app->post('', \ChatController::class . ':insertChat');

        /* 채팅방 정보 조회 : GET /aiinz/chat/{chatSeq} */
        $app->get('/{chatSeq}', \ChatController::class . ':getChat');

        /* 채팅방 회원의 메시지 위치값을 업데이트 : PUT /aiinz/chat/{chatSeq}/message/index */
        $app->put('/{chatSeq}/message/index', \ChatController::class . ':updateMemberChatMessageIndex');

        /* 나간 채팅방 회원의 outMemberSeq 되돌리기 : PUT /aiinz/chat/{chatSeq}/message/outMember */
        $app->put('/{chatSeq}/message/outMember', \ChatController::class . ':updateChatOutMemberSeq');

        /* 채팅방 정보 삭제 : DELETE /aiinz/chat/{chatSeq} */
        $app->delete('/{chatSeq}', \ChatController::class . ':deleteChat');

        /* 채팅방 존재 여부 확인 : GET /aiinz/chat/{memberSeq}/seq */
        $app->get('/{memberSeq:[0-9]+}/seq', \ChatController::class . ':getChatSeq');
    });


    /**
     * 활동 이력
     **/
    $app->group('/activity', function (\Slim\App $app) {
        /* 활동 이력 조회 : GET /aiinz/activity/{memberSeq} */
        $app->get('/{memberSeq:[0-9]+}', \ActivityController::class . ':getActivityList');
    });


    /**
     * PUSH
     **/
    $app->group('/push', function (\Slim\App $app) {
        /* 채팅 알림 푸시 보내기 : POST /aiinz/push/chat */
        $app->post('/chat', \PushController::class . ':sendChatPush');
    });


    /**
     * V2
     **/
    $app->group('/v2', function (\Slim\App $app) {

        /* 회원 */
        $app->group('/members', function (\Slim\App $app) {
            /* 회원 프로필 정보조회 v2 : GET /aiinz/v2/members/mypage/{memberSeq}*/
            $app->get('/mypage/{memberSeq}', \MemberController::class . ':getMemberProfileV2');
        });

        /* 게시물 */
        $app->group('/posts', function (\Slim\App $app) {
            /* 게시물 목록 조회 (타임라인 v2) : GET /aiinz/v2/posts/timeline */
            $app->get('/timeline', \PostController::class . ':getPostsListV2');

            /* 게시물 조회 v2 : GET /aiinz/v2/posts/{postSeq} */
            $app->get('/{postSeq:[0-9]+}', \PostController::class . ':getPostsV2');

            /* 전체 게시물 랜덤 조회 v2 : GET /aiinz/v2/posts/random */
            $app->get('/random', \PostController::class . ':getPostsRandomV2');

            /* 해시태그에 해당하는 게시물 목록 조회 v2 :  GET /aiinz/v2/posts/fromhashtag */
            $app->get('/fromhashtag', \PostController::class . ':getPostsFromHashtagListV2');
        });
    });

    /**
     * V3
     **/
    $app->group('/v3', function (\Slim\App $app) {
        /* 게시물 */
        $app->group('/posts', function (\Slim\App $app) {
            /* 전체 게시물 랜덤 조회 v3 : GET /aiinz/v3/posts/random */
            $app->get('/random', \PostController::class . ':getPostsRandomV3');
            $app->get('/randomview', \PostController::class . ':getPostsRandomView');
        });
    });


});
$app->run();