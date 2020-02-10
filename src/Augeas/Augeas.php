<?php declare(strict_types=1);

namespace AugeasLib;

use FFI;
use AugeasLib\Exception\AugeasRuntimeError;
use AugeasLib\Exception\AugeasValueError;

class Augeas
{
    // Augeas flags
    const NONE = 0;
    // Keep the original file with a .augsave extension
    const SAVE_BACKUP = 1 << 0;
    // Save changes into a file with extension .augnew, and do not overwrite the original file.
    // Takes precedence over AUG_SAVE_BACKUP
    const SAVE_NEWFILE = 1 << 1;
    // Type check lenses; since it can be very expensive it is not done by default
    const TYPE_CHECK = 1 << 2;
    // Do not use the builtin load path for modules
    const NO_STDINC = 1 << 3;
    // Make save a no-op process, just record what would have changed
    const SAVE_NOOP = 1 << 4;
    // Do not load the tree from AUG_INIT
    const NO_LOAD = 1 << 5;
    const NO_MODL_AUTOLOAD = 1 << 6;
    // Track the span in the input of nodes
    const ENABLE_SPAN = 1 << 7;

    // Augeas errors
    const AUG_NOERROR = 0;
    const AUG_ENOMEM = 1;
    const AUG_EINTERNAL = 2;
    const AUG_EPATHX = 3;
    const AUG_ENOMATCH = 4;
    const AUG_EMMATCH = 5;
    const AUG_ESYNTAX = 6;
    const AUG_ENOLENS = 7;
    const AUG_EMXFM = 8;
    const AUG_ENOSPAN = 9;
    const AUG_EMVDESC = 10;
    const AUG_ECMDRUN = 11;
    const AUG_EBADARG = 12;
    const AUG_ELABEL = 13;
    const AUG_ECPDESC = 14;

    private static ?FFI $ffi = null;
    private FFI\CData $augeas;

    public function __construct(?string $root = null, ?string $loadPath = null, int $flag = self::NONE)
    {
        if (!self::$ffi instanceof FFI) {
            // TODO implement FFI::scope()
            // TODO add in opcache if it's enabled
            self::$ffi = FFI::load(__DIR__ . '/headers/php-augeas.h');
        }

        $this->augeas = self::$ffi->aug_init($root, $loadPath, $flag);

        $errorCode = $this->getAugErrorCode();
        if ($errorCode !== 0) {
            throw new AugeasRuntimeError('Unable to initialize augeas.', $errorCode);
        }
    }

    /**
     * Lookup the value associated with `path`. It is an error if more than one node matches `path`.
     * @param string $path
     * @return string|null
     * @throws AugeasValueError
     */
    public function get(string $path): ?string
    {
        $value = FFI::new("char*[1]");

        // Call the function and pass value by reference (char **)
        $ret = self::$ffi->aug_get($this->augeas, $path, $value);

        if ($ret < 0) {
            throw new AugeasValueError(sprintf('Unable to get path \'%s\'.', $path), 1);
        }

        if ($value[0] == null) {
            return null;
        }

        return FFI::string( $value[0]);
    }

    /**
     * Get augeas error code
     * @return int
     */
    private function getAugErrorCode(): int
    {
        return self::$ffi->aug_error($this->augeas);
    }
}
