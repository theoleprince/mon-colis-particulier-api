<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Shared\Providers\ApiDocsServiceProvider::class,

    // Business modules — one provider per module, mirroring lib/features/* of the Flutter app.
    App\Modules\Identity\IdentityServiceProvider::class,
    App\Modules\Profile\ProfileServiceProvider::class,
    App\Modules\Delivery\DeliveryServiceProvider::class,
];
