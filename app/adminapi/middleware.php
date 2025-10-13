<?php
return [
    \app\adminapi\middleware\JWT::class,
    \app\middleware\AllowCrossDomain::class,
    \app\middleware\Reptiles::class
];