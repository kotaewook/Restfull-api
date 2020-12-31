<?php

class Member
{
    public $memberSeq = 0;
    public $phoneNumber = '';
    public $nickName = '';
    public $profileImagePath = '';
    public $regDate = '';
    public $modDate = '';
    public $fcmToken = '';

    /**
     * 회원가입
     * @param $phoneNumber 휴대폰번호
     * @param $nickName 별명
     * @param $fcmToken fcmToken
     * @return bool
     */
    public function insertMember($phoneNumber, $nickName, $fcmToken)
    {
        $db = new db();
        $query =
            "INSERT INTO
                    Member (phoneNumber, nickName, fcmToken)
                VALUES (
                    '$phoneNumber'
                    , '$nickName'
                    , '$fcmToken'
                )";
        return $db->execute($query);
    }

    /**
     * 휴대폰 번호, 닉네임으로 회원조회
     * @param string $phoneNumber 휴대폰 번호
     * @param string $nickName 닉네임
     * @return array|bool|mixed
     */
    public function getMemberSeq($phoneNumber = '', $nickName = '')
    {
        $db = new db();
        $query = "SELECT
                memberSeq
            FROM
                Member
            WHERE            
            ";
        // where 문
        if ($phoneNumber !== '') {
            $query .= " phoneNumber = '$phoneNumber'";
        }
        if ($phoneNumber !== '' && $nickName !== '') {
            $query .= ' and ';
        }
        if ($nickName !== '') {
            $query .= " nickName = '$nickName'";
        }
        $result = $db->fetchAll($query);
        if (!empty($result))
            $result = $result[0];
        return $result;
    }

    /**
     * 닉네임 조회 (유사 닉네임 포함)
     * @param $nickName 검색닉네임
     * @return array|bool
     */
    public function getMemberByNickname($nickName)
    {
        $db = new db();
        $query = "SELECT
                A.memberSeq ,
                IF(LENGTH(A.profileImagePath) > 0 , A.profileImagePath, '') AS profileImagePath ,
                A.nickName ,
                SUM(IF(B.regDate > DATE_ADD(NOW(),INTERVAL -3 DAY), 1, 0)) AS newPostsCount
            FROM
                Member A FORCE INDEX(nickName_UNIQUE)
                    LEFT OUTER JOIN Posts B FORCE INDEX(fk_Posts_Member1_idx) ON A.memberSeq = B.memberSeq
            WHERE
                A.nickName LIKE CONCAT('$nickName', '%')
            GROUP BY
                A.memberSeq";
        return $db->fetchAll($query);
    }

    /**
     * 회원 프로필 정보 조회
     * @param $memberSeq
     * @return array|bool|mixed
     */
    public function getMemberProfile($memberSeq)
    {
        $db = new db();
        $query =
            "SELECT
                IF(LENGTH(A.profileImagePath) > 0 , A.profileImagePath, '') AS profileImagePath
                , A.nickName
                , (SELECT COUNT(*) FROM MemberFollow WHERE followMemberSeq = A.memberSeq) AS followerCount
                , (SELECT COUNT(*) FROM MemberFollow WHERE memberSeq = A.memberSeq) AS followingCount
                , A.memberSeq
            FROM
                Member A
            WHERE
                A.memberSeq = $memberSeq";
        $result = $db->fetchAll($query);
        $result = (empty($result)) ? null : $result[0];
        return $result;
    }

    /**
     * 팔로우 구분 조회
     * @param $memberSeq
     * @param $followMemberSeq
     * @return array|bool|mixed
     */
    public function getMemberFollowtype($memberSeq, $followMemberSeq)
    {
        $db = new db();
        $query = "SELECT
                IF(COUNT(*) > 0, 'following', '') AS followType
            FROM
                MemberFollow
            WHERE
                memberSeq = $memberSeq
                AND followMemberSeq = $followMemberSeq";
        $result = $db->fetchAll($query);
        if (!empty($result)) {
            $result = $result[0];
        }
        return $result;
    }

    /**
     * 프로필 사진 수정
     * @param $memberSeq
     * @param $profileImagePath
     * @return bool
     */
    public function updateMemberProfile($memberSeq, $profileImagePath)
    {
        $db = new db();
        $query = " UPDATE
                    Member
                SET
                    profileImagePath = '$profileImagePath'
                    , modDate = current_timestamp()
                WHERE
                    memberSeq = '$memberSeq'";
        return $db->execute($query);
    }

    /**
     * 회원 팔로우 여부 확인
     * @param $memberSeq
     * @param $followMemberSeq
     * @return array|bool
     */
    public function getMemberFollowCount($memberSeq, $followMemberSeq)
    {
        $db = new db();
        $query = "SELECT
                COUNT(*) as cnt
            FROM
                MemberFollow
            WHERE
                memberSeq = $memberSeq
                AND followMemberSeq = $followMemberSeq";
        $result = $db->fetchAll($query);
        $result = $result[0]->cnt;
        return $result;
    }

    /**
     * 회원 팔로우 등록
     * @param $memberSeq
     * @param $followMemberSeq
     * @return bool
     */
    public function insertMemberFollow($memberSeq, $followMemberSeq)
    {
        $db = new db();
        $query = "INSERT INTO
                MemberFollow (memberSeq, followMemberSeq)
            VALUES (
                $memberSeq
                , $followMemberSeq
            )";
        return $db->execute($query);
    }

    /**
     * 회원 FcmToken 조회
     * @param $memberSeq
     * @return array|bool
     */
    public function getMemberFcmToken($memberSeq)
    {
        $db = new db();
        $query = "SELECT
                fcmToken
            FROM
                Member
            WHERE
                memberSeq = $memberSeq";
        $result = $db->fetchAll($query);
        $result = $result[0]->fcmToken;
        return $result;
    }

    /**
     * 회원 팔로우 해제
     * @param $memberSeq
     * @param $followMemberSeq
     * @return bool
     */
    public function deleteMemberFollow($memberSeq, $followMemberSeq)
    {
        $db = new db();
        $query = "DELETE FROM
                MemberFollow
            WHERE
                memberSeq = $memberSeq
                AND followMemberSeq = $followMemberSeq";
        return $db->execute($query);
    }

    /**
     * 팔로워 목록 조회
     * @param $memberSeq
     * @return array|bool|mixed
     */
    public function getMemberFollower($memberSeq)
    {
        $db = new db();
        $query = "SELECT
                A.memberSeq
                , IF(LENGTH(B.profileImagePath) > 0 , B.profileImagePath, '') AS profileImagePath
                , B.nickName
                , IF((SELECT COUNT(*) FROM MemberFollow WHERE followMemberSeq = A.memberSeq AND memberSeq = A.followMemberSeq) > 0, 'true', 'false') AS isFollow
            FROM
                MemberFollow A
                    INNER JOIN Member B ON A.memberSeq = B.memberSeq
            WHERE
                A.followMemberSeq = $memberSeq";
        return $db->fetchAll($query);
    }

    /**
     * 팔로잉 목록 조회
     * @param $memberSeq
     * @return array|bool
     */
    public function getMemberFollowing($memberSeq)
    {
        $db = new db();
        $query = "SELECT
                A.followMemberSeq
                , IF(LENGTH(B.profileImagePath) > 0 , B.profileImagePath, '') AS profileImagePath
                , B.nickName
            FROM
                MemberFollow A
                    INNER JOIN Member B ON A.followMemberSeq = B.memberSeq
            WHERE
                A.memberSeq = $memberSeq";
        return $db->fetchAll($query);
    }

    /**
     * 회원의 FCM 토큰 업데이트
     * @param $memberSeq
     * @param $fcmToken
     * @return bool
     */
    public function updateMemberFcmToken($memberSeq, $fcmToken)
    {
        $db = new db();
        $query = "UPDATE
                Member
            SET
                fcmToken = '$fcmToken'
                , modDate = current_timestamp()
            WHERE
                memberSeq = $memberSeq";
        return $db->execute($query);
    }

}