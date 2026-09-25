<?php

return [
    App\Providers\AppServiceProvider::class,

    // Business modules — one provider per module, mirroring lib/features/* of the Flutter app.
    App\Modules\Identity\IdentityServiceProvider::class,
    App\Modules\Profile\ProfileServiceProvider::class,
];
