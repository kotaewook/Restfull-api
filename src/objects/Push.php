<?php

class Push
{
    private $url = 'https://fcm.googleapis.com/fcm/send';
    private $key = 'AAAAhX9E6bs:APA91bHKGuLAHOyU_QV9jEjU8lMQw0mjA-Yf3TD9otmKLyLmiUKrjQNSELuqhhBVnfVkF6fjcVPkxXrl0WRWIcIxhK8ESD7ASOETQqxx1czBw9LHIKM4qP4AZrVabMQH5TXEaFypwuYP';

    /**
     * FCM Push 전송
     * @param $fcmToken
     * @param $fcmTitle
     * @param $fcmContents
     * @param array $data
     * @return bool
     */
    public function sendPush($fcmToken, $fcmContents, $data = array())
    {
        $fcmTitle = 'AIINZ';
        $registrationIds = array($fcmToken);

        // prep the bundle
        $msg = array
        (
            'title' => $fcmTitle,
            'body' => $fcmContents,
        );

        $fields = array
        (
            'registration_ids' => $registrationIds,
            'data' => $msg
        );

        if ($data) {
            $fields['fromMemberSeq'] = $data['fromMemberSeq'];
            $fields['fromProfileImagePath'] = $data['fromProfileImagePath'];
            $fields['fromNickName'] = $data['fromNickName'];
            $fields['profileImagePath'] = $data['profileImagePath'];
        }

        $headers = array
        (
            'Authorization: key=' . $this->key,
            'Content-Type: application/json; UTF-8'
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        $result = curl_exec($ch);
        $curlResult = curl_getinfo($ch);
        curl_close($ch);
        $curlResult = $curlResult['http_code'];

        if ($curlResult == 200) {
            $pushStatus = ($result['success']) ? 'success' : 'fail';
        } else {
            $pushStatus = 'fail';
        }
        return $this->insertPush($fcmToken, $fcmTitle, $fcmContents, $pushStatus);
    }

    /**
     * Push 전송에 따른 Push 기록
     * @param $fcmToken
     * @param $fcmTitle
     * @param $fcmContents
     * @param $pushStatus
     * @return bool
     */
    public function insertPush($fcmToken, $fcmTitle, $fcmContents, $pushStatus)
    {
        $db = new db();
        $query = "INSERT INTO
                Push (fcmToken, pushTitle, pushContents, pushStatus, pushStatusDate)
            VALUES (
                '$fcmToken'
                , '$fcmTitle'
                , '$fcmContents'
                , '$pushStatus'
                , CURRENT_TIMESTAMP
            )";
        return $db->execute($query);
    }
}