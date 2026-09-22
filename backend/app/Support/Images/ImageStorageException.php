<?php

namespace App\Support\Images;

use RuntimeException;

/**
 * The image service failed or is not configured. Rendered as 502 with a user-facing message.
 */
class ImageStorageException extends RuntimeException {}
