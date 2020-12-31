<?php

use Psr\Container\ContainerInterface;

require_once '../src/objects/Post.php';
require_once '../src/objects/Member.php';

class PostController
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /* ******************************
     * 게시물 목록 조회 (타임라인 v1)
     * [GET] /aiinz/posts/timeline?memberSeq={memberSeq}&postSeq={postSeq}
     * ******************************/
    public function getPostsList($req, $res)
    {
        // CLS 선언
        $api = new Api;
        $post = new Post;

        // Request
        $memberSeq = $req->getQueryParam('memberSeq');
        $postSeq = $req->getQueryParam('postSeq');
        // memberSeq가 넘어오지 않으면 51 Error
        if ($memberSeq == null) {
            return $res->withJson($api->callError(51));
        }

        try {
            // 게시물 목록 조회
            $query_result = $post->getPostsList($memberSeq, $postSeq);
            // PDO Exception Error
            if (!$query_result && gettype($query_result) == 'boolean') {
                return $res->withJson($api->callError(99));
            }
            $result['postsList'] = $query_result;
        } catch (Exception $e) {
            return $res->withJson($api->callError(98));
        }
        // Response
        return $res->withJson($api->callResponse($result), 200, JSON_NUMERIC_CHECK);
    }

    /* ******************************
     * 게시물 목록 조회 (타임라인 v2)
     * [GET] /aiinz/v2/posts/timeline?memberSeq={memberSeq}&postSeq={postSeq}
     * ******************************/
    public function getPostsListV2($req, $res)
    {
        // CLS 선언
        $api = new Api;
        $post = new Post;

        // Request
        $memberSeq = $req->getQueryParam('memberSeq');
        $postSeq = $req->getQueryParam('postSeq');
        // memberSeq가 넘어오지 않으면 51 Error
        if ($memberSeq == null) {
            return $res->withJson($api->callError(51));
        }

        try {
            // 게시물 목록 조회
            $query_result = $post->getPostsListV2($memberSeq, $postSeq);
            // PDO Exception Error
            if (!$query_result && gettype($query_result) == 'boolean') {
                return $res->withJson($api->callError(99));
            }
            $result['postsList'] = $query_result;
        } catch (Exception $e) {
            return $res->withJson($api->callError(98));
        }
        // Response
        return $res->withJson($api->callResponse($result), 200, JSON_NUMERIC_CHECK);
    }


    /* ******************************
     * 게시물 조회 (v1)
     * [GET] /aiinz/posts/{postSeq}?memberSeq={memberSeq}
     * ******************************/
    public function getPosts($req, $res)
    {
        // CLS 선언
        $api = new Api;
        $post = new Post;

        // Request
        $postSeq = $req->getAttribute('postSeq');
        $memberSeq = $req->getQueryParam('memberSeq');
        // memberSeq와 $postSeq가 넘어오지 않으면 51 Error
        if ($memberSeq == null || $postSeq == null) {
            return $res->withJson($api->callError(51));
        }

        try {
            // 게시물 조회
            $postList = $post->getPosts($memberSeq, $postSeq);
            if (!$postList) {
                return $res->withJson($api->callError(99));
            }
            $postResult = get_object_vars($postList[0]);

            // 게시물 댓글 조회
            $replyList = $post->getReplyList($postSeq);

            // 게시물 hashTag 조회
            $hasTagList = $post->getPostsHashtag($postSeq);

            // 게시물 imageList 조회
            $imageList = $post->getPostsImagesList($postSeq);

            // 게시물 memberHashTag 조회
            $memberTagList = $post->getPostsMemberTagList($postSeq);

            // post_result, replyList, hasTagList, imageList, memberTagList => result로 병합
            $postResult['postsReplyList'] = $replyList;
            $postResult['postHashtagList'] = $hasTagList;
            $postResult['postsImagesList'] = $imageList;
            $postResult['postsMemberTagList'] = $memberTagList;
            $result['posts'] = $postResult;
        } catch (Exception $e) {
            return $res->withJson($api->callError(98));
        }
        // Response
        return $res->withJson($api->callResponse($result), 200, JSON_NUMERIC_CHECK);
    }

    /* ******************************
     * 게시물 조회 (v2)
     * [GET] /aiinz/v2/posts/{postSeq}
     * ******************************/
    public function getPostsV2($req, $res)
    {
        // CLS 선언
        $api = new Api;
        $post = new Post;

        // Request
        $postSeq = $req->getAttribute('postSeq');
        $memberSeq = $req->getQueryParam('memberSeq');
        // memberSeq와 $postSeq가 넘어오지 않으면 51 Error
        if ($memberSeq == null || $postSeq == null) {
            return $res->withJson($api->callError(51));
        }

        try {
            // 게시물 조회
            $postList = $post->getPostsV2($memberSeq, $postSeq);
            if (!$postList) {
                return $res->withJson($api->callError(99));
            }
            $postResult = get_object_vars($postList[0]);

            // 게시물 댓글 조회
            $replyList = $post->getReplyList($postSeq);

            // 게시물 hashTag 조회
            $hasTagList = $post->getPostsHashtag($postSeq);

            // 게시물 imageList 조회
            $imageList = $post->getPostsImagesList($postSeq);

            // 게시물 memberHashTag 조회
            $memberTagList = $post->getPostsMemberTagList($postSeq);

            // post_result, replyList, hasTagList, imageList, memberTagList => result로 병합
            $postResult['postsReplyList'] = $replyList;
            $postResult['postsHashtagList'] = $hasTagList;
            $postResult['postsImagesList'] = $imageList;
            $postResult['postsMemberTagList'] = $memberTagList;
            $result['posts'] = $postResult;
        } catch (Exception $e) {
            return $res->withJson($api->callError(98));
        }
        // Response
        return $res->withJson($api->callResponse($result), 200, JSON_NUMERIC_CHECK);
    }

    /* ******************************
     * 전체 게시물 랜덤 조회
     * [GET] /aiinz/posts/random
     * ******************************/
    public function getPostsRandom($req, $res)
    {
        // CLS 선언
        $api = new Api;
        $post = new Post;

        try {
            // 전체 게시물 랜덤 조회
            $query_result = $post->getPostsRandom();
            // Exception Error
            if (!$query_result && gettype($query_result) == 'boolean') {
                return $res->withJson($api->callError(99));
            }
            $result['postsList'] = $query_result;
        } catch (Exception $e) {
            return $res->withJson($api->callError(98));
        }
        // Response
        return $res->withJson($api->callResponse($result), 200, JSON_NUMERIC_CHECK);
    }

    /* ******************************
     * 전체 게시물 랜덤 조회 (v2)
     * [GET] /aiinz/posts/random
     * ******************************/
    public function getPostsRandomV2($req, $res)
    {
        // CLS 선언
        $api = new Api;
        $post = new Post;

        try {
            // 전체 게시물 랜덤 조회
            $query_result = $post->getPostsRandomV2();
            // Exception Error
            if (!$query_result && gettype($query_result) == 'boolean') {
                return $res->withJson($api->callError(99));
            }
            $result['postsRandomList'] = $query_result;
        } catch (Exception $e) {
            return $res->withJson($api->callError(98));
        }
        // Response
        return $res->withJson($api->callResponse($result), 200, JSON_NUMERIC_CHECK);
    }

    /* ******************************
     * 전체 게시물 랜덤 조회 (v3)
     * [GET] /aiinz/posts/random
     * ******************************/
    public function getPostsRandomV3($req, $res)
    {
        // CLS 선언
        $api = new Api;
        $post = new Post;

        $memberSeq = $req->getParam('memberSeq');
        $refresh = $req->getParam('refresh');

        if ($memberSeq == null || '') {
            return $res->withJson($api->callError(51));
        }

        try {
            // 전체 게시물 랜덤 조회
            $query_result = $post->getPostsRandomV3($memberSeq, $refresh);

            // Exception Error
            if (!$query_result && gettype($query_result) == 'boolean') {
                return $res->withJson($api->callError(99));
            }
            $result['contents'] = $query_result;
        } catch (Exception $e) {
            return $res->withJson($api->callError(98));
        }
        // Response
        return $res->withJson($api->callResponse($result), 200, JSON_NUMERIC_CHECK);
    }

    /* ******************************
     * 전체 랜덤 게시글 클릭 (v3)
     * [GET] /aiinz/posts/random
     * ******************************/
    public function getPostsRandomView($req, $res)
    {
        // CLS 선언
        $api = new Api;
        $post = new Post;

        $postSeq = $req->getQueryParam('postSeq');
        $memberSeq = $req->getParam('memberSeq');

        try {
            // 전체 게시물 랜덤 조회
            $query_result = $post->getPostsRandomView($memberSeq, $postSeq);

            // Exception Error
            if (!$query_result && gettype($query_result) == 'boolean') {
                return $res->withJson($api->callError(99));
            }
            $result['contents'] = $query_result;
        } catch (Exception $e) {
            return $res->withJson($api->callError(98));
        }
        // Response
        return $res->withJson($api->callResponse($result), 200, JSON_NUMERIC_CHECK);
    }

    /* ******************************
     * 검색어 해당 해시태그 조회
     * [GET] /aiinz/posts/search/hashtag
     * ******************************/
    public function getPostsHashtagList($req, $res)
    {
        // CLS 선언
        $api = new Api;
        $post = new Post;

        // Request
        $searchKeyword = $req->getQueryParam('searchKeyword');
        // searchKeyword가 넘어오지 않으면 51 Error
        if ($searchKeyword == null) {
            return $res->withJson($api->callError(51));
        }

        try {
            // 검색어 해당 해시태그 조회
            $result['hashTagList'] = $post->getPostsHashtagList($searchKeyword);
        } catch (Exception $e) {
            return $res->withJson($api->callError(98));
        }
        // Response
        return $res->withJson($api->callResponse($result), 200, JSON_NUMERIC_CHECK);
    }


    /* ******************************
     * 해시태그에 해당하는 게시물 목록 조회
     * [GET] /aiinz/posts/fromhashtag?hashtag={hashtag}
     * ******************************/
    public function getPostsFromHashtagList($req, $res)
    {
        // CLS 선언
        $api = new Api;
        $post = new Post;

        // Request
        $hashTag = $req->getQueryParam('hashtag');
        // hashtag 넘어오지 않으면 51 Error
        if ($hashTag == null) {
            return $res->withJson($api->callError(51));
        }

        try {
            // 해시태그 해당 게시글 조회
            $postList = $post->getPostsFromHashtagList($hashTag);

            // usedPostsCount (해당 태그가 사용된 포스트의 수) 를 만드는 쿼리 생성
            $usedPostsCount = count($postList);

            // Exception Error
            if (!$postList && gettype($postList) == 'boolean') {
                return $res->withJson($api->callError(99));
            }

            // PostList, usedPostsCount => result로 병합
            $result['postList'] = $postList;
            $result['usedPostsCount'] = $usedPostsCount;
        } catch (Exception $e) {
            return $res->withJson($api->callError(98));
        }
        // Response
        return $res->withJson($api->callResponse($result), 200, JSON_NUMERIC_CHECK);
    }

    /* ******************************
     * 해시태그에 해당하는 게시물 목록 조회 (v2)
     * [GET] /aiinz/v2/posts/fromhashtag?hashtag={hashtag}
     * ******************************/
    public function getPostsFromHashtagListV2($req, $res)
    {
        // CLS 선언
        $api = new Api;
        $post = new Post;

        // Request
        $hashTag = $req->getQueryParam('hashtag');
        // hashtag 넘어오지 않으면 51 Error
        if ($hashTag == null) {
            return $res->withJson($api->callError(51));
        }

        try {
            // 해시태그에 해당하는 게시물 목록 조회
            $postList = $post->getPostsFromHashtagListV2($hashTag);

            // usedPostsCount (해당 태그가 사용된 포스트의 수) 를 만드는 쿼리 생성
            $usedPostsCount = count($postList);

            // Exception Error
            if (!$postList && gettype($postList) == 'boolean') {
                return $res->withJson($api->callError(99));
            }

            // PostList, usedPostsCount => result로 병합
            $result['postList'] = $postList;
            $result['usedPostsCount'] = $usedPostsCount;
        } catch (Exception $e) {
            return $res->withJson($api->callError(98));
        }
        // Response
        return $res->withJson($api->callResponse($result), 200, JSON_NUMERIC_CHECK);
    }

    /* ******************************
     * 게시물 등록
     * [POST] /aiinz/posts/post
     * ******************************/
    public function insertPosts($req, $res)
    {
        // CLS 선언
        $api = new Api;
        $post = new Post;
        $member = new Member;
        $push = new Push;

        // Request
        $memberSeq = $req->getParam('memberSeq');
        $postContents = $req->getParam('postContents');
        $hashTagList = $req->getParam('postsHashtagList');
        $imagesList = $req->getParam('postsImagesList');
        $memberTagList = $req->getParam('postsMemberTagList');
        // 필수 파라미터 누락
        if ($memberSeq == null) {
            return $res->withJson($api->callError(51));
        }

        try {
            // 게시물 등록
            $insertPost = $post->insertPost($memberSeq, $postContents); // 저장시 lastId return
            if (!$insertPost)
                return $res->withJson($api->callError(99, 'posts insert error has occurred'));

            // 게시물 이미지 등록
            if ($imagesList) {
                $insertPostImages = $post->insertPostImages($insertPost, $imagesList);
                if (!$insertPostImages)
                    return $res->withJson($api->callError(97, 'posts images insert error has occurred'));
            }

            if (!empty($hashTagList)) {
                // 게시물 해시태그 등록
                $insertPostHashTag = $post->insertPostHashTag($insertPost, $hashTagList);
                if (!$insertPostHashTag)
                    return $res->withJson($api->callError(98, 'posts hashtag insert error has occurred'));
            }

            if (!empty($memberTagList)) {
                $nickName = $member->getMemberProfile($memberSeq)->nickName;
                // 게시물 사용자 태그 등록
                $insertPostMemberTag = $post->insertPostMemberTag($insertPost, $memberTagList);
                if (!$insertPostMemberTag)
                    return $res->withJson($api->callError(98, 'posts member tag insert error has occurred'));
                // PUSH 전송
                for ($i = 0; $i < count($memberTagList); $i++) {
                    $fcmMemberSeq = ($member->getMemberSeq('', $memberTagList[$i]['nickName']))->memberSeq;
                    // 내가 아닐 경우에만 전송함
                    if ($memberSeq != $fcmMemberSeq && $fcmMemberSeq) {
                        $fcmToken = $member->getMemberFcmToken($fcmMemberSeq);
                        $activityMessage = $nickName . '님이 게시글에 회원님을 태그했습니다.';
                        $push->sendPush($fcmToken, $activityMessage);
                    }
                }
            }
        } catch (Exception $e) {
            return $res->withJson($api->callError(98));
        }
        // Response
        return $res->withJson($api->callResponse(true), 200, JSON_NUMERIC_CHECK);
    }

    /* ******************************
     * 게시물 좋아요
     * [POST] /aiinz/posts/likes
     * ******************************/
    public function insertPostsLikes($req, $res)
    {
        // CLS 선언
        $api = new Api;
        $post = new Post;
        $member = new Member;
        $activity = new Activity;
        $push = new Push;

        // Request
        $memberSeq = $req->getParam('memberSeq');
        $postSeq = $req->getParam('postSeq');
        // 필수 파라미터 누락
        if ($memberSeq == null || $postSeq == null) {
            return $res->withJson($api->callError(51));
        }

        try {
            // 이미 게시물 좋아요 했는지 조회
            $getPostsLikesCount = $post->getPostsLikesCount($memberSeq, $postSeq)[0]->cnt;
            if ($getPostsLikesCount > 0) {
                return $res->withJson($api->callError(90, 'fail'));
            }

            // 게시물 좋아요 등록
            $insertPostsLikes = $post->insertPostsLikes($memberSeq, $postSeq);
            if (!$insertPostsLikes) {
                return $res->withJson($api->callError(99, 'fail'));
            }

            // 게시글 작성자 조회
            $postWriter = $post->getPostsMember($postSeq)[0]->memberSeq;

            $result = true;
            // 게시글 작성자 != 좋아요 시도 회원이 같지 않은 경우에만 PUSH
            if ($postWriter != $memberSeq) {
                // 좋아요한 회원 조회
                $toMemberSeq = $memberSeq;
                $nickName = $member->getMemberProfile($memberSeq)->nickName;

                // Activity, Push Contents
                $activityMessage = $nickName . "님이 회원님의 게시물을 좋아합니다.";

                // 게시물 작성자 조회
                $fromMemberSeq = $post->getPostsMember($postSeq)[0]->memberSeq;

                //활동 이력 저장
                $activityLogLastId = $activity->insertActivity('likes', $activityMessage, $toMemberSeq, $fromMemberSeq);
                $activity->insertActivityLikes($activityLogLastId, $postSeq);

                // PUSH 발송
                // 좋아요 대상 회원 FcmToken 조회
                $fcmToken = $member->getMemberFcmToken($fromMemberSeq);

                // Push 발송
                $result = $push->sendPush($fcmToken, $activityMessage);
            }
        } catch (Exception $e) {
            return $res->withJson($api->callError(98));
        }
        // Response
        return $res->withJson($api->callResponse($result), 200, JSON_NUMERIC_CHECK);
    }

    /* ******************************
     * 게시물 좋아요 취소
     * [DELETE] /aiinz/posts/likes
     * ******************************/
    public function deletePostsLikes($req, $res)
    {
        // CLS 선언
        $api = new Api;
        $post = new Post;

        // Request
        $memberSeq = $req->getParam('memberSeq');
        $postSeq = $req->getParam('postSeq');
        // 필수 파라미터 누락
        if ($memberSeq == null || $postSeq == null) {
            return $res->withJson($api->callError(51));
        }

        try {
            // 이미 게시물 좋아요 했는지 조회
            $getPostsLikesCount = $post->getPostsLikesCount($memberSeq, $postSeq)[0]->cnt;
            if ($getPostsLikesCount == 0) {
                return $res->withJson($api->callError(90, 'fail'));
            }

            // 게시물 좋아요 취소
            $deletePostsLikes = $post->deletePostsLikes($memberSeq, $postSeq);
            if (!$deletePostsLikes) {
                return $res->withJson($api->callError(99, 'fail'));
            }
        } catch (Exception $e) {
            return $res->withJson($api->callError(98));
        }
        // Response
        return $res->withJson($api->callResponse($deletePostsLikes), 200, JSON_NUMERIC_CHECK);
    }

    /* ******************************
     * 게시물 댓글 조회
     * [GET] /aiinz/posts/{postSeq}/reply
     * ******************************/
    public function getPostsReply($req, $res)
    {
        // CLS 선언
        $api = new Api;
        $post = new Post;

        // Request
        $postSeq = $req->getAttribute('postSeq');
        // postSeq가 넘어오지 않으면 51 Error
        if ($postSeq == null) {
            return $res->withJson($api->callError(51));
        }

        try {
//            $result = array('replyList' => $post->getReplyList($postSeq));
            $result['replyList'] = $post->getReplyList($postSeq);
        } catch (Exception $e) {
            return $res->withJson($api->callError(98));
        }
        // Response
        return $res->withJson($api->callResponse($result), 200, JSON_NUMERIC_CHECK);
    }

    /* ******************************
     * 게시물 댓글 등록
     * [POST] /aiinz/posts/{postSeq}/reply
     * ******************************/
    public function insertPostsReply($req, $res)
    {
        // CLS 선언
        $api = new Api;
        $post = new Post;
        $member = new Member;
        $push = new Push;

        // Request
        $postSeq = $req->getAttribute('postSeq');
        $memberSeq = $req->getParam('memberSeq');
        $replyContents = $req->getParam('replyContents');
        $replyHashtagList = $req->getParam('postsReplyHashtagList');
        $replyMemberTagList = $req->getParam('postsReplyMemberTagList');

        error_log($req->getBody());

        // 필수 파라미터 누락
        if ($postSeq == null || $memberSeq == null || $replyContents == null) {
            return $res->withJson($api->callError(51));
        }

        try {
            // 게시물 댓글 등록
            $insertPostReply = $post->insertPostReply($postSeq, $memberSeq, $replyContents); // 저장시 lastId return
            if (!$insertPostReply)
                return $res->withJson($api->callError(99, 'posts reply insert error has occurred'));

            if ($replyHashtagList) {
                // 댓글 해시태그 등록
                $insertPostHashTag = $post->insertPostReplyHashtag($insertPostReply, $replyHashtagList);
                if (!$insertPostHashTag)
                    return $res->withJson($api->callError(98, 'posts reply hashtag insert error has occurred'));
            }

            if ($replyMemberTagList) {
                // 댓글 회원태깅 등록
                $insertPostMemberTag = $post->insertPostReplyMemberTag($insertPostReply, $replyMemberTagList);
                if (!$insertPostMemberTag)
                    return $res->withJson($api->callError(98, 'posts reply member tag insert error has occurred'));
                $nickName = $member->getMemberProfile($memberSeq)->nickName;
                // PUSH 전송
                for ($i = 0; $i < count($replyMemberTagList); $i++) {
                    $fcmMemberSeq = ($member->getMemberSeq('', $replyMemberTagList[$i]['nickName']))->memberSeq;
                    // 내가 아닐 경우에만 전송함
                    if ($memberSeq != $fcmMemberSeq) {
                        $fcmToken = $member->getMemberFcmToken($fcmMemberSeq);
                        $activityMessage = $nickName . '님이 댓글에서 회원님을 태그했습니다.';
                        $push->sendPush($fcmToken, $activityMessage);
                    }
                }
            }

        } catch (Exception $e) {
            return $res->withJson($api->callError(98));
        }
        // Response
        return $res->withJson($api->callResponse(true), 200, JSON_NUMERIC_CHECK);
    }

    /* ******************************
     * 게시물 댓글 삭제
     * [DELETE] /aiinz/posts/reply/{replySeq}
     * ******************************/
    public function deletePostsReply($req, $res)
    {
        // CLS 선언
        $api = new Api;
        $post = new Post;

        // Request
        $replySeq = $req->getAttribute('replySeq');
        $memberSeq = $req->getQueryParam('memberSeq');
        // 필수 파라미터 누락
        if ($replySeq == null || $memberSeq == null) {
            return $res->withJson($api->callError(51));
        }

        try {
            // 삭제 인증
            $replyWriter = $post->getPostReplyWriter($replySeq)[0]->memberSeq;
            if ($replyWriter == null) // 결과 없을때
                return $res->withJson($api->callError(90, 'no deletion target'));
            if ($memberSeq !== $replyWriter) // 글쓴이와 삭제자가 다를 때
                return $res->withJson($api->callError(52, 'delete authentication failed'));

            // 댓글 해시태그 삭제
            $post->deletePostReplyHashtag($replySeq);

            // 댓글 회원태깅 삭제
            $post->deletePostReplyMemberTag($replySeq);

            // 게시물 댓글 삭제
            $deletePostReply = $post->deletePostReply($replySeq);
            if (!$deletePostReply)
                return $res->withJson($api->callError(99, 'reply delete error has occurred'));
        } catch (Exception $e) {
            return $res->withJson($api->callError(98));
        }
        // Response
        return $res->withJson($api->callResponse(true), 200, JSON_NUMERIC_CHECK);
    }


    /* ******************************
     * 특정 회원 이미지 게시글 조회
     * [GET] /aiinz/posts/imagePosts?memberSeq={memberSeq}
     * ******************************/
    public function getImagePosts($req, $res)
    {
        // CLS 선언
        $api = new Api;
        $post = new Post;

        // Request
        $memberSeq = $req->getQueryParam('memberSeq');
        // postSeq가 넘어오지 않으면 51 Error
        if ($memberSeq == null) {
            return $res->withJson($api->callError(51));
        }

        try {
            $result['imagePosts'] = $post->getImagePosts($memberSeq);
        } catch (Exception $e) {
            return $res->withJson($api->callError(98));
        }
        // Response
        return $res->withJson($api->callResponse($result), 200, JSON_NUMERIC_CHECK);
    }

    /* ******************************
     * 특정 회원 텍스트 게시글 조회
     * [GET] /aiinz/posts/textPosts?memberSeq={memberSeq}
     * ******************************/
    public function getTextPosts($req, $res)
    {
        // CLS 선언
        $api = new Api;
        $post = new Post;

        // Request
        $memberSeq = $req->getQueryParam('memberSeq');
        // postSeq가 넘어오지 않으면 51 Error
        if ($memberSeq == null) {
            return $res->withJson($api->callError(51));
        }

        try {
            $result['textPosts'] = $post->getTextPosts($memberSeq);
        } catch (Exception $e) {
            return $res->withJson($api->callError(98));
        }
        // Response
        return $res->withJson($api->callResponse($result), 200, JSON_NUMERIC_CHECK);
    }

    /* ******************************
     * 특정 회원 게시물 중 클릭 게시글이 상단에 존재하는 타임라인
     * [GET] /aiinz/posts/textPosts?memberSeq={memberSeq}
     * ******************************/
    public function getStandardPosts($req, $res)
    {
        // CLS 선언
        $api = new Api;
        $post = new Post;

        // Request
        $postSeq = $req->getAttribute('postSeq');
        $memberSeq = $req->getQueryParam('memberSeq');
        // postSeq가 넘어오지 않으면 51 Error
        if ($memberSeq == null) {
            return $res->withJson($api->callError(51));
        }

        try {
            $result['contents'] = $post->getStandardPosts($postSeq, $memberSeq);
        } catch (Exception $e) {
            return $res->withJson($api->callError(98));
        }
        // Response
        return $res->withJson($api->callResponse($result), 200, JSON_NUMERIC_CHECK);
    }

    public function updatePost($req, $res)
    {
        $api = new Api;
        $post = new Post;

        $postSeq = $req->getAttribute('postSeq');
        $memberSeq = $req->getParam('memberSeq');
        $postContents = $req->getParam('postContents');
        $hashTagList = $req->getParam('postsHashtagList');
        $imagesList = $req->getParam('postsImagesList');
        $memberTagList = $req->getParam('postsMemberTagList');

        if ($postSeq == null || $memberSeq == null) {
            return $res->withJson($api->callError(51));
        }
        try {
            $postMember = $post->getPostsV2($memberSeq, $postSeq)[0]->memberSeq;
            if ($postMember == null) {
                return $res->withJson($api->callError(90, 'no deletion target'));
            }
            if ($postMember != $memberSeq) {
                return $res->withJson($api->callError(52, 'delete authentication failed'));
            }
            $result = $post->updatePost($postSeq, $postContents);

            if (isset($hashTagList)) {
                $orgHashTagList = $post->getPostsHashtag($postSeq);
                $orgHashTag = [];
                $newHashTag = [];
                foreach ($orgHashTagList as $key => $value) {
                    array_push($orgHashTag, $orgHashTagList[$key]->hashTag);
                }
                foreach ($hashTagList as $key => $value) {
                    array_push($newHashTag, $hashTagList[$key]['hashTag']);
                }

                $deleteHashTag = array_values(array_diff($orgHashTag, $newHashTag));
                if (count($deleteHashTag) > 0) {
                    $post->deletePostHashTag($postSeq, $deleteHashTag);
                }

                $insertHashTag = array_values(array_diff($newHashTag, $orgHashTag));
                if (count($insertHashTag) > 0) {
                    foreach ($insertHashTag as $key => $val) {
                        unset($insertHashTag[$key]);
                        $insertHashTag[$key]['hashTag'] = $val;
                    }
                    $post->insertPostHashTag($postSeq, $insertHashTag);
                }
            }

            if (isset($imagesList)) {
                $orgImageList = $post->getPostsImagesList($postSeq);
                $orgImageArr = [];
                $newImageArr = [];

                foreach ($orgImageList as $key => $value) {
                    array_push($orgImageArr, $orgImageList[$key]->imagePath);
                }

                foreach ($imagesList as $key => $value) {
                    array_push($newImageArr, $imagesList[$key]['imagePath']);
                }

                $deleteImageList = array_values(array_diff($orgImageArr, $newImageArr));
                if (count($deleteImageList) > 0) {
                    $post->deletePostImages($postSeq, $deleteImageList);
                }
                $insertImageList = array_values(array_diff($newImageArr, $orgImageArr));

                if (count($insertImageList) > 0) {
                    foreach ($insertImageList as $key => $val) {
                        unset($insertImageList[$key]);
                        $insertImageList[$key]['imagePath'] = $val;
                    }
                    $post->insertPostImages($postSeq, $insertImageList);
                }
            }

            if (isset($memberTagList)) {
                $orgMemberTagList = $post->getPostsMemberTagList($postSeq);
                $orgMemberTag = [];
                $newMemberTag = [];
                foreach ($orgMemberTagList as $key => $value) {
                    array_push($orgMemberTag, $orgMemberTagList[$key]->nickName);
                }
                foreach ($memberTagList as $key => $value) {
                    array_push($newMemberTag, $memberTagList[$key]['nickName']);
                }

                $deleteMemberTag = array_values(array_diff($orgMemberTag, $newMemberTag));
                if (count($deleteMemberTag) > 0) {
                    $post->deletePostMemberTag($postSeq, $deleteMemberTag);
                }

                $insertMemberTag = array_values(array_diff($newMemberTag, $orgMemberTag));
                if (count($insertMemberTag) > 0) {
                    foreach ($insertMemberTag as $key => $val) {
                        unset($insertMemberTag[$key]);
                        $insertMemberTag[$key]['nickName'] = $val;
                    }
                    $post->insertPostMemberTag($postSeq, $insertMemberTag);
                    // PUSH 전송
                    $member = new Member;
                    $push = new Push;
                    $nickName = $member->getMemberProfile($memberSeq)->nickName;
                    for ($i = 0; $i < count($memberTagList); $i++) {
                        $fcmMemberSeq = ($member->getMemberSeq('', $memberTagList[$i]['nickName']))->memberSeq;
                        // 내가 아닐 경우에만 전송함
                        if ($memberSeq != $fcmMemberSeq && $fcmMemberSeq) {
                            $fcmToken = $member->getMemberFcmToken($fcmMemberSeq);
                            $activityMessage = $nickName . '님이 게시글에 회원님을 태그했습니다.';
                            $push->sendPush($fcmToken, $activityMessage);
                        }
                    }
                }
            }
        } catch (Exception $e) {
            return $res->withJson($api->callError(98));
        }
        // Response
        return $res->withJson($api->callResponse($result), 200, JSON_NUMERIC_CHECK);
    }

    public function deletePost($req, $res)
    {
        $api = new Api;
        $post = new Post;
        $postSeq = $req->getAttribute('postSeq');
        $memberSeq = $req->getQueryParam('memberSeq');

        if ($postSeq == null || $memberSeq == null) {
            return $res->withJson($api->callError(51));
        }
        try {
            $postMember = $post->getPostsV2($memberSeq, $postSeq)[0]->memberSeq;
            if ($postMember == null) {
                return $res->withJson($api->callError(90, 'no deletion target'));
            }
            if ($postMember != $memberSeq) {
                return $res->withJson($api->callError(52, 'delete authentication failed'));
            }
            $result = $post->deletePost($postSeq);
        } catch (Exception $e) {
            return $res->withJson($api->callError(98));
        }
        // Response
        return $res->withJson($api->callResponse($result), 200, JSON_NUMERIC_CHECK);
    }
}