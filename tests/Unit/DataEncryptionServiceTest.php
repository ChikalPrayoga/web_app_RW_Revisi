<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Security\DataEncryptionService;
use Tests\TestCase;

class DataEncryptionServiceTest extends TestCase
{
    public function test_encryption_produces_different_ciphertext_for_same_plaintext_due_to_random_iv(): void
    {
        $plaintext = '3216011505900021';

        $ciphertext1 = DataEncryptionService::encrypt($plaintext);
        $ciphertext2 = DataEncryptionService::encrypt($plaintext);

        $this->assertNotEmpty($ciphertext1);
        $this->assertNotEmpty($ciphertext2);
        $this->assertNotEquals($ciphertext1, $ciphertext2, 'AES-256-CBC with random IV must produce different ciphertexts.');

        // Both decrypt back to same plaintext
        $this->assertEquals($plaintext, DataEncryptionService::decrypt($ciphertext1));
        $this->assertEquals($plaintext, DataEncryptionService::decrypt($ciphertext2));
    }

    public function test_deterministic_hash_produces_consistent_hmac_sha256_hash(): void
    {
        $plaintext = '3216010101230012';

        $hash1 = DataEncryptionService::deterministicHash($plaintext);
        $hash2 = DataEncryptionService::deterministicHash($plaintext);

        $this->assertNotEmpty($hash1);
        $this->assertEquals(64, strlen($hash1));
        $this->assertEquals($hash1, $hash2, 'Deterministic HMAC-SHA256 must produce identical hash for same input.');
    }

    public function test_deterministic_hash_differs_for_different_inputs(): void
    {
        $hash1 = DataEncryptionService::deterministicHash('3216010101230011');
        $hash2 = DataEncryptionService::deterministicHash('3216010101230012');

        $this->assertNotEquals($hash1, $hash2);
    }
}
