<?php

namespace tests\unit\components;

use app\components\JwtHelper;

class JwtHelperTest extends \Codeception\Test\Unit
{
    public function testEncodeAndDecode()
    {
        $payload = [
            'sub' => '123',
            'name' => 'John Doe',
            'iat' => time(),
            'exp' => time() + 3600
        ];
        $secret = 'test-secret-key';

        $token = JwtHelper::encode($payload, $secret);

        $this->assertIsString($token);
        $this->assertCount(3, explode('.', $token));

        $decoded = JwtHelper::decode($token, $secret);

        $this->assertNotNull($decoded);
        $this->assertEquals($payload['sub'], $decoded['sub']);
        $this->assertEquals($payload['name'], $decoded['name']);
    }

    public function testDecodeInvalidToken()
    {
        $secret = 'test-secret-key';
        $invalidToken = 'header.payload.signature-invalid';

        $decoded = JwtHelper::decode($invalidToken, $secret);
        $this->assertNull($decoded);
    }

    public function testDecodeExpiredToken()
    {
        $payload = [
            'sub' => '123',
            'exp' => time() - 3600 // Expired 1 hour ago
        ];
        $secret = 'test-secret-key';

        $token = JwtHelper::encode($payload, $secret);
        $decoded = JwtHelper::decode($token, $secret);

        $this->assertNull($decoded);
    }

    public function testDecodeWrongSecret()
    {
        $payload = ['sub' => '123'];
        $secret = 'correct-secret';
        $wrongSecret = 'wrong-secret';

        $token = JwtHelper::encode($payload, $secret);
        $decoded = JwtHelper::decode($token, $wrongSecret);

        $this->assertNull($decoded);
    }
}
