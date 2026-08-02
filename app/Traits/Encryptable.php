<?php

namespace App\Traits;

use Illuminate\Support\Facades\Crypt;

trait Encryptable
{
    /**
     * Encrypt value
     */
    private function encryptValue($value)
    {
        if ($value === null || $value === '') {
            return $value;
        }

        return Crypt::encryptString($value);
    }

    /**
     * Decrypt value
     */
    private function decryptValue($value, $purpose = '')
    {
        if ($value === null || $value === '') {
            return $value;
        }

        try {
            return Crypt::decryptString($value);
        } catch (\Exception $e) {
            \Log::error("Failed to decrypt value for {$purpose}: ".$e->getMessage());

            return null;
        }
    }

    /**
     * Get encrypted attributes
     */
    public function getEncryptedAttributes()
    {
        return property_exists($this, 'encrypted') ? $this->encrypted : [];
    }

    /**
     * Check if attribute is encrypted
     */
    public function isEncrypted($key)
    {
        return in_array($key, $this->getEncryptedAttributes());
    }
}
