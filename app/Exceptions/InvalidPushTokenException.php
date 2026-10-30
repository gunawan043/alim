<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Token FCM/APNs sudah tidak berlaku dan perangkat harus dicabut.
 */
class InvalidPushTokenException extends RuntimeException {}
