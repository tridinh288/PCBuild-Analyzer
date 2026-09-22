<?php

namespace Tests;

use App\Support\Images\FakeImageStorage;
use App\Support\Images\ImageStorage;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Http;

abstract class TestCase extends BaseTestCase
{
    protected FakeImageStorage $images;

    protected function setUp(): void
    {
        parent::setUp();

        // Tests never call Cloudinary or any other external service (D-022).
        Http::preventStrayRequests();
        $this->images = new FakeImageStorage;
        $this->app->instance(ImageStorage::class, $this->images);
    }
}
