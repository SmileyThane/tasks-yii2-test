<?php
declare(strict_types=1);

namespace app\modules\api\tests\support;

use yii\web\HeaderCollection;
use yii\web\JsonParser;
use yii\web\Request;

class TestRequest extends Request
{
    private array $body = [];
    private HeaderCollection $headersOverride;

    public function __construct($config = [])
    {
        $config = array_merge([
            'enableCookieValidation' => false,
            'cookieValidationKey'    => 'tests-secret',
            'enableCsrfCookie'       => false,
            'parsers'                => ['application/json' => JsonParser::class],
        ], $config);

        parent::__construct($config);
        $this->headersOverride = new \yii\web\HeaderCollection();
    }

    public function setBodyParams($values): void
    {
        $this->body = $values;
    }

    public function getBodyParams()
    {
        return $this->body ?: parent::getBodyParams();
    }

    public function setAuthBearer(?string $token): void
    {
        if ($token) {
            $this->headersOverride->set('Authorization', 'Bearer ' . $token);
        } else {
            $this->headersOverride->remove('Authorization');
        }
    }

    public function getHeaders(): HeaderCollection
    {
        $base = parent::getHeaders();
        $merged = new HeaderCollection($base->toArray());
        foreach ($this->headersOverride as $k => $v) {
            $merged->set($k, $v);
        }
        return $merged;
    }
}