<?php

namespace App;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: "1.0.0",
    title: "Laravel Delivery Management API",
    description: "API documentation for the Laravel Developer Assessment Delivery System"
)]
#[OA\Server(
    url: "http://localhost:8000",
    description: "Local API Server"
)]
#[OA\SecurityScheme(
    securityScheme: "bearerAuth",
    type: "http",
    scheme: "bearer",
    bearerFormat: "Sanctum Token"
)]
class Swagger {}
