<?php
/**
 * This file is part of the igogo5yo/yii2-upload-from-url project.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @copyright (c) igogo5yo
 * @link http://github.com/igogo5yo/yii2-upload-from-url
 */
namespace mamadali\S3Storage\behaviors;

use yii\helpers\Html;
use yii\db\ActiveRecord;
use yii\helpers\FileHelper;
use yii\base\Exception;
use yii\base\BaseObject;
use yii\web\UploadedFile;

/**
 * UploadFileByURL represents the information for an file by url address.
 *
 * You can call [[initWithModel()]] or  [[initWithUrl()]] or  [[initWithUrlAndModel()]]
 * with to retrieve the instance of file object, and then use [[saveAs()]] to save it on the server.
 * You may also query other information about the file, including [[name]],
 * [[extension]], [[type]], [[size]] etc.
 *
 * @author Skliar Ihor <skliar.ihor@gmail.com>
 * @since 1.0
 */
class UploadFromUrl extends BaseObject
{
    /**
     * @var string the original name of the file being uploaded
     */
    public $name;
    /**
     * @var string the name of the file without extension being uploaded
     */
    public $baseName;
    /**
     * @var string the MIME-type of the uploaded file (such as "image/gif").
     * Since this MIME type is not checked on the server side, do not take this value for granted.
     * Instead, use [[\yii\helpers\FileHelper::getMimeType()]] to determine the exact MIME type.
     */
    public $type;
    /**
     * @var integer the actual size of the uploaded file in bytes
     */
    public $size;
    /**
     * @var integer an error code describing the status of this file uploading.
     * @see http://www.php.net/manual/en/features.file-upload.errors.php
     */
    public $error = UPLOAD_ERR_OK;
    /**
     * @var integer the actual size of the uploaded file in bytes
     */
    public $extension;

    public $url;
    public $model;
    public $attribute;
    public $isWithModel = false;

    /**
     * String output.
     * This is PHP magic method that returns string representation of an object.
     * The implementation here returns the uploaded file's name.
     * @return string the string representation of the object
     */
    public function __toString()
    {
        return $this->name;
    }

    public static function getUploadedFile(string $url): UploadedFile|null
    {
        $self = self::initWithUrl($url);
        if($self->error === UPLOAD_ERR_OK){
            return new UploadedFile([
                'name' => $self->name,
                'type' => $self->type,
                'size' => $self->size,
                'tempName' => $self->url,
            ]);
        }
        return null;
    }

    public static function initWithUrl($url)
    {
        return self::createInstance([
            'url' => $url
        ]);
    }


    protected static function createInstance($options)
    {
        $options = self::extendOptions($options);
        return new static($options);
    }

    protected static function extendOptions(array $options)
    {
        $parsed_url = parse_url($options['url']);
        $headers = get_headers($options['url'], 1);

        if (!$parsed_url || !$headers || !preg_match('/^(HTTP)(.*)(200)(.*)/i', $headers[0])) {
            $options['error'] = UPLOAD_ERR_NO_FILE;
        }

        $options['name'] = isset($parsed_url['path']) ? pathinfo($parsed_url['path'], PATHINFO_BASENAME) : '';
        $options['baseName'] = isset($parsed_url['path']) ? pathinfo($parsed_url['path'], PATHINFO_FILENAME) : '';
        $options['extension'] = isset($parsed_url['path'])
            ? mb_strtolower(pathinfo($parsed_url['path'], PATHINFO_EXTENSION))
            : '';
        $options['size'] = isset($headers['Content-Length']) ? $headers['Content-Length'] : 0;
        $options['type'] = isset($headers['Content-Type'])
            ? $headers['Content-Type']
            : FileHelper::getMimeTypeByExtension($options['name']);

        return $options;
    }
}