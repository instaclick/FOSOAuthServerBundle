<?php

/*
 * This file is part of the FOSOAuthServerBundle package.
 *
 * (c) FriendsOfSymfony <http://friendsofsymfony.github.com/>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FOS\OAuth2ServiceBundle\Tests\Storage;

use FOS\OAuthServerBundle\Model\AccessToken;
use FOS\OAuthServerBundle\Model\RefreshToken;
use FOS\OAuthServerBundle\Model\AuthCode;
use FOS\OAuthServerBundle\Model\Client;
use FOS\OAuthServerBundle\Storage\OAuthStorage;

class OAuthStorageTest extends \PHPUnit_Framework_TestCase
{
    protected $clientManager;

    protected $accessTokenManager;

    protected $refreshTokenManager;

    protected $authCodeManager;

    protected $userProvider;

    protected $encoderFactory;

    protected $storage;

    public function setUp()
    {
        $this->clientManager = $this->getMock('FOS\OAuthServerBundle\Model\ClientManagerInterface');
        $this->accessTokenManager = $this->getMock('FOS\OAuthServerBundle\Model\AccessTokenManagerInterface');
        $this->refreshTokenManager = $this->getMock('FOS\OAuthServerBundle\Model\RefreshTokenManagerInterface');
        $this->authCodeManager = $this->getMock('FOS\OAuthServerBundle\Model\AuthCodeManagerInterface');
        $this->userProvider = $this->getMock('Symfony\Component\Security\Core\User\UserProviderInterface');
        $this->encoderFactory = $this->getMock('Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface');

        $this->storage = new OAuthStorage($this->clientManager, $this->accessTokenManager, $this->refreshTokenManager, $this->authCodeManager, $this->userProvider, $this->encoderFactory);
    }

    public function testGetClientReturnsClientWithGivenId()
    {
        $client = new Client;

        $this->clientManager->expects($this->once())
            ->method('findClientByPublicId')
            ->with('123_abc')
            ->will($this->returnValue($client));

        $this->assertSame($client, $this->storage->getClient('123_abc'));
    }

    public function testGetClientReturnsNullIfNotExists()
    {
        $client = new Client;

        $this->clientManager->expects($this->once())
            ->method('findClientByPublicId')
            ->with('123_abc')
            ->will($this->returnValue(null));

        $this->assertNull($this->storage->getClient('123_abc'));
    }

    public function testCheckClientCredentialsThrowsIfInvalidClientClass()
    {
        $client = $this->getMock('OAuth2\Model\IOAuth2Client');

        $this->setExpectedException('InvalidArgumentException');
        $this->storage->checkClientCredentials($client, 'dummy');
    }

    public function testCheckClientCredentialsReturnsTrueOnValidCredentials()
    {
        $client = new Client;
        $client->setSecret('dummy');

        $this->assertTrue($this->storage->checkClientCredentials($client, 'dummy'));
    }

    public function testCheckClientCredentialsReturnsFalseOnValidCredentials()
    {
        $client = new Client;
        $client->setSecret('dummy');

        $this->assertFalse($this->storage->checkClientCredentials($client, 'passe'));
    }

    public function testGetAccessTokenReturnsAccessTokenWithGivenId()
    {
        $token = new AccessToken;

        $this->accessTokenManager->expects($this->once())
            ->method('findTokenByToken')
            ->with('123_abc')
            ->will($this->returnValue($token));

        $this->assertSame($token, $this->storage->getAccessToken('123_abc'));
    }

    public function testGetAccessTokenReturnsNullIfNotExists()
    {
        $token = new AccessToken;

        $this->accessTokenManager->expects($this->once())
            ->method('findTokenByToken')
            ->with('123_abc')
            ->will($this->returnValue(null));

        $this->assertNull($this->storage->getAccessToken('123_abc'));
    }

    public function testCreateAccessTokenThrowsOnInvalidClientClass()
    {
        $client = $this->getMock('OAuth2\Model\IOAuth2Client');

        $this->setExpectedException('InvalidArgumentException');
        $this->storage->createAccessToken('foo', $client, 42, 1, 'foo bar');
    }

    public function testCreateAccessToken()
    {
        $savedToken = null;

        $this->accessTokenManager->expects($this->once())
            ->method('createToken')
            ->with()
            ->will($this->returnValue(new AccessToken));
        $this->accessTokenManager->expects($this->once())
            ->method('updateToken')
            ->will($this->returnCallback(function($token) use (&$savedToken) {
                $savedToken = $token;
            }));

        $client = new Client;

        $token = $this->storage->createAccessToken('foo', $client, 42, 1, 'foo bar');

        $this->assertEquals($token, $savedToken);

        $this->assertSame('foo', $token->getToken());
        $this->assertSame($client, $token->getClient());
        $this->assertSame(42, $token->getData());
        $this->assertSame(1, $token->getExpiresAt());
        $this->assertSame('foo bar', $token->getScope());
    }

    public function testGetRefreshTokenReturnsRefreshTokenWithGivenId()
    {
        $token = new RefreshToken;

        $this->refreshTokenManager->expects($this->once())
            ->method('findTokenByToken')
            ->with('123_abc')
            ->will($this->returnValue($token));

        $this->assertSame($token, $this->storage->getRefreshToken('123_abc'));
    }

    public function testGetRefreshTokenReturnsNullIfNotExists()
    {
        $this->refreshTokenManager->expects($this->once())
            ->method('findTokenByToken')
            ->with('123_abc')
            ->will($this->returnValue(null));

        $this->assertNull($this->storage->getRefreshToken('123_abc'));
    }

    public function testCreateRefreshTokenThrowsOnInvalidClientClass()
    {
        $client = $this->getMock('OAuth2\Model\IOAuth2Client');

        $this->setExpectedException('InvalidArgumentException');
        $this->storage->createRefreshToken('foo', $client, 42, 1, 'foo bar');
    }

    public function testCreateRefreshToken()
    {
        $savedToken = null;

        $this->refreshTokenManager->expects($this->once())
            ->method('createToken')
            ->with()
            ->will($this->returnValue(new RefreshToken));
        $this->refreshTokenManager->expects($this->once())
            ->method('updateToken')
            ->will($this->returnCallback(function($token) use (&$savedToken) {
                $savedToken = $token;
            }));

        $client = new Client;

        $token = $this->storage->createRefreshToken('foo', $client, 42, 1, 'foo bar');

        $this->assertEquals($token, $savedToken);

        $this->assertSame('foo', $token->getToken());
        $this->assertSame($client, $token->getClient());
        $this->assertSame(42, $token->getData());
        $this->assertSame(1, $token->getExpiresAt());
        $this->assertSame('foo bar', $token->getScope());
    }

    public function testCheckRestrictedGrantTypeThrowsOnInvalidClientClass()
    {
        $client = $this->getMock('OAuth2\Model\IOAuth2Client');

        $this->setExpectedException('InvalidArgumentException');

        $this->storage->checkRestrictedGrantType($client, 'foo');
    }

    public function testCheckRestrictedGrantType()
    {
        $client = new Client;
        $client->setAllowedGrantTypes(array('foo', 'bar'));

        $this->assertTrue($this->storage->checkRestrictedGrantType($client, 'foo'));
        $this->assertTrue($this->storage->checkRestrictedGrantType($client, 'bar'));
        $this->assertFalse($this->storage->checkRestrictedGrantType($client, 'baz'));
    }

    public function testCheckUserCredentialsThrowsOnInvalidClientClass()
    {
        $client = $this->getMock('OAuth2\Model\IOAuth2Client');

        $this->setExpectedException('InvalidArgumentException');

        $this->storage->checkUserCredentials($client, 'Joe', 'baz');
    }

    public function testCheckUserCredentialsCatchesAuthenticationExceptions()
    {
        $client = new Client;

        $result = $this->storage->checkUserCredentials($client, 'Joe', 'baz');

        $this->assertFalse($result);
    }

    public function testCheckUserCredentialsReturnsTrueOnValidCredentials()
    {
        $client = new Client;
        $user = $this->getMock('Symfony\Component\Security\Core\User\UserInterface');
        $user->expects($this->once())
            ->method('getPassword')->with()->will($this->returnValue('foo'));
        $user->expects($this->once())
            ->method('getSalt')->with()->will($this->returnValue('bar'));

        $encoder = $this->getMock('Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface');
        $encoder->expects($this->once())
            ->method('isPasswordValid')
            ->with('foo', 'baz', 'bar')
            ->will($this->returnValue(true));

        $this->userProvider->expects($this->once())
            ->method('loadUserByUsername')
            ->with('Joe')
            ->will($this->returnValue($user));

        $this->encoderFactory->expects($this->once())
            ->method('getEncoder')
            ->with($user)
            ->will($this->returnValue($encoder));

        $this->assertSame(array(
            'data' => $user,
        ), $this->storage->checkUserCredentials($client, 'Joe', 'baz'));
    }

    public function testCheckUserCredentialsReturnsFalseOnInvalidCredentials()
    {
        $client = new Client;
        $user = $this->getMock('Symfony\Component\Security\Core\User\UserInterface');
        $user->expects($this->once())
            ->method('getPassword')->with()->will($this->returnValue('foo'));
        $user->expects($this->once())
            ->method('getSalt')->with()->will($this->returnValue('bar'));

        $encoder = $this->getMock('Symfony\Component\Security\Core\Encoder\PasswordEncoderInterface');
        $encoder->expects($this->once())
            ->method('isPasswordValid')
            ->with('foo', 'baz', 'bar')
            ->will($this->returnValue(false));

        $this->userProvider->expects($this->once())
            ->method('loadUserByUsername')
            ->with('Joe')
            ->will($this->returnValue($user));

        $this->encoderFactory->expects($this->once())
            ->method('getEncoder')
            ->with($user)
            ->will($this->returnValue($encoder));

        $this->assertFalse($this->storage->checkUserCredentials($client, 'Joe', 'baz'));
    }

    public function testCheckUserCredentialsReturnsFalseIfUserNotExist()
    {
        $client = new Client;

        $this->userProvider->expects($this->once())
            ->method('loadUserByUsername')
            ->with('Joe')
            ->will($this->returnValue(null));

        $this->assertFalse($this->storage->checkUserCredentials($client, 'Joe', 'baz'));
    }

    public function testCreateAuthCodeThrowsOnInvalidClientClass()
    {
        $client = $this->getMock('OAuth2\Model\IOAuth2Client');

        $this->setExpectedException('InvalidArgumentException');
        $this->storage->createAuthCode('foo', $client, 42, 'http://www.example.com/', 1, 'foo bar');
    }

    public function testCreateAuthCode()
    {
        $savedCode = null;

        $this->authCodeManager->expects($this->once())
            ->method('createAuthCode')
            ->with()
            ->will($this->returnValue(new AuthCode));
        $this->authCodeManager->expects($this->once())
            ->method('updateAuthCode')
            ->will($this->returnCallback(function($code) use (&$savedCode) {
                $savedCode = $code;
            }));

        $client = new Client;

        $code = $this->storage->createAuthCode('foo', $client, 42, 'http://www.example.com/', 1, 'foo bar');

        $this->assertEquals($code, $savedCode);

        $this->assertSame('foo', $code->getToken());
        $this->assertSame($client, $code->getClient());
        $this->assertSame(42, $code->getData());
        $this->assertSame(1, $code->getExpiresAt());
        $this->assertSame('foo bar', $code->getScope());
    }

    public function testGetAuthCodeReturnsAuthCodeWithGivenId()
    {
        $code = new AuthCode();

        $this->authCodeManager->expects($this->once())
            ->method('findAuthCodeByToken')
            ->with('123_abc')
            ->will($this->returnValue($code));

        $this->assertSame($code, $this->storage->getAuthCode('123_abc'));
    }

    public function testGetAuthCodeReturnsNullIfNotExists()
    {
        $this->authCodeManager->expects($this->once())
            ->method('findAuthCodeByToken')
            ->with('123_abc')
            ->will($this->returnValue(null));

        $this->assertNull($this->storage->getAuthCode('123_abc'));
    }
}
