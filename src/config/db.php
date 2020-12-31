<?php

class db
{
    // Project Database
    private $PROJECT_DB = array(
        'AIINZ' => array(
            'host' => 'host',
            'user' => 'user',
            'password' => 'password'
        )
    );
    // Database Name
    private $DB_ARR = array('database');
    private $conn;

    public function __construct()
    {
        $this->conn = $this->connect();
    }

    public function __destruct()
    {
        $this->conn = null;
    }

    /**
     * Connect
     * @param int $dbname
     * 0일 경우 DB_ARR[0] 연결
     * @param string $project
     * 기본 'AIINZ' <<Get Project Database>>
     * @return PDO
     */
    public function connect($dbname = 0, $project = 'AIINZ')
    {
        $project = $this->PROJECT_DB[$project];

        $mysql_connect_str = "mysql:host={$project['host']};charset=utf8;";
        $mysql_connect_str .= "dbname={$this->DB_ARR[$dbname]};";

        $dbConnection = new PDO($mysql_connect_str, $project['user'], $project['password']);
        $dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        return $dbConnection;
    }

    /**
     * Select, READ, 조회
     * @param $sql
     * @return array|bool
     * Success : Array()
     * Fail : Boolean(false)
     */
    public function fetchAll($sql)
    {
        $db = $this->conn;
        $stmt = $db->query($sql);

        try {
            return $stmt->fetchAll(PDO::FETCH_OBJ);
        } catch (PDOException $e) {
            error_log("[PDOException]" . $e);
            return false;
        }
    }

    /**
     * UPDATE, DELETE, 수정, 삭제
     * @param $sql
     * @param array $param
     * UPDATE 일 경우 bindParam 넘길 값 // DELETE 일 경우 빈 배열
     * @return bool
     */
    public function execute($sql, $param = array())
    {
        $db = $this->conn;
        $stmt = $db->prepare($sql);
        if (count($param)) {
            foreach ($param as $key => &$value) {
                $stmt->bindParam($key, $value);
            }
        }
        try {
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("[PDOException]" . $e);
            return false;
        }
    }

    /**
     * INSERT, 삽입
     * @param $sql
     * @param $param
     * bindParam 넘길 값
     * @param array $dbname
     * 기본: 0,1 => 모든 DB_NAME 에 INSERT
     * @param array $project
     * <<Get Project Database>>
     * @return bool
     */
//    public function Insert($sql, $param, $dbname = array(0, 1), $project = array('AIINZ'))
    public function Insert($sql, $param, $dbname = array(0), $project = array('AIINZ'))
    {
        $db_count = count($dbname);
        $result_cnt = 0;
        for ($p = 0; $p < count($project); $p++) {
            if (!$this->PROJECT_DB[$project[$p]]) {
                continue;
            }
            for ($i = 0; $i < $db_count; $i++) {
                $db = $this->connect($dbname[$i], $project[$p]);
                $stmt = $db->prepare($sql);
                foreach ($param as $key => &$value) {
                    $stmt->bindParam($key, $value);
                }
                try {
                    $stmt->execute();
                } catch (PDOException $e) {
                    error_log("[PDOException]" . $e);
                    return false;
                }
                $result_cnt++;
                $db = null;
            }
        }
        return $db_count == $result_cnt;
    }

    public function reformFetch($sql, $ArrayName, $subsql, $subWhere = array())
    {

        $db = $this->conn;
        $stmt = $db->prepare($sql);
        $stmt->execute();

        try {
            $list = [];
            for ($i = 0; $row = $stmt->fetch(PDO::FETCH_ASSOC); $i++) {
                $field = $row[$subWhere[1]];
                $subRealSql = count($subWhere) > 0 ? str_replace('(1)', "{$subWhere[0]} = {$field}", $subsql) :
                    $subsql;
                $stmtSub = $db->query($subRealSql);
                $list[$i] = $row;
                $list[$i][$ArrayName] = $stmtSub->fetchAll(PDO::FETCH_OBJ);
            }
            return $list;
        } catch (PDOException $e) {
            error_log("[PDOException]" . $e);
            return false;
        }
    }

    public function multiReformFetch($sql, $ArrayName = array(), $subsql = array(), $subWhere = array())
    {
        $db = $this->conn;
        $stmt = $db->prepare($sql);
        $stmt->execute();

        try {
            $list = [];
            for ($i = 0; $row = $stmt->fetch(PDO::FETCH_ASSOC); $i++) {
                $list[$i] = $row;
                for ($j = 0; $j < count($ArrayName); $j++) {
                    $field = $row[$subWhere[$j][1]];
                    $subRealSql = count($subWhere) > 0 ? str_replace('(1)', "{$subWhere[$j][0]} = {$field}",
                        $subsql[$j]) :
                        $subsql[$j];
                    $stmtSub = $db->query($subRealSql);
                    $list[$i][$ArrayName[$j]] = $stmtSub->fetchAll(PDO::FETCH_OBJ);
                }
            }
            return $list;
        } catch (PDOException $e) {
            error_log("[PDOException]" . $e);
            return false;
        }
    }

    public function lastInsertId()
    {
        return $this->conn->lastInsertId();
    }
}
