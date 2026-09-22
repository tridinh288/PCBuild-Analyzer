<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * The request is valid but conflicts with existing data (HTTP 409), e.g. deleting a product
 * that templates still use. The message is shown to the admin.
 */
class ConflictException extends RuntimeException {}
