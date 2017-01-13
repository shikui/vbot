<?php
/**
 * Created by PhpStorm.
 * User: HanSon
 * Date: 2017/1/9
 * Time: 16:18
 */

namespace Hanson\Robot\Message;


/**
 * Class UploadAble
 * @package Hanson\Robot\Message\
 *
 * @property  string static $mediaCount
 */
trait UploadAble
{

    static $file;

    /**
     * @param $username
     * @param $file
     * @return bool|mixed|string
     */
    public static function uploadMedia($username, $file)
    {
        $url = 'https://file.wx2.qq.com/cgi-bin/mmwebwx-bin/webwxuploadmedia?f=json';
        static::$mediaCount = ++static::$mediaCount;
        static::$file = $file;

        list($mime, $mediaType) = static::getMediaType($file);

        $data = [
            'id' => 'WU_FILE_' .static::$mediaCount,
            'name' => basename($file),
            'type' => $mime,
            'lastModifieDate' => gmdate('D M d Y H:i:s TO', filemtime($file)).' (CST)',
            'size' => filesize($file),
            'mediatype' => $mediaType,
            'uploadmediarequest' => json_encode([
                'BaseRequest' => server()->baseRequest,
                'ClientMediaId' => time(),
                'TotalLen' => filesize($file),
                'StartPos' => 0,
                'DataLen' => filesize($file),
                'MediaType' => 4,
                'UploadType' => 2,
                'FromUserName' => myself()->username,
                'ToUserName' => $username,
                'FileMd5' => md5_file($file)
            ], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
            'webwx_data_ticket' => static::getTicket(),
            'pass_ticket' => (server()->passTicket),
            'filename' => fopen($file, 'r'),
        ];

        $data = static::dataToMultipart($data);

        $result = http()->request($url, 'post', [
            'multipart' => $data
        ]);
        $result = json_decode($result, true);

        if($result['BaseResponse']['Ret'] == 0){
            return $result;
        }

        return false;
    }

    private static function getMediaType($file)
    {
        $info = finfo_open(FILEINFO_MIME_TYPE);
        $mime =  finfo_file($info, $file);
        finfo_close($info);

        return [$mime, explode('/', $mime)[0] === 'image' ? 'pic' : 'doc'];
    }

    private static function getTicket()
    {
        $cookies = http()->getClient()->getConfig('cookies')->toArray();

        $key = array_search('webwx_data_ticket', array_column($cookies, 'Name'));

        return $cookies[$key]['Value'];
    }

    private static function dataToMultipart($data)
    {
        $result = [];

        foreach ($data as $key => $item) {
            $field = [
                'name' => $key,
                'contents' => $item
            ];
            if($key === 'filename'){
                $field['filename'] = basename(static::$file);
            }
            $result[] = $field;
        }

        return $result;
    }

}