<?php

class Activity
{
    public $activityLogSeq = 0;
    public $activityType = '';
    public $activityMessage = '';
    public $toMemberSeq = 0;
    public $fromMemberSeq = 0;
    public $regDate = '';

    /**
     * 활동 이력 등록
     * @param $activityType 활동타입
     * (follow, likes, cancelfollowing, cancelfollower, reply, membertag, notice, ad)
     * @param $activityMessage 저장메시지
     * @param $toMemberSeq 누가
     * @param $fromMemberSeq 누구에게
     * @return bool
     */
    public function insertActivity($activityType, $activityMessage, $toMemberSeq, $fromMemberSeq)
    {
        $db = new db();
        $query = "INSERT INTO
                ActivityLog (activityType, activityMessage, toMemberSeq, fromMemberSeq)
            VALUES (
                '$activityType'
                , '$activityMessage'
                , '$toMemberSeq'
                , '$fromMemberSeq'
            )";
        $db->execute($query);
        return $db->lastInsertId();
    }

    public function insertActivityLikes($activityLogSeq, $postSeq)
    {
        $db = new db();
        $query = "INSERT INTO
                ActivityLikesLog (activityLogSeq, postSeq)
                VALUES (
                    '$activityLogSeq',
                    '$postSeq'
                )";
        return $db->execute($query);
    }

    /**
     * 활동 이력 조회
     * @param $memberSeq
     * @return array|bool
     */
    public function getActivityList($memberSeq)
    {
        $db = new db();
        $query = "SELECT
                A.activityLogSeq
                , A.activityType
                , IF(LENGTH(B.profileImagePath) > 0 , B.profileImagePath, '') AS profileImagePath
                , A.activityMessage
                , A.toMemberSeq
                , (SELECT nickName FROM Member WHERE memberSeq = A.toMemberSeq) as toMemberNickName
                , A.fromMemberSeq
                , IF((SELECT count(*) FROM MemberFollow WHERE memberSeq = A.fromMemberSeq and followMemberSeq = A.toMemberSeq),'true','false') as mutual
                , (SELECT postSeq FROM ActivityLikesLog WHERE activityLogSeq = A.activityLogSeq) as postSeq
            FROM
                ActivityLog A
                    INNER JOIN Member B ON A.toMemberSeq = B.memberSeq
            WHERE
                A.fromMemberSeq = $memberSeq
            ORDER BY
                A.regDate DESC";

        return $db->fetchAll($query);

//        $query = "SELECT
//                A.activityLogSeq
//                , A.activityType
//                , IF(LENGTH(B.profileImagePath) > 0 , B.profileImagePath, '') AS profileImagePath
//                , A.activityMessage
//                , A.toMemberSeq
//                , (SELECT nickName FROM Member WHERE memberSeq = A.toMemberSeq) as toMemberNickName
//                , A.fromMemberSeq
//            FROM
//                ActivityLog A
//                    INNER JOIN Member B ON A.toMemberSeq = B.memberSeq
//            WHERE
//                A.fromMemberSeq = $memberSeq
//            ORDER BY
//                A.regDate DESC";

//        $result = $db->fetchAll($query);

//        $result_arr = [];
//        foreach ($result as $item) {
//            $activityType = $item->activityType;
//            switch ($activityType) {
//                case "follow":
//                    $toMemberSeq = $item->toMemberSeq;
//                    $fromMemberSeq = $item->fromMemberSeq;
//                    $follow_query = "SELECT
//                                    IF(count(*),'true','false')as mutual
//                                    FROM MemberFollow
//                                    WHERE memberSeq = $fromMemberSeq and followMemberSeq = $toMemberSeq;";
//                    $item->mutual = ($db->fetchAll($follow_query))[0]->mutual;
//                    break;
//                case "likes":
//                    $activityLogSeq = $item->activityLogSeq;
//                    $likes_query = "SELECT postSeq
//                                    FROM ActivityLikesLog
//                                    WHERE activityLogSeq = ${activityLogSeq}";
//                    $item->postSeq = ($db->fetchAll($likes_query))[0]->postSeq;
//                    break;
//            }
//            array_push($result_arr, $item);
//        }

//        return $result;
    }
}