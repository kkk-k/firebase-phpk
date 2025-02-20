<?php

declare(strict_types=1);

namespace Kreait\Firebase\Tests\Integration;

use Kreait\Firebase\Contract\Auth;
use Kreait\Firebase\Tests\IntegrationTestCase;
use Lcobucci\JWT\Token\Plain;

/**
 * @internal
 */
class TenantAwareAuthTest extends IntegrationTestCase
{
    /** @var Auth */
    private $auth;

    protected function setUp(): void
    {
        $this->auth = self::$factory->withTenantId(self::TENANT_ID)->createAuth();
    }

    /**
     * @test
     */
    public function new_users_are_scoped_to_a_tenant(): void
    {
        $user = $this->auth->createUserWithEmailAndPassword(
            self::randomEmail(__FUNCTION__),
            'password123'
        );

        try {
            $this->assertSame(self::TENANT_ID, $user->tenantId);
        } finally {
            $this->auth->deleteUser($user->uid);
        }
    }

    /**
     * @test
     */
    public function custom_tokens_include_the_tenant(): void
    {
        $token = $this->auth->createCustomToken('some-uid');

        $this->assertInstanceOf(Plain::class, $token);
        $this->assertSame(self::TENANT_ID, $token->claims()->get('tenant_id'));
    }

    public function it_can_sign_in_anonymously(): void
    {
        $result = $this->auth->signInAnonymously();

        try {
            $this->assertSame(self::TENANT_ID, $result->firebaseTenantId());
            $this->auth->verifyIdToken($result->idToken());
        } finally {
            $this->auth->deleteUser($result->firebaseUserId());
        }
    }

    /**
     * @test
     */
    public function it_can_sign_in_with_a_custom_token(): void
    {
        $user = $this->auth->createAnonymousUser();
        $result = $this->auth->signInAsUser($user);

        try {
            $this->assertSame(self::TENANT_ID, $result->firebaseTenantId());
            $this->auth->verifyIdToken($result->idToken());
        } finally {
            $this->auth->deleteUser($result->firebaseUserId());
        }
    }
}
