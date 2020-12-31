<?php

class Post
{
    public $postSeq = 0;
    public $memberSeq = 0;
    public $postContents = '';
    public $regDate = '';
    public $modDate = '';

    public function getMemberPosts($memberSeq)
    {
        $db = new db();
        $query = "SELECT
                AA.postSeq
                , IF(LENGTH(AA.imagePath) > 0 , AA.imagePath, '') AS imagePath
                , IF(SUBSTRING_INDEX(AA.imagePath, '.', -1) = 'jpg', 'true', 'false') AS isImage
            FROM (
                SELECT
                    A.postSeq
                    , (SELECT imagePath FROM PostsImages FORCE INDEX(fk_PostsImages_Posts_idx) WHERE postSeq = A.postSeq ORDER BY postSeq ASC LIMIT 1) AS imagePath
                    , date_format(A.regDate,'%Y-%m-%d %H:%i:%S.0') as regDate
                FROM
                    Posts A
                WHERE
                    A.memberSeq = $memberSeq
                ) AA
            ORDER BY
                AA.regDate DESC";
        return $db->fetchAll($query);
    }

    public function getMemberPostsV2($memberSeq)
    {
        $db = new db();
        $query = "SELECT
                postSeq
            FROM
                Posts
            WHERE
                memberSeq = $memberSeq
            ORDER BY
                regDate DESC";
        $subSql = "SELECT
                IF(LENGTH(imagePath) > 0 , imagePath, '') AS imagePath
            FROM
                PostsImages
            WHERE
                (1)
            ORDER BY
                regDate ASC";
        return $db->reformFetch($query, 'postsImagesList', $subSql, array('postSeq', 'postSeq'));
    }

    /**
     * 게시물
     */

    /** 게시물 목록 조회
     * @param $memberSeq
     * @param $postSeq
     * @return array|bool
     */
    public function getPostsList($memberSeq, $postSeq = null)
    {
        $db = new db();

        $query = "SELECT
               AA.postSeq
                , AA.profileImagePath
                , AA.nickName
                , AA.imagePath
                , IF(SUBSTRING_INDEX(AA.imagePath, '.', -1) = 'jpg', 'true', 'false') AS isImage
                , AA.postContents
                , date_format(AA.regDate,'%Y-%m-%d %H:%i:%S.0') as regDate
                , AA.replyCount
                , AA.memberSeq
                , AA.isLikes
            FROM (
                SELECT
                    B.postSeq
                    , C.profileImagePath
                    , C.nickName
                    , (SELECT imagePath FROM PostsImages FORCE INDEX(fk_PostsImages_Posts_idx) WHERE postSeq = B.postSeq ORDER BY postSeq ASC LIMIT 1) AS imagePath
                    , B.postContents
                    , date_format(B.regDate,'%Y-%m-%d %H:%i:%S.0') as regDate
                    , (SELECT COUNT(*) FROM PostsReply WHERE postSeq = B.postSeq) AS replyCount
                    , B.memberSeq
                    , IF((SELECT COUNT(*) FROM PostsLikes WHERE postSeq = B.postSeq AND memberSeq = $memberSeq) > 0, 'true', 'false') AS isLikes
                FROM
                    MemberFollow A
                        LEFT OUTER JOIN Posts B FORCE INDEX(fk_Posts_Member1_idx) ON A.followMemberSeq = B.memberSeq
                        INNER JOIN Member C ON B.memberSeq = C.memberSeq
                WHERE
                    (A.memberSeq = $memberSeq OR B.memberSeq = $memberSeq)";
        // postSeq가 넘어왔을 경우
        $query .= ($postSeq != null) ? "AND B.postSeq > $postSeq" : "";
        $query .= " GROUP BY
                    B.postSeq
                    ORDER BY
                        B.postSeq DESC
                ) AA";
        return $db->fetchAll($query);
    }

    /** 게시물 목록 조회 V2
     * @param $memberSeq
     * @param $postSeq
     * @return array|bool
     */
    public function getPostsListV2($memberSeq, $postSeq = null)
    {
        $db = new db();

        $query = "SELECT
                B.postSeq
                , C.profileImagePath
                , C.nickName
                , B.postContents
                , date_format(B.regDate,'%Y-%m-%d %H:%i:%S.0') as regDate
                , (SELECT COUNT(*) FROM PostsReply WHERE postSeq = B.postSeq) AS replyCount
                , B.memberSeq
                , IF((SELECT COUNT(*) FROM PostsLikes WHERE postSeq = B.postSeq AND memberSeq = $memberSeq) > 0, 'true', 'false') AS isLikes
            FROM
                MemberFollow A
                    LEFT OUTER JOIN Posts B FORCE INDEX(fk_Posts_Member1_idx) ON A.followMemberSeq = B.memberSeq
                    INNER JOIN Member C ON B.memberSeq = C.memberSeq
            WHERE
                (A.memberSeq = $memberSeq OR B.memberSeq = $memberSeq)";
        // postSeq가 넘어왔을 경우
        $query .= ($postSeq != null) ? "AND B.postSeq > $postSeq" : "";
        $query .= " GROUP BY
                        B.postSeq
                    ORDER BY
                        B.postSeq DESC";

        // PostList => imagePath(포스트 이미지 리스트) 를 만드는 서브 쿼리
        $subSql = "SELECT imagePath FROM PostsImages WHERE (1) ORDER BY regDate ASC";

        // reformFetch 의 array('postSeq', 'postSeq') =>[0] : where column 이름, [1] : 내용
        return $db->reformFetch($query, 'postsImagesList', $subSql, array('postSeq', 'postSeq'));
    }

    /**
     * 게시물 조회 V1
     * @param $memberSeq
     * @param $postSeq
     * @return array|bool
     */
    public function getPosts($memberSeq, $postSeq)
    {
        $db = new db();

        $postQuery = " SELECT
                AA.postSeq
                , AA.imagePath
                , IF(SUBSTRING_INDEX(AA.imagePath, '.', -1) = 'jpg', 'true', 'false') AS isImage
                , AA.postContents
                , date_format(AA.regDate,'%Y-%m-%d %H:%i:%S.0') as regDate
                , AA.modDate
                , AA.replyCount
                , AA.memberSeq
                , AA.profileImagePath
                , AA.nickName
                , AA.isLikes
            FROM (
                SELECT
                    A.postSeq
                    , (SELECT imagePath FROM PostsImages FORCE INDEX(fk_PostsImages_Posts_idx) WHERE postSeq = A.postSeq ORDER BY postSeq ASC LIMIT 1) AS imagePath
                    , A.postContents
                    , date_format(A.regDate,'%Y-%m-%d %H:%i:%S.0') as regDate
                    , A.modDate
                    , (SELECT COUNT(*) FROM PostsReply WHERE postSeq = A.postSeq) AS replyCount
                    , A.memberSeq
                    , B.profileImagePath
                    , B.nickName
                    , IF((SELECT COUNT(*) FROM PostsLikes WHERE postSeq = $postSeq AND memberSeq = $memberSeq) > 0, 'true', 'false') AS isLikes
                FROM
                    Posts A
                        INNER JOIN Member B ON A.memberSeq = B.memberSeq
                WHERE
                    A.postSeq = $postSeq
            ) AA";

        return $db->fetchAll($postQuery);
    }

    /**
     * 게시물 조회 V2
     * @param $memberSeq
     * @param $postSeq
     * @return array|bool
     */
    public function getPostsV2($memberSeq, $postSeq)
    {
        $db = new db();

        $postQuery = "SELECT
                A.postSeq
                , A.postContents
                , date_format(A.regDate,'%Y-%m-%d %H:%i:%S.0') as regDate
                , A.modDate
                , (SELECT COUNT(*) FROM PostsReply WHERE postSeq = A.postSeq) AS replyCount
                , A.memberSeq
                , B.profileImagePath
                , B.nickName
                , IF((SELECT COUNT(*) FROM PostsLikes WHERE postSeq = $postSeq AND memberSeq = $memberSeq) > 0, 'true', 'false') AS isLikes
                , IF((SELECT COUNT(*) FROM MemberFollow WHERE memberSeq = $memberSeq and followMemberSeq = A.memberSeq), 'true', 'false') AS isFollow
            FROM
                Posts A
                    INNER JOIN Member B ON A.memberSeq = B.memberSeq
            WHERE
                A.postSeq = $postSeq";

        return $db->fetchAll($postQuery);
    }

    /**
     * 게시물 hashTag 목록 조회
     * @param $postSeq
     * @return array|bool
     */
    public function getPostsHashtag($postSeq)
    {
        $db = new db();

        $query = "SELECT
                    hashTag
                FROM
                    PostsHashtag
                WHERE
                    postSeq = $postSeq";

        return $db->fetchAll($query);
    }

    /**
     * 게시물 memberTag 목록 조회
     * @param $postSeq
     * @return array|bool
     */
    public function getPostsMemberTagList($postSeq)
    {
        $db = new db();

        $query = " SELECT
                        A.nickName
                        , B.memberSeq
                    FROM
                        PostsMemberTag A
                            LEFT OUTER JOIN Member B ON A.nickName = B.nickName
                    WHERE
                        A.postSeq = $postSeq";

        return $db->fetchAll($query);
    }

    /**
     * 게시물 imagesList 목록 조회
     * @param $postSeq
     * @return array|bool
     */
    public function getPostsImagesList($postSeq)
    {
        $db = new db();

        $query = " SELECT
                        imagePath
                    FROM
                        PostsImages
                    WHERE
                        postSeq = $postSeq
                    ORDER BY
                        regDate ASC";

        return $db->fetchAll($query);
    }

    /**
     * 전체 게시물 랜덤 조회
     * @return array|bool
     */
    public function getPostsRandom()
    {
        $db = new db();

        $query = "SELECT
                    AA.postSeq
                    , AA.imagePath
                    , IF(SUBSTRING_INDEX(AA.imagePath, '.', -1) = 'jpg', 'true', 'false') AS isImage
                FROM (
                    SELECT
                        A.postSeq
                        , (SELECT imagePath FROM PostsImages WHERE postSeq = A.postSeq ORDER BY postSeq ASC LIMIT 1) AS imagePath
                    FROM
                        Posts A
                    ORDER BY
                        RAND()
                    LIMIT 100
                ) AA";

        return $db->fetchAll($query);
    }

    /**
     * 전체 게시물 랜덤 조회 (v2)
     * @return array|bool
     */
    public function getPostsRandomV2()
    {
        $db = new db();

        $query = "SELECT
                        postSeq
                    FROM
                        Posts
                    ORDER BY
                        RAND()
                    LIMIT 100";

        // PostList => imagePath(포스트 이미지 리스트) 를 만드는 서브 쿼리
        $subSql = "SELECT imagePath FROM PostsImages WHERE (1) ORDER BY regDate ASC";

        // reformFetch 의 array('postSeq', 'postSeq') =>[0] : where column 이름, [1] : 내용
        return $db->reformFetch($query, 'postsImagesList', $subSql, array('postSeq', 'postSeq'));
    }

    /**
     * 전체 게시물 랜덤 조회 (v3)
     * @return array|bool
     */
    public function getPostsRandomV3($memberSeq, $Refresh)
    {
        $db = new db();
        try {

            // 새로 고침 이면 세션 지우기
            if ($Refresh == 1 || @$_SESSION['memberSeq'] != $memberSeq) {
                unset($_SESSION['notSearch']);
                $_SESSION['memberSeq'] = $memberSeq;
            }
            $smallArray = $BigArray = []; // 변수 선언

            //===== 큰칸 셋팅 시작 =====

            // 해당 배열에 저장된 postSeq를 제외
            $notSearchSql =
                count($_SESSION['notSearch']) > 0 ?
                    ' and ' . implode(' and ', $_SESSION['notSearch']) :
                    '';

            // JOIN 사용 하는 Query
            $query = "select 
                IMG.postSeq, 
                IMG.imagePath, 
                ( select count(*) as cnt from PostsImages imgCnt where IMG.postSeq = imgCnt.postSeq ) as imgCount 
                from PostsImages as IMG 
                LEFT JOIN Posts as P on IMG.postSeq = P.postSeq 
                LEFT OUTER JOIN MemberFollow as MF on P.memberSeq = MF.followMemberSeq 
                where MF.memberSeq != '{$memberSeq}' and P.memberSeq != '{$memberSeq}'
                and (SUBSTRING_INDEX(IMG.imagePath, '.', -1) = 'mp4') {$notSearchSql} 
                group by IMG.postSeq 
                order by rand()";

            /*
             * // IN 사용 하는 Query
            $query = "select postSeq, imagePath from (
                        select * from PostsImages where postSeq in (
                            select postSeq from Posts where memberSeq not in (
                                select followMemberSeq from MemberFollow where memberSeq = '{$memberSeq}'
                            ) and memberSeq != '{$memberSeq}'
                        )
                        and (SUBSTRING_INDEX(imagePath, '.', -1) = 'mp4') {$notSearchSql} group by postSeq order by rand()
                    ) as TOTAL";
            */

            $stmt = $db->fetchAll($query);
            $rowCount = count($stmt);
            for ($i = 0; $i < $rowCount; $i++) {
                $row = $stmt[$i];
                $BigArray[$i]['postSeq'] = $row->postSeq;
                $BigArray[$i]['path'] = $row->imagePath;
                $_SESSION['notSearch'][] = "IMG.postSeq != {$row->postSeq}";
            }
            unset($stmt);
            $bigArrayCount = count($BigArray);
            // ===== 큰칸 셋팅 끝 =====

            // ===== 작은칸 셋팅 시작 =====
            // 해당 배열에 저장된 postSeq를 제외
            $notSearchSql =
                count($_SESSION['notSearch']) > 0 ?
                    ' and ' . implode(' and ', $_SESSION['notSearch']) :
                    '';

            // JOIN 사용하는 Query
            $query = "select 
                IMG.postSeq, 
                IMG.imagePath, 
                ( select count(*) as cnt from PostsImages imgCnt 
                where IMG.postSeq = imgCnt.postSeq ) as imgCount 
                from PostsImages as IMG 
                LEFT JOIN Posts as P on IMG.postSeq = P.postSeq 
                LEFT OUTER JOIN MemberFollow as MF on P.memberSeq = MF.followMemberSeq 
                where MF.memberSeq != '{$memberSeq}' and P.memberSeq != '{$memberSeq}' {$notSearchSql} 
                group by IMG.postSeq 
                order by rand()";

            /*
            // IN 사용하는 Query
            $query = "select imgCount, postSeq, imagePath from (
                        select *, (
                            select count(*) as cnt from PostsImages imgCnt where IMG.postSeq = imgCnt.postSeq
                        ) as imgCount from PostsImages IMG where postSeq in (
                            select postSeq from Posts where memberSeq not in (
                                select followMemberSeq from MemberFollow where memberSeq = '{$memberSeq}'
                            ) and memberSeq != '{$memberSeq}'
                        ) {$notSearchSql} group by postSeq order by rand()
                    ) as TOTAL";
            */

            $stmt = $db->fetchAll($query);
            $rowCount = count($stmt);
            for ($i = 0; $i < $rowCount; $i++) {
                $row = $stmt[$i];
                $smallArray[$i]['isMulti'] = $row->imgCount > 1 ? true : false;
                $smallArray[$i]['postSeq'] = $row->postSeq;
                $smallArray[$i]['path'] = $row->imagePath;
                if (strpos($row->imagePath, '.mp4') !== false)
                    $_SESSION['notSearch'][] = "postSeq != {$row->postSeq}"; // jpg가 아니면 해당 배열에 저장
            }

            unset($stmt);
            $smallArrayCount = count($smallArray);
            // ===== 작은칸 셋팅 끝 =====

            // 작은칸, 큰칸 합치기
            $smallPlus = $noBig = $break = $bigPlus = 0;
            for ($i = 0; $i < $bigArrayCount * 9; $i++) {
                $mixSmallArray = [];
                for ($j = 0; $j < 8; $j++) { // 작은 이미지 8개
                    $smallNow = $i * 8 + $j + $smallPlus;
                    if ($smallArray[$smallNow])
                        $mixSmallArray[] = $smallArray[$smallNow];
                }
                // 큰칸부터 배열에 넣고 큰칸이 없으면 작은칸을 추가로 배열에 넣기
                $bigNow = $i + $bigPlus;
                if ($BigArray[$bigNow]) {
                    $mixArray[$i]['big'] = $BigArray[$bigNow];
                } else {
                    $noBig = 1;
                    $mixArray[$i]['big']['postSeq'] = -1;
                    $mixArray[$i]['big']['path'] = '';
                }
                if ($noBig == 1) { // 동영상이 없어서 이미지를 추가로 넣어야 할 경우
                    $lastCount = $smallArrayCount - $i * 8 + $smallPlus;
                    if (count($mixArray[$i]['small']) < 9 && $lastCount > 0) {
                        for ($j = 0; $j < 4; $j++) {
                            $smallNow++;
                            if ($smallArray[$smallNow]) {
                                $mixSmallArray[] = $smallArray[$smallNow];
                                $smallPlus++;
                            } else {
                                $break = 1;
                                break;
                            }
                        }
                    } else { // $lastCount이 0보다 작을경우 끝내기
                        unset($mixArray[$i]);
                        break;
                    }
                }
                if (count($mixSmallArray) < 8 && $bigArrayCount - $i - $bigPlus > 0) {
                    for ($j = 0; $j < 8 - count($mixArray[$i]['small']); $j++) {
                        $bigPlus++;
                        $bigNow = $i + $bigPlus;
                        if (!$BigArray[$bigNow] || ($mixArray[$i]['big']['postSeq'] > 0 && count($mixSmallArray) >= 8)) {
                            $bigPlus--;
                            break;
                        }
                        $BigArray[$bigNow]['isMulti'] = false;
                        $mixSmallArray[] = $BigArray[$bigNow];
                    }
                }
                $mixArray[$i]['small'] = $mixSmallArray; // 작은칸 추가된게 있으면 같이 넣고 없으면 기본 8개만 넣기
                if ($break == 1)
                    break;
            }
            unset($mixSmallArray);
            return count($mixArray) > 0 ? $mixArray : array();
        } catch (PDOException $e) {
            error_log("[PDOException]" . $e);
            return false;
        }
    }

    /**
     * 전체 게시물 랜덤 조회 클릭 (v3)
     * @return array|bool
     */
    public function getPostsRandomView($memberSeq, $postSeq)
    {
        $db = new db();
        try {
            $list = [];
//            $whereArray = '';

//            $count = count($_SESSION['notSearch']);
//            for ($i = 0; $i < $count; $i++) {
//                $postNum = str_replace('IMG.postSeq != ', '', $_SESSION['notSearch'][$i]);
//                $whereArray .= " AND B.postSeq = {$postNum} ";
//            }

            $query = "SELECT
					P.postSeq
					, M.profileImagePath
					, M.nickName
					, P.postContents
					, date_format(P.regDate,'%Y-%m-%d %H:%i:%S.0') as regDate
					, (SELECT COUNT(*) FROM PostsReply WHERE postSeq = P.postSeq) AS replyCount
					, P.memberSeq
					, IF((SELECT COUNT(*) FROM PostsLikes WHERE postSeq = P.postSeq AND memberSeq = '$memberSeq') > 0, 'true', 'false') AS isLikes
					, IF((SELECT COUNT(*) FROM MemberFollow WHERE memberSeq = '$memberSeq' and followMemberSeq = P.memberSeq), 'true', 'false') AS isFollow
            FROM
                Posts P
					LEFT OUTER JOIN Member M ON P.memberSeq = M.memberSeq
					JOIN PostsImages IMG ON P.postSeq = IMG.postSeq
            WHERE
                (P.memberSeq != '$memberSeq' AND P.memberSeq NOT IN (SELECT followMemberSeq FROM MemberFollow WHERE memberSeq = '$memberSeq')) OR P.postSeq = '$postSeq'";
            // postSeq가 넘어왔을 경우
//            $query .= $whereArray;
            $query .= " GROUP BY
                P.postSeq
            ORDER BY
                FIELD(P.postSeq, '$postSeq') DESC,
                rand()";

            // PostList => imagePath(포스트 이미지 리스트) 를 만드는 서브 쿼리
            $subSql = "SELECT imagePath FROM PostsImages WHERE (1) ORDER BY regDate ASC";

            // reformFetch 의 array('postSeq', 'postSeq') =>[0] : where column 이름, [1] : 내용

            $list = $db->reformFetch($query, 'postsImagesList', $subSql, array('postSeq', 'postSeq'));

            return $list;
        } catch (PDOException $e) {
            error_log("[PDOException]" . $e);
            return false;
        }
    }


    /**
     * 검색어 해당 해시태그 조회
     * @param $searchKeyword
     * @return array|bool
     */
    public function getPostsHashtagList($searchKeyword)
    {
        $db = new db();

        $query = "SELECT
                A.hashTagSeq
                , A.hashTag
                , A.postSeq
                , (SELECT COUNT(*) FROM Posts AA INNER JOIN PostsHashtag BB ON AA.postSeq = BB.postSeq WHERE BB.hashTag = A.hashTag) AS usedPostsCount
            FROM
                PostsHashtag A FORCE INDEX(idx_PostsHashtag_hashTag)
            WHERE
                A.hashTag LIKE CONCAT( '$searchKeyword', '%')
            GROUP BY
                A.hashTag";

        return $db->fetchAll($query);
    }

    /**
     * 해시태그에 해당하는 게시물 조회
     * @param $hashTag
     * @return array|bool
     */
    public function getPostsFromHashtagList($hashTag)
    {
        $db = new db();

        // postList query 생성
        $query = "SELECT
                        AA.postSeq
                        , AA.hashTag
                        , AA.imagePath
                        , IF(SUBSTRING_INDEX(AA.imagePath, '.', -1) = 'jpg', 'true', 'false') AS isImage
                        , AA.memberSeq
                    FROM (
                        SELECT
                            A.postSeq
                            , A.hashTag
                            , (SELECT imagePath FROM PostsImages FORCE INDEX(fk_PostsImages_Posts_idx) WHERE postSeq = A.postSeq ORDER BY postSeq ASC LIMIT 1) AS imagePath
                            , B.memberSeq
                        FROM
                            PostsHashtag A
                                INNER JOIN Posts B ON A.postSeq = B.postSeq
                        WHERE
                            A.hashTag = '$hashTag'                    
                        ) AA";

        return $db->fetchAll($query);
    }

    /**
     * 해시태그에 해당하는 게시물 조회
     * @param $hashTag
     * @return array|bool
     */
    public function getPostsFromHashtagListV2($hashTag)
    {
        $db = new db();

        // postList query 생성
        $query = "SELECT
            A.postSeq
            , A.hashTag
            , B.memberSeq
        FROM
            PostsHashtag A
                INNER JOIN Posts B ON A.postSeq = B.postSeq
        WHERE
            A.hashTag = '$hashTag'";

        // postList => imagePath(포스트 이미지 리스트) 를 만드는 서브 쿼리
        $subSql = "SELECT imagePath FROM PostsImages WHERE (1) ORDER BY regDate ASC";

        // reformFetch 의 array('postSeq', 'postSeq') =>[0] : where column 이름, [1] : 내용
        return $db->reformFetch($query, 'postsImagesList', $subSql, array('postSeq', 'postSeq'));
    }

    /**
     * 해당 태그가 사용된 포스트의 수
     * @param $hashTag
     * @return array|bool
     */
    public function getHashtagUsedPostsCount($hashTag)
    {
        $db = new db();

        $countQuery = "SELECT
                        COUNT(*) AS usedPostsCount
                    FROM
                        PostsHashtag A
                            INNER JOIN Posts B ON A.postSeq = B.postSeq
                    WHERE
                        A.hashTag = '$hashTag'";

        return $db->fetchAll($countQuery);
    }

    // 게시물 작성자 조회
    public function getPostsMember($postSeq)
    {
        $db = new db();

        $query = " SELECT
                        memberSeq
                    FROM
                        Posts
                    WHERE
                        postSeq = $postSeq";

        return $db->fetchAll($query);
    }


    /** 등록*/
    // 게시물 등록
    public function insertPost($memberSeq, $postContents)
    {
        $db = new db();

        $query = "INSERT INTO
                        Posts (memberSeq, postContents)
                    VALUES (
                        $memberSeq
                        , '$postContents')";

        if ($result = $db->execute($query)) {
            $lastInsertPostSeq = $db->lastInsertId();
            return $lastInsertPostSeq;
        } else {
            return $result;
        }
    }

    // 게시물 해시태그 등록
    public function insertPostHashTag($postSeq, $hashTagList)
    {
        $db = new db();
        $hashTagListQuery = $this->multiInsertValues($postSeq, $hashTagList, 'hashTag');
        $query = "INSERT INTO
                        PostsHashtag (postSeq, hashTag)
                    VALUES $hashTagListQuery";
        return $db->execute($query);
    }

    public function deletePostHashTag($postSeq, $hashTagList)
    {
        $db = new db();
        $hashTagArr = '';
        foreach ($hashTagList as $key => $value) {
            $hashTagArr .= (($key == 0 || $key == count($hashTagList)) ? '' : ',') . $value;
        }
        $query = "delete from PostsHashtag where postSeq = '{$postSeq}' and hashTag in (" . $hashTagArr . ")";
        return $db->execute($query);
    }

    // 게시물 이미지 등록
    public function insertPostImages($postSeq, $imagesList)
    {
        $db = new db();
        $imagesListQuery = $this->multiInsertValues($postSeq, $imagesList, 'imagePath');
        $query = "INSERT INTO
                       PostsImages (postSeq, imagePath)
                    VALUES $imagesListQuery";
        return $db->execute($query);
    }

    public function deletePostImages($postSeq, $imagesList)
    {
        $db = new db();
        $imagesArr = '';
        foreach ($imagesList as $key => $value) {
            $imagesArr .= (($key == 0 || $key == count($imagesList)) ? '' : ',') . "'" . $value . "'";
        }
        $query = "delete from PostsImages where postSeq = '{$postSeq}' and imagePath in (" . $imagesArr . ")";
        return $db->execute($query);
    }

    // 게시물 사용자 태그 등록
    public function insertPostMemberTag($postSeq, $memberTagList)
    {
        $db = new db();
        $memberTagListQuery = $this->multiInsertValues($postSeq, $memberTagList, 'nickName');
        $query = "INSERT INTO
                        PostsMemberTag (postSeq, nickName)
                    VALUES $memberTagListQuery";
        return $db->execute($query);
    }

    public function deletePostMemberTag($postSeq, $memberTagList)
    {
        $db = new db();
        $memberTagArr = '';
        foreach ($memberTagList as $key => $value) {
            $memberTagArr .= (($key == 0 || $key == count($memberTagList)) ? '' : ',') . "'" . $value . "'";
        }
        $query = "delete from PostsMemberTag where postSeq = '{$postSeq}' and nickName in (" . $memberTagArr . ")";
        return $db->execute($query);
    }

    // insert values list 생성
    function multiInsertValues($postSeq, $originList, $arrayName)
    {
        $originListArr = [];
        for ($i = 0; $i < count($originList); $i++) {
            $originListValue = $originList[$i][$arrayName];
            array_push($originListArr, "($postSeq,'$originListValue')");
        }
        return implode(',', $originListArr);
    }

    /** 좋아요 */
    // 좋아요 여부 조회
    public function getPostsLikesCount($memberSeq, $postSeq)
    {
        $db = new db();
        $query = "SELECT
                        COUNT(*) as cnt
                    FROM
                        PostsLikes
                    WHERE
                        postSeq = $postSeq
                        AND memberSeq = $memberSeq";

        return $db->fetchAll($query);;
    }

    // 좋아요 등록
    public function insertPostsLikes($memberSeq, $postSeq)
    {
        $db = new db();
        $query = " INSERT INTO
                        PostsLikes (postSeq, memberSeq)
                    VALUES (
                        $postSeq
                        , $memberSeq
                    )";
        return $db->execute($query);
    }

    // 좋아요 취소
    public function deletePostsLikes($memberSeq, $postSeq)
    {
        $db = new db();

        $query = "DELETE FROM
                        PostsLikes
                    WHERE
                        postSeq = $postSeq
                        AND memberSeq = $memberSeq";

        return $db->execute($query);
    }

    /**
     * 댓글
     **/
    /** 조회 */
    // 댓글 조회
    public function getReplyList($postSeq)
    {
        $db = new db();

        $query = "  SELECT
                        A.replySeq
                        , date_format(A.regDate,'%Y-%m-%d %H:%i:%S.0') as regDate
                        , B.nickName
                        , A.replyContents
                        , B.profileImagePath
                    FROM
                        PostsReply A
                            INNER JOIN Member B ON A.memberSeq = B.memberSeq
                    WHERE
                        A.postSeq = $postSeq
                    ORDER BY
                        A.regDate DESC";

        $hashTagQuery = "SELECT
                                hashTag
                            FROM
                                PostsReplyHashtag
                            WHERE (1)";

        $memberHashTagQuery = " SELECT
                        A.nickName
                        , B.memberSeq
                    FROM
                        PostsReplyMemberTag A
                            LEFT OUTER JOIN Member B ON A.nickName = B.nickName
                    WHERE (1)";

        return $db->multiReformFetch($query, array('replyHashtagList', 'replyMemberTagList'), array($hashTagQuery, $memberHashTagQuery), array(array('replySeq', 'replySeq'), array('A.replySeq', 'replySeq')));
    }

    /** 등록*/
    // 댓글 등록
    public function insertPostReply($postSeq, $memberSeq, $replyContents)
    {
        $db = new db();

        $query = "INSERT INTO
                        PostsReply (postSeq, memberSeq, replyContents)
                    VALUES (
                        $postSeq
                        , $memberSeq
                        , '$replyContents')";

        if ($result = $db->execute($query)) {
            $lastInsertPostReplySeq = $db->lastInsertId();
            return $lastInsertPostReplySeq;
        } else {
            return $result;
        }
    }

    // 댓글 해시태그 등록
    public function insertPostReplyHashtag($replySeq, $replyHashtagList)
    {
        $db = new db();
        $replyHashtagListQuery = $this->multiInsertValues($replySeq, $replyHashtagList, 'hashTag');
        $query = "INSERT INTO
                       PostsReplyHashtag (replySeq, hashTag)
                    VALUES $replyHashtagListQuery";
        return $db->execute($query);
    }

    // 댓글 사용자 태그 등록
    public function insertPostReplyMemberTag($replySeq, $replyMemberTagList)
    {
        $db = new db();
        $replyMemberTagListQuery = $this->multiInsertValues($replySeq, $replyMemberTagList, 'nickName');
        $query = "INSERT INTO
                       PostsReplyMemberTag (replySeq, nickName)
                    VALUES $replyMemberTagListQuery";
        return $db->execute($query);
    }

    /** 삭제 */
    // 댓글 삭제 인증
    public function getPostReplyWriter($replySeq)
    {
        $db = new db();

        $query = " SELECT
            memberSeq
        FROM
            PostsReply
        WHERE
            replySeq = $replySeq";

        return $db->fetchAll($query);
    }

    // 댓글 해시태그 삭제
    public function deletePostReplyHashtag($replySeq)
    {
        $db = new db();

        $query = "DELETE FROM
            PostsReplyHashtag
        WHERE
            replySeq = $replySeq";

        return $db->execute($query);
    }

    // 댓글 회원태깅 삭제
    public function deletePostReplyMemberTag($replySeq)
    {
        $db = new db();

        $query = "DELETE FROM
            PostsReplyMemberTag
        WHERE
            replySeq = $replySeq";

        return $db->execute($query);
    }

    // 게시물 댓글 삭제
    public function deletePostReply($replySeq)
    {
        $db = new db();

        $query = "DELETE FROM
                        PostsReply
                    WHERE
                        replySeq = $replySeq";

        return $db->execute($query);
    }

    // 특정 회원 이미지 게시글 조회
    public function getImagePosts($memberSeq)
    {
        $db = new db();

        $query = "SELECT
                P.postSeq
                FROM Posts P
                JOIN PostsImages IMG ON P.postSeq = IMG.postSeq
                WHERE P.memberSeq = '$memberSeq'
                GROUP BY P.postSeq
                Order BY P.postSeq DESC";

        // PostList => imagePath(포스트 이미지 리스트) 를 만드는 서브 쿼리
        $subSql = "SELECT imagePath FROM PostsImages WHERE (1) ORDER BY regDate ASC";

        // reformFetch 의 array('postSeq', 'postSeq') =>[0] : where column 이름, [1] : 내용
        return $db->reformFetch($query, 'postsImagesList', $subSql, array('postSeq', 'postSeq'));
    }

    // 특정 회원 텍스트 게시글 조회
    public function getTextPosts($memberSeq)
    {
        $db = new db();

        $query = "SELECT
                P.postSeq,
                P.postContents,
                date_format(P.regDate,'%Y-%m-%d %H:%i:%S.0') as regDate
                FROM Posts P
                LEFT JOIN PostsImages IMG ON P.postSeq = IMG.postSeq
                WHERE P.memberSeq = '$memberSeq' AND IMG.postSeq IS NULL
                GROUP BY P.postSeq
                Order BY P.postSeq DESC";

        return $db->fetchAll($query);
    }

    // 특정 회원 게시물 중 클릭 게시글이 상단에 존재하는 타임라인
    public function getStandardPosts($postSeq, $memberSeq)
    {
        $db = new db();

        $query = "SELECT
                P.postSeq,
                P.memberSeq,
                M.profileImagePath,
                M.nickName,
                P.postContents,
                date_format(P.regDate,'%Y-%m-%d %H:%i:%S.0') AS regDate,
                (SELECT count(*) FROM PostsReply PR WHERE P.postSeq = PR.postSeq) AS replyCount,
                IF((SELECT count(*) FROM PostsLikes PL WHERE P.postSeq = PL.postSeq AND PL.memberSeq = '$memberSeq' AND PL.postSeq = P.postSeq),'true', 'false') AS isLikes
                FROM Posts P
                LEFT JOIN PostsImages IMG ON P.postSeq = IMG.postSeq
                JOIN Member M ON P.memberSeq = M.memberSeq
                WHERE P.memberSeq = '$memberSeq'
                GROUP BY P.postSeq
                Order BY 
                    FIELD(P.postSeq, '$postSeq') DESC,
                    P.postSeq DESC";

        // PostList => imagePath(포스트 이미지 리스트) 를 만드는 서브 쿼리
        $subSql = "SELECT imagePath FROM PostsImages WHERE (1) ORDER BY regDate ASC";

        // reformFetch 의 array('postSeq', 'postSeq') =>[0] : where column 이름, [1] : 내용
        return $db->reformFetch($query, 'postsImagesList', $subSql, array('postSeq', 'postSeq'));
    }

    public function updatePost($postSeq, $postContents)
    {
        $db = new db();
        $query = "update Posts set postContents = '{$postContents}', modDate = CURRENT_TIMESTAMP() where postSeq = '{$postSeq}'";
        return $db->execute($query);
    }

    public function deletePost($postSeq)
    {
        $db = new db();
        $query = "delete from Posts where postSeq = '{$postSeq}'";
        return $db->execute($query);
    }
}