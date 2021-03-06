<?php

use Exceptions\AuthInstagramException;
use Instagram\Followers;
use Instagram\Likes;
use Instagram\Publications;
use Instagram\Subscriptions;
use Instagram\Views;

/**
 * Created by PhpStorm.
 * User: Дмитрий
 * Date: 007 07.11
 * Time: 11:56:40
 */
class Instagram
{
    private static $csrfToken = '';

    private static $userId;

    private $subscriptions = [];

    private $followers = [];

    private $publications = [];

    public function __construct()
    {
        $curl1 = new Curl();
        $curl1->setUrl('https://www.instagram.com/')
            ->setHeader('Content-Type', 'text/html')
            ->run();
        $cookies = Curl::getCookieArray();
        if (!empty($cookies['csrftoken'])) {
            self::$csrfToken = $cookies['csrftoken'];
        }
        if (!empty($cookies['ds_user_id'])) {
            self::$userId = $cookies['ds_user_id'];
        }
        $this->auth();
    }

    public function auth()
    {
        try {
            $curl2 = new Curl();
            $curl2->setUrl('https://www.instagram.com/accounts/login/ajax/')
                ->setMethod('POST')
                ->setHeader('Content-Type', 'application/x-www-form-urlencoded')
                ->setHeader('accept-language', 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7')
                ->setHeader('X-Requested-With', 'XMLHttpRequest')
                ->setHeader('X-Instagram-AJAX', '1')
                ->setHeader('X-CSRFToken', self::$csrfToken)
                ->setHeader('Referer', 'https://www.instagram.com/')
                ->run([
                    'username' => Config::getConfig('instagram.login'),
                    'password' => Config::getConfig('instagram.password'),
                ]);
            $result = $curl2->getjson();
            if ($result['user'] === true) {
                if ($result['authenticated'] === true) {
                    $cookies = Curl::getCookieArray();
                    if (!empty($cookies['ds_user_id'])) {
                        self::$userId = $cookies['ds_user_id'];
                    }
                    return true;
                } else {
                    throw new AuthInstagramException('Не верный пароль от пользователя пользователь');
                }
            } else {
                throw new AuthInstagramException('Не верный пользователь');
            }
        } catch (\Exception $e) {
            die($e->getMessage());
        }
    }

    public function getFollowers()
    {
        if (!$this->followers) {
            $this->followers = Followers::getFollowers(self::$userId);
        }
        return $this->followers;
    }

    public function getSubscriptions()
    {
        if (!$this->subscriptions) {
            $this->subscriptions = Subscriptions::getSubscriptions(self::$userId);
        }
        return $this->subscriptions;
    }

    public function getPublications($count = false)
    {
        if (!$this->publications) {
            $this->publications = Publications::getPublications(self::$userId, $count);
        }
        return $this->publications;
    }

    public function getLikes($shortCode)
    {
        if (empty($shortCode)) {
            return null;
        }
        return Likes::getLikes($shortCode);
    }

    public static function getCSRFToken()
    {
        return self::$csrfToken;
    }

    public static function getUserId()
    {
        return self::$userId;
    }
}
