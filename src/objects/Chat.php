<?php

class Chat
{
    public $chatSeq = '';
    public $toMemberSeq = 0;
    public $toMemberIndex = 0;
    public $fromMemberSeq = 0;
    public $fromMemberIndex = 0;
    public $regDate = '';

    /**
     * 회원 채팅 목록
     * @param $memberSeq
     * @return array|bool|mixed
     */
    public function getChatList($memberSeq)
    {
        $db = new db();
        $query = "SELECT
                IFNULL(chatSeq, '') AS chatSeq
            FROM
                Chat
            WHERE
                (toMemberSeq = $memberSeq OR fromMemberSeq = $memberSeq)";
        return $db->fetchAll($query);
    }

    /**
     * 채팅방 정보 등록
     * @param $chatSeq
     * @param $toMemberSeq
     * @param $fromMemberSeq
     * @return bool
     */
    public function insertChat($chatSeq, $toMemberSeq, $fromMemberSeq)
    {
        $db = new db();
        $query = "INSERT INTO
                Chat (chatSeq, toMemberSeq, fromMemberSeq)
            VALUES (
                '$chatSeq'
                , $toMemberSeq
                , $fromMemberSeq
            )";
        return $db->execute($query);
    }

    /**
     * 채팅방 정보 조회
     * @param $chatSeq
     * @return bool|mixed
     */
    public function getChat($chatSeq)
    {
        $db = new db();
        $query = "SELECT
                A.chatSeq
                , A.toMemberSeq
                , B.nickName AS toNickName
                , B.profileImagePath AS toProfileImagePath
                , A.toMemberIndex
                , A.fromMemberSeq
                , C.nickName AS fromNickName
                , C.profileImagePath AS fromProfileImagePath
                , A.fromMemberIndex
                , A.outMemberSeq
                , date_format(A.regDate,'%Y-%m-%d %H:%i:%S.0') as regDate
            FROM
                Chat A
                    INNER JOIN Member B ON A.toMemberSeq = B.memberSeq
                    INNER JOIN Member C ON A.fromMemberSeq = C.memberSeq
            WHERE
                A.chatSeq = '$chatSeq'";
        $result = $db->fetchAll($query);
        return $result[0];
    }

    /**
     * 채팅방 회원의 메시지 위치값을 업데이트
     * @param $chatSeq
     * @param $memberIndexType
     * @param $memberIndex
     * @return bool
     */
    public function updateMemberChatMessageIndex($chatSeq, $memberIndexType, $memberIndex)
    {
        $db = new db();
        $query = "UPDATE
                Chat
            SET
            $memberIndexType = $memberIndex
            WHERE
                chatSeq = '$chatSeq'";
        return $db->execute($query);
    }

    public function updateChatOutMemberSeq($chatSeq, $myMemberType, $memberSeq, $type = 'update', $memberIndex = '')
    {
        $where = ($type == 'delete') ?
            "outMemberSeq = 
            IF(({$myMemberType}Seq = '$memberSeq'), '$memberSeq', outMemberSeq),
            {$myMemberType}Index = 
            IF(({$myMemberType}Seq = '$memberSeq'), '$memberIndex', {$myMemberType}Index)" :
            "outMemberSeq = IF(({$myMemberType}Seq = '$memberSeq'), -1, outMemberSeq)";
        $db = new db();
        $query = "UPDATE
                Chat
                SET
                {$where}
                WHERE chatSeq = '$chatSeq'";
        return $db->execute($query);
    }

    /**
     * 채팅방 삭제
     * @param $chatSeq
     * @return bool
     */
    public function deleteChat($chatSeq, $memberSeq, $myMemberType)
    {
        $db = new db();
        $query = "DELETE FROM
                Chat
            WHERE
                chatSeq = '$chatSeq'
                and {$myMemberType}Seq = '$memberSeq'
                and outMemberSeq != '$memberSeq'
                and outMemberSeq != -1";
        return $db->execute($query);
    }

    /**
     * 채팅방 존재 여부 확인 (일렬 번호 조회)
     * @param $toMemberSeq
     * @param $fromMemberSeq
     * @return bool|mixed
     */
    public function getChatSeq($toMemberSeq, $fromMemberSeq)
    {
        $db = new db();
        $query = "SELECT
                IFNULL(chatSeq, '') AS chatSeq
            FROM
                Chat
            WHERE
                (toMemberSeq = $toMemberSeq AND fromMemberSeq = $fromMemberSeq)
                OR (fromMemberSeq = $toMemberSeq AND toMemberSeq = $fromMemberSeq)";
        return $db->fetchAll($query);
    }
}