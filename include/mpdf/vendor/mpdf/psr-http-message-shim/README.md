# psr-http-message-shim

Trait to allow support of different psr/http-message versions.

Based on the psr-log-aware-trait, developed by Matěj Humpál, K Widholm and Mark Dorison.

By including this shim, you can allow composer to resolve your Psr\Http\Message version for you.

## Use

Require the shim.

        composer require mpdf/psr-http-message-shim

Modify any use of mpdf's Request.php, Response.php, Stream.php and Uri.php classes to instead use versions
from the Mpdf\HttpMessageShim namespace.
